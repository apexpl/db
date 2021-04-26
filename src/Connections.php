<?php
declare(strict_types = 1);

namespace Apex\Db;

use Apex\Db\Exceptions\DbConnectionManagerException;
use redis;


/**
 * Connection handler, used by drivers to obtain read / write connections as needed.
 */
class Connections
{

    // Properties
    private array $connections = [];
    private array $connection_params = [];


    /**
     * Construct
     */
    public function __construct(
        public object $driver, 
        array $connection_params = [], 
        private ?redis $redis = null
    ) { 

        // Add connection
        if (count($connection_params) > 0) { 
            $this->addConnection('write', $connection_params);

        // Check for redis
        } elseif ($redis !== null && $params = $redis->hgetall('config:db.master')) { 
            $this->addConnection('write', $params);

            // Check for read-only connection
            if ($server = $redis->rpoplpush('config:db.readonly', 'config:db.readonly')) { 
                $readonly = $redis->hgetall('config:db.readonly.' . $server); 
                $this->addConnection('read', $readonly);
            }
        }

    }

    /**
     * Add connection
     */
    public function addConnection(string $type, array $params):void
    {
        $this->connection_params[$type] = $params;
    }

    /**
     * Import connection
     */
    public function importConnection(\PDO $pdo, string $type = 'write'):void
    {
        $this->connections[$type] = $pdo;
    }

    /**
     * Get connection
     */
    public function getConnection(string $type)
    {

        // Check type
        if ($type == 'read' && !isset($this->connection_params[$type])) { 
            $type = 'write';
        }

        // Check if connection exists
        if (isset($this->connections[$type])) { 
            return $this->connections[$type];
        }

        // Ensure params exist
        if (!isset($this->connection_params[$type])) { 
            throw new Apex\Db\Exceptions\DbConnectionError("No database connection params exist for the type, $type");
        }
        $params = $this->connection_params[$type];

        // Connect and return
        $this->connections[$type] = $this->driver->connect(
            dbname: $params['dbname'], 
            user: $params['user'], 
            password: $params['password'], 
            host: $params['host'], 
            port: (int) $params['port']
        );

        // Return
        return $this->connections[$type];
    }

    /**
     * Close all connections
     */
    public function closeAll():void
    {

        foreach ($this->connections as $type => $conn) { 
        $this->connections[$type] = null;
        }
        $this->connections = [];
    }

}


