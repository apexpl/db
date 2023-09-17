<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\mySQL;

use Apex\Db\Drivers\AbstractFormat;
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
     * Format SQL statement
     */
    public static function stmt($conn, string $sql, $args)
    { 

        // Initialize
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
        if (is_object($value) && $value::class == 'DateTime') {
            $orig_value = $value->format('Y-m-d H:i:s');
        } else {
                $orig_value = (string) $value;
            }

            // Check value
            if (($value = self::checkValue($col_type, $orig_value)) === null) {  
                throw new DbInvalidArgumentException("Invalid SQL argument, expecting a " . (self::$data_types[$col_type] ?? $col_type) . " and received '$orig_value' instead within SQL statement, $raw_sql");
            }
            $values[] = $col_type == 'ls' ? '%' . $value . '%' : $value;

            // Replace placeholder in SQL
            $args[0] = preg_replace("/" . preg_quote($match[0]) . "/", '?', $args[0], 1);
            $raw_sql = preg_replace("/" . preg_quote($match[0]) . "/", "'" . $value . "'", $raw_sql, 1);
        }

        // Return
        return array($args[0], $raw_sql, $values);
    }

    /**
     * Get placeholder based on column type 
     */
    public static function getPlaceholder(string $col_type):string
    {

        $col_type = strtolower($col_type);
        $type = match (true) {
            $col_type == 'boolean' => '%b', 
            $col_type == 'tinyint(1)' => '%b', 
            preg_match("/int\(/", $col_type) ? true : false => '%i',
            preg_match("/decimal\(/", $col_type) ? true : false => '%d', 
            preg_match("/blob/", $col_type) ? true : false => '%blob', 
            default => '%s'
        };

        // Return
        return $type;

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
        if (!isset($params['port'])) { $params['port'] = 3306; }

        // Return
        return $params;
    }

}



