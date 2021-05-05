<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\PostgreSQL;

use Apex\Db\Drivers\AbstractFormat;
use Apex\Db\Drivers\PostgreSQL\Convert;
use Apex\Db\Exceptions\DbInvalidArgumentException;

/**
 * Helper class to format SQL queries properly against SQL injection, et al.
 */
class Format extends AbstractFormat
{

    // Data types
    private static array $data_types = [
        'b' => 'boolean', 
        'i' => 'integer', 
        'd' => 'decimal', 
        's' => 'string', 
        'blob' => 'blob', 
        'url' => 'url', 
        'email' => 'email', 
        'ds' => 'date stamp', 
        'dt' => 'datetime stamp', 
        'ts' => 'timestamp'
    ];
    /**
     * Get placeholder based on column type 
     */
    public static function getPlaceholder(string $col_type):string
    {

        $col_type = strtolower($col_type);
        $type = match (true) {
            $col_type == 'tinyint(1)' => '%b', 
            $col_type == 'boolean' => '%b', 
            preg_match("/int\(/", $col_type) ? true : false => '%i',
            preg_match("/decimal\(/", $col_type) ? true : false => '%d', 
            preg_match("/bytea/", $col_type) ? true : false => '%blob', 
            default => '%s'
        };

        // Return
        return $type;

    }

    /**
     * Format SQL statement
     */
    public static function stmt($conn, string $sql, $args)
    { 

        // Initialize
        $sql = Convert::convert($conn, $sql);
        list($values, $raw_sql) = array([], $sql);
        array_unshift($args, $sql);

        // Go through args
        $x=1;
        preg_match_all("/\%(\w+)|\{(.+?)\}/", $raw_sql, $args_match, PREG_SET_ORDER);
        foreach ($args_match as $match) { 
            $value = $args[$x++] ?? '';
            if (str_starts_with($match[0], '%')) { 
                $col_type = $match[1];
            } else {
                $col_type = 's';
                if (is_array($args[1]) && isset($args[1][$match[2]])) { 
                    $value = $args[1][$match[2]];
                }
            }
            $orig_value = (string) $value;

            // Check value
            if (($value = self::checkValue($col_type, $orig_value)) === null) {  
                throw new DbInvalidArgumentException("Invalid SQL argument, expecting a " . (self::$data_types[$col_type] ?? $col_type) . " and received '$orig_value' instead within SQL statement, $raw_sql");
            }

            // Add to values
            if (preg_match("/blob|bytea/i", $col_type)) { 
                $value = mb_convert_encoding($value, 'UTF-8');
            } elseif ($col_type == 'b') { 
                $value = $value == 1 ? 't' : 'f';
            }
            $values[] = $col_type == 'ls' ? '%' . $value . '%' : $value;

            // Replace placeholder in SQL
            $args[0] = preg_replace("/" . preg_quote($match[0]) . "/", '?', $args[0], 1);
            $value = preg_match("/blob|bytea/i", $col_type) ? '--BLOB--' : $value;
            $raw_sql = preg_replace("/" . preg_quote($match[0]) . "/", "'" . $value . "'", $raw_sql, 1);
        }

        // Return
        return array($args[0], $raw_sql, $values);
    }

    /**
     * Validate params
     */
    public static function validateParams(array $params):array
    {

        // Check required
        if (!isset($params['dbname'])) { 
            throw new DbInvalidArgumentException("No 'dbname' element specified within mySQL connection parameters.");
        } elseif (!isset($params['user'])) { 
            throw new DbInvalidArgumentException("No 'user' element specified within mySQL connection parameters.");
        }

        // Default parameters
        if (!isset($params['password'])) { $params['password'] = ''; }
        if (!isset($params['host'])) { $params['host'] = 'localhost'; }
        if (!isset($params['port'])) { $params['port'] = 5432; }

        // Return
        return $params;
    }

}




