<?php
declare(strict_types = 1);

namespace Apex\Db\Mapper;


/**
 * Converts an object into an associative array of INSERT / DELETE statements.
 */
class FromInstance
{


    /**
     * Map object to array
     */
    public static function map(object $obj, array $columns = []):array
    {

        // Get properties
        $reflect_obj = new \ReflectionClass($obj::class);
        $props = $reflect_obj->getProperties();

        // GO through properties
        $values = [];
        foreach ($props as $prop) { 

            // Check name
            $name = $prop->getName();
            if (count($columns) > 0 && !in_array($name, array_keys($columns))) { 
                continue;
            }

            // Ensure property initialized
            $prop->setAccessible(true);
            if (!$prop->isInitialized($obj)) { 
                continue;
            }

            // Add to values array
            $value = self::getValue($prop, $obj);
            if ($value === null) { 
                continue;
            }
            $values[$name] = $value;
        }

        // Return
        return $values;
    }

    /**
     * Get value of property
     */
    public static function getValue(\ReflectionProperty | \ReflectionParam $prop, object $obj):mixed
    {

        // Initialize
        $value = $prop->getValue($obj);

        // Check if has type
        if ($prop->hasType() === true && $prop->getType()->isBuiltin() !== true && $value !== null) { 
            $name = $prop->getType()?->getName();
            if ($name == 'DateTime') { 
                $value = $value->format('Y-m-d H:i:s');
            } elseif (enum_exists($name)) {
                $value = $value->value;
            }
        }

        // Return
        return $value;
    }

    /**
     * Get id# of object
     */
    public static function getObjectId(object $obj, string $primary_key = 'id'):?string
    {

        // Get reflection class
        $reflect_obj = new \ReflectionClass($obj::class);

        // Get property
        if (!$prop = $reflect_obj->getProperty($primary_key)) { 
            return null;
        } 

        // Get id#
        $prop->setAccessible(true);
        if (!$id = $prop->getValue($obj)) { 
            return null;
        }

        // Return
        return (string) $id;
    }




}



