<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\PostgreSQL;

use Apex\Db\Connections;
use Apex\Db\Drivers\AbstractSQL;
use Apex\Db\Drivers\PostgreSQL\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbInvalidArgumentException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;
use PDO;


/**
 * PostgreSQL database driver.
 */
class PostgreSQL extends AbstractSQL implements DbInterface
{


    /**
     * Constructor
     */
    public function __construct(
        array $params = [], 
        array $readonly_params = [],
        ?redis $redis = null,  
        ?DebuggerInterface $debugger = null, 
        private $onconnect_fail = null,
        private string $charset = 'utf8',  
        private string $tz_offset = '+0:00'
    ) {

        // Set formatter
        $this->setFormatter(Format::class);
        $this->setDebugger($debugger);
        $this->setDb($this);

        // Validate params 
        if (count($params) > 0) { 
            $params = Format::validateParams($params);
        }

        // Start connection manager
        $this->connect_mgr = new Connections($this, $params, $redis);

        // Add read-only params, if we have them
        if (count($readonly_params) > 0) { 
            $readonly_params = Format::validateParams($readonly_params);
            $this->connect_mgr->addConnection('read', $readonly_params);
        }

    } 

    /**
     * Connect to database
     */
    public function connect(string $dbname, string $user, string $password = '', string $host = 'localhost', int $port = 5432):PDO
    { 

        // Connection string
        $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname;

        // Connect
        try {
            $conn = new PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            if ($this->onconnect_fail !== null && is_callable($this->onconnect_fail)) { 
                call_user_func($this->onconnect_fail);
            }
            throw new DbConnectException("Unable to connect to database using supplied information.  Error: " . $e->getMessage());
        }
        $conn->query("SET client_min_messages = 'error'");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set timezone to UTC
        try {
            $conn->query("SET TIMEZONE TO '$this->tz_offset'");
        } catch (\PDOException $e) { 
            throw new DbConnectException("Unable to set timezone offset to $this->tz_offset, error: " . $e->getMessage());
        }

        // Set charset
        try {
            $conn->query("SET CLIENT_ENCODING TO '$this->charset';");
        } catch (\PDOException $e) { 
            throw new DbConnectException("Unable to set charset to $this->charset, error: " . $e->getMessage());
        }

        // Return
        return $conn;
    }

    /**
     * Get table names
     */
    public function getTableNames():array
    { 

        // Check if tables already retrieved
    if (count($this->tables) > 0) { 
            return $this->tables;
        }

        // Get tables
        $result = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        while ($row = $this->fetchArray($result)) { 
            $this->tables[] = $row[0];
        }

        // Return
        return $this->tables;
    }

    /**
     * Get column names of table
     */
    public function getColumnNames(string $table_name, bool $include_types = false):array
    { 

        // Check IF COLUMNS ALREADY GOTTEN
        if (isset($this->columns[$table_name]) && is_array($this->columns[$table_name]) && count($this->columns[$table_name]) > 0) { 
            return $include_types === true ? $this->columns[$table_name] : array_keys($this->columns[$table_name]);
        }

        // Get column names
        $this->columns[$table_name] = [];
        $result = $this->query("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = '$table_name'");
        while ($row = $this->fetchArray($result)) { 
            $this->columns[$table_name][$row[0]] = $row[1];
        }

        // Return
        return $include_types === true ? $this->columns[$table_name] : array_keys($this->columns[$table_name]);
    }

    /**
     * Get number of rows in select result
     */
    public function getSelectCount(\PDOStatement $stmt):int
    {
        return count($stmt->fetchAll());
    }

    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string
    {

        // Check for valid period
        if (!in_array($period, ['second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year'])) { 
            throw new DbInvalidArgumentException("Invalid time period specified, $period.  Supported values are:  second, minute, hour, day, week, month, quarter, year");
        }

        // Get function name
        $func_name = "DATE('$from_date') + JUSTIFY_INTERVAL('$length $period')";
        if ($return_datestamp === false) { $func_name = 'EXTRACT(EPOCH FROM ' . $func_name . ')'; }

        // Get and return date
        return $this->getField("SELECT $func_name");
    }

    /**
     * Subtract time
     */
    public function subtractTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string
    {

        // Check for valid period
        if (!in_array($period, ['second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year'])) { 
            throw new DbInvalidArgumentException("Invalid time period specified, $period.  Supported values are:  second, minute, hour, day, week, month, quarter, year");
        }

        // Get function name
        $func_name = "DATE('$from_date') - JUSTIFY_INTERVAL('$length $period')";
        if ($return_datestamp === false) { $func_name = 'EXTRACT(EPOCH FROM ' . $func_name . ')'; }

        // Get and return date
        return $this->getField("SELECT $func_name");
    }

}

