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
    public static function map(object $obj, array $columns):array
    {

        // Get properties
        $reflect_obj = new \ReflectionClass($obj::class);
        $props = $reflect_obj->getProperties();

        // GO through properties
        $values = [];
        foreach ($props as $prop) { 

            // Check name
            $name = $prop->getName();
            if (!in_array($name, array_keys($columns))) { 
                continue;
            }

            // Add to values array
            $prop->setAccessible(true);
            $value = $prop->getValue($obj);
            if ($name == 'id' && (string) $value == '0') { 
                //continue;
            }
            $values[$name] = $value;
        }

        // Return
        return $values;
    }

    /**
     * Get id# of object
     */
    public static function getObjectId(object $obj):?int
    {

        // Get reflection class
        $reflect_obj = new \ReflectionClass($obj::class);

        // Get property
        if (!$prop = $reflect_obj->getProperty('id')) { 
            return null;
        } 

        // Get id#
        $prop->setAccessible(true);
        if (!$id = $prop->getValue($obj)) { 
            return null;
        }

        // Return
        return $id;
    }




}



