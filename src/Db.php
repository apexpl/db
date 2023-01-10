<?php
declare(strict_types = 1);

namespace Apex\Db;

use Apex\Db\Interfaces\{DbInterface, FlatFileDbInterface};
use Apex\Db\Exceptions\DbStaticException;


/**
 * Static wrapper for database drivers, allowing you to access all database methods statically instead of 
 * Passing a database connection object to every class and method.
 */
class Db
{

    public static $instance = null;

    /**
     * Initialize the wrapper.  Simply pass any connection object into it, and begin access all methods statically.
     */
    public static function init(DbInterface | FlatFileDbInterface $db):void
    {
        self::$instance = $db;
    }

    /**
     * Calls a method of the instance.
     */
    public static function __callstatic($method, $params) 
    {

        // Ensure we have an instance defined
        if (!self::$instance) { 
            throw new DbStaticException("You have not initialized the Db wrapper yet.  Please first call Db::init(\$db) passing any database object, then begin accessing methods statically.");
        }

        // Ensure method exists
        if (!method_exists(self::$instance, $method)) { 
            throw new DbStaticException("The method '$method' does not exist within the database driver class.");
        }

        // Call method, and return 
        return self::$instance->$method(...$params);
    }

}

