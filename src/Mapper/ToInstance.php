<?php
declare(strict_types = 1);

namespace Apex\Db\Mapper;

use Apex\Container\Di;
use Symfony\Component\String\UnicodeString;

/**
 * Converts an associative array into object via reflection.
 */
class ToInstance
{

    /**
     * Map
     */
    public static function map(string $class_name, array $row):object
    {

        // Get reflection object
        $obj = new \ReflectionClass($class_name);
        $instance = self::createInstance($obj, $row);

        // Map args
        $props = $obj->getProperties();
        $args = self::mapArgs($row, $props);

        // Go through properties
        foreach ($props as $prop) {

            // Check for value
            $name = $prop->getName();
            if (!isset($args[$name])) { 
                continue;
            }

            // Inject value
            $prop->setAccessible(true);
            $prop->setValue($instance, $args[$name]);
        }

        // Return
        return $instance;
    }


    /**
     * Create new instance
     */
    private static function createInstance(\ReflectionClass $obj, array $row):object
    {

        // Check for constructor
        $constructor_args = [];
        if ($method = $obj->getConstructor()) { 
            $params = $method->getParameters();
            $constructor_args = self::mapArgs($row, $params);
        }

        // Instantiate object
        if (class_exists(Di::class)) { 
            $instance = Di::make($obj->getName(), $constructor_args);
        } else { 
            $instance = $obj->NewInstanceArgs($constructor_args);
        }

        // Return
    return $instance;
    }

    /**
     * Map args for constructor / object properties
     */
    private static function mapArgs(array $row, array $props):array
    {

        // Go through props / params
        $args = [];
        foreach ($props as $prop) { 

            // Check name
            $name = $prop->getName();
            if (!isset($row[$name])) { 
                continue;
            }

            // Get property type
            $type = $prop->getType()?->getName();
            if ($type === null) { 
                $args[$name] = $row[$name];
                continue;

            // Check for enum
            } elseif (enum_exists($type)) {
                $args[$name] = is_scalar($row[$name]) ? $type::from($row[$name]) : $row[$name];
                continue;
            }

            // Type cast, if possible
            $value = match($type) {
                'int' => (int) $row[$name], 
                'float' => (float) $row[$name], 
                'bool' => $row[$name] == 1 ? true : false, 
                'DateTime' => $row[$name] === null ? null : new \DateTime($row[$name]), 
                default => (string) $row[$name]
            };

            // Add to args
            $args[$name] = $value;
        }

        // Return
        return $args;
    }


}


