<?php
declare(strict_types = 1);

namespace Apex\Db;

use Apex\Db\Drivers\mySQL\Format;
use Apex\Db\Exceptions\DbConnectionManagerException;
use redis;


/**
 * Connection manager
 */
class ConnectionManager
{


    /**
     * Constructor
     */
    public function __construct(
        private redis $redis
    ) { 

    }

    /**
     * Add database
     */
    public function addDatabase(string $type, array $params, string $alias = ''):void
    {

        // Check driver
        if (!in_array($type, ['write','read'])) { 
            throw new DbConnectionManagerException("Invalid type of database, $type.  Must be either 'write' or 'read'");
        } elseif ($type == 'read' && ($alias == '' || preg_match("/[\W\s]/", $alias))) { 
            throw new DbConnectionManagerException("When adding a read-only database connection you must specify an alpha-numeric alias with no spaces or special characters.");
        }
        $alias = strtolower($alias);

        // Format params through mySQL
        $params = Format::validateParams($params);

        // Save to redis
        if ($type == 'write') { 
            $this->redis->hmset('config:db.master', $params);
        } else { 

            $aliases = $this->redis->lrange('config:db.readonly', 0, -1);
            if (!in_array($alias, $aliases)) { 
                $this->redis->lpush('config:db.readonly', $alias);
            }
            $this->redis->hmset('config:db.readonly.' . $alias, $params);
        }

    }

    /**
     * Delete database
     */
    public function deleteDatabase(string $alias):void
    {

        // Check if exists
        if (!$this->redis->exists('config:db.readonly.' . $alias)) { 
            throw new DbConnectionManagerException("No read-only database exists at $alias");
        }

        // Delete
        $this->redis->lrem('config:db.readonly', $alias, 1);
        $this->redis->del('config:db.readonly.' . $alias);
    }

    /**
     * Get a database
     */
    public function getDatabase(string $type, string $alias = ''):?array
    {

        // Check driver
        if (!in_array($type, ['write','read'])) { 
            throw new DbConnectionManagerException("Invalid type of database, $type.  Must be either 'write' or 'read'");
        }

        // Get database
        if ($type == 'write') { 
            $dbinfo = $this->redis->hgetall('config:db.master');
        } else { 
            $dbinfo = $this->redis->hgetall('config:db.readonly.' . $alias);
        }

        // Check for null result
        if (!$dbinfo) { 
            throw new DbConnectionManagerException("No database connection info exists at type $type with index $alias");
        }

        // Return
        return $dbinfo;
    }

    /**
     * List all read-only databases
     */
    public function listReadonly():array
    {

        // Get all dbs
        $aliases = $this->redis->lrange('config:db.readonly', 0, -1);

        // Go through dbs
        $dbs = [];
        foreach ($aliases as $alias) { 
            $vars = $this->redis->hgetall('config:db.readonly.' . $alias);
            $dbs[$alias] = $vars['user'] . '@' . $vars['host'] . ':' . $vars['port'] . '/' . $vars['dbname'];
        }

        // Return
        return $dbs;
    }

    /**
     * Delete all
     */
    public function deleteAll():void
    {
        $keys = $this->redis->keys('config:db.*');
        foreach ($keys as $key) { 
            $this->redis->del($key);
        }
    }

}



