<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\SQLite;

use Apex\Db\Drivers\SQLite\Convert;
use Apex\Db\Exceptions\DbInvalidArgumentException;

/**
 * Helper class to format SQL queries properly against SQL injection, et al.
 */
class Format
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
            preg_match("/int\(/", $col_type) ? true : false => '%i',
            preg_match("/decimal\(/", $col_type) ? true : false => '%d', 
            preg_match("/blob/", $col_type) ? true : false => '%blob', 
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
        $sql = Convert::convert($sql, $conn);
        list($values, $raw_sql) = array([], $sql);
        array_unshift($args, $sql);

        // Go through args
        $x=1;
        preg_match_all("/\%(\w+)|\{(.+?)\}/", $raw_sql, $args_match, PREG_SET_ORDER);
        foreach ($args_match as $match) { 
            $value = $args[$x] ?? '';
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

            // Add to bind params, and values
            if ($col_type == 'ls') { $value = '%' . $value . '%'; }
            $values[] = $value;

            // Replace placeholder in SQL
            $args[0] = preg_replace("/" . preg_quote($match[0]) . "/", '?', $args[0], 1);
            $raw_sql = preg_replace("/" . preg_quote($match[0]) . "/", "'" . $value . "'", $raw_sql, 1);
        $x++; }

        // Return
        return array($args[0], $raw_sql, $values);
    }

    /**
     * Check value
     */
    public static function checkValue(string $type, string $value):?string
    {

        // Initial checks / formatting
        if ($type == 'd' && is_float($value) && preg_match('/e/i', (string) $value)) { 
            $value = sprintf("%f", floatval($value)); 
        } elseif (in_array($type, ['i', 'd', 'b']) && $value == '') { 
            $value = '0';
        }

        // Check if value valid
        $is_valid = match (true) {
            ($type == 'i' && !preg_match("/[0-9]+/", ltrim($value, '-'))) ? true : false => false, 
            ($type == 'd' && !preg_match("/^[0-9]+(\.[0-9]{1,8})?$/", ltrim($value, '-'))) ? true : false => false, 
            ($type == 'b' && !in_array($value, ['0', '1'])) ? true : false => false, 
            ($type == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) ? true : false => false, 
            ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) ? true : false => false, 
            ($type == 'ds' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) ? true : false => false, 
            ($type == 'ts' && !preg_match("/^\d{2}:\d{2}:\d{2}$/", $value)) ? true : false => false, 
            ($type == 'dt' && !preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $value)) ? true : false => false, 
            default => true
        };

        // Return
        return $is_valid === true ? (string) $value : null;
    }

    /**
     * Get bind param
     */
    public static function getBindParam(string $col_type):int
    {
        $param = match($col_type) {
            'i', 'b' => SQLITE3_INTEGER, 
            'd' => SQLITE3_FLOAT, 
            'blob' => SQLITE3_BLOB, 
            default => SQLITE3_TEXT
        };

        // Return
        return $param;

    }

    /**
     * Validate params
     */
    public static function validateParams(array $params):array
    {

        // Check required
        if (!isset($params['dbname'])) { 
            throw new DbInvalidArgumentException("No 'dbname' element specified within mySQL connection parameters.");
        }

        // Default parameters
        if (!isset($params['user'])) { $params['user'] = ''; }
        if (!isset($params['password'])) { $params['password'] = ''; }
        if (!isset($params['host'])) { $params['host'] = 'localhost'; }
        if (!isset($params['port'])) { $params['port'] = 3306; }

        // Return
        return $params;
    }

}



