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

        // Go through row
        foreach ($row as $name => $value) { 

            // Get camel case method name
            $word = new UnicodeString('set_' . $name);
            $method = (string) $word->camel();

            // Check for setter method
            if ($obj->hasMethod($method)) { 
                $instance->$method($value);
                continue;
            } elseif (!$prop = $obj->getProperty($name)) { 
                continue;
            }

            // Type cast, if possible
            $type = $prop->getType()?->getName();
            if ($type !== null) { 

                $value = match($type) {
                    'int' => (int) $row[$name], 
                    'float' => (float) $row[$name], 
                    'bool' => (bool) $row[$name], 
                    default => (string) $row[$name]
                };

                // Check type
                if ($type != GetType($value)) { 
                    continue;
                }
            }

            // Inject
            $prop->setAccessible(true);
            $prop->setValue($instance, $value);
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
        if ($method = $obj->getConstructor()) { 
            $constructor_args = self::getConstructorArgs($method, $row);
        } else { 
            $constructor_args = [];
        }

        // Instantiate object
        if (class_exists(Di::class)) { 
            $instance = Di::make($class_name, $row);
        } else { 
            $instance = $obj->NewInstanceArgs($constructor_args);
        }

        // Return
    return $instance;
    }

    /**
     * Get constructor args
     */
    private static function getConstructorArgs(\ReflectionMethod $method, array $row):array
    {

        // Go through parameters
        $args = [];
        $params = $method->getParameters();
        foreach ($params as $param) { 

            // Check name
            $name = $param->getName();
            if (!isset($row[$name])) { 
                continue; 
                }
            $args[$name] = $row[$name];
        }

        // Return
        return $args;
    }

}


