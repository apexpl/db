<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers;

/**
 * Abstract formatter
 */
class AbstractFormat
{

    /**
     * Check value
     */
    public static function checkValue(string $type, string $value):?string
    {

        // Initial checks / formatting
        if ($type == 'd' && is_float($value) && preg_match('/e/i', (string) $value)) { 
            $value = sprintf("%f", floatval($value)); 
        } elseif ($type == 'b' && is_bool($value)) { 
            $value = $value === true ? '1' : '0';
        } elseif (in_array($type, ['i', 'd', 'b']) && $value == '') { 
            $value = 0;
        }
        $value = (string) $value;

        // Check if value valid
        $is_valid = match (true) {
            ($type == 'i' && !preg_match("/[0-9]+/", ltrim($value, '-'))) ? true : false => false, 
            ($type == 'd' && !preg_match("/^[0-9]+(\.[0-9]+)?$/", ltrim($value, '-'))) ? true : false => false, 
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

}

