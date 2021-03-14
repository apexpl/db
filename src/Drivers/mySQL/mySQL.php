<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\mySQL;

use Apex\Db\Connections;
use Apex\Db\Drivers\AbstractSQL;
use Apex\Db\Drivers\mySQL\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbInvalidArgumentException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;
use PDO;


/**
 * mySQL database driver.
 */
class mySQL extends AbstractSQL implements DbInterface
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
        private string $charset = 'utf8mb4',  
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
    public function connect(string $dbname, string $user, string $password = '', string $host = 'localhost', int $port = 3306):PDO
    { 

        // Connection string
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=' . $this->charset;

        // Connect
        try {
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            if ($this->onconnect_fail !== null && is_callable($this->onconnect_fail)) { 
                call_user_func($this->onconnect_fail);
            }
            throw new DbConnectException("Unable to connect to database using supplied information.  Error: " . $e->getMessage());
        }

        // Set timezone to UTC
    try {
            $conn->query("SET TIME_ZONE = '$this->tz_offset'");
        } catch (\PDOException $e) { 
            throw new DbConnectException("Unable to set timezone offset to $this->tz_offset, error: " . $e->getMessage());
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
        $result = $this->query("SHOW TABLES");
        while ($row = $this->fetchArray($result)) { 
            $this->tables[] = $row[0];
        }

        // Return
        return $this->tables;
    }

    /**
     * Get columns of table.
     */
    public function getColumnNames(string $table_name, bool $include_types = false):array
    { 

        // Check if we already have columns
        if (isset($this->columns[$table_name]) && is_array($this->columns[$table_name]) && count($this->columns[$table_name]) > 0) { 
            return $include_types === true ? $this->columns[$table_name] : array_keys($this->columns[$table_name]);
        }

        // Get column names
        $this->columns[$table_name] = [];
        $result = $this->query("DESCRIBE $table_name");
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
        return $this->numRows($stmt);
    }

    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date = '', bool $return_datestamp = true):string
    {

        // Check for valid period
        if (!in_array($period, ['second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year'])) { 
            throw new DbInvalidArgumentException("Invalid time period specified, $period.  Supported values are:  second, minute, hour, day, week, month, quarter, year");
        }

        // Get current date, if needed
        if ($from_date == '') { 
            $from_date = $db->getField("SELECT now()");
        }

        // Get SQL statement
        $func_name = "date_add('$from_date', interval $length $period)";
        if ($return_datestamp === false) { $func_name = 'unix_timestamp(' . $func_name . ')'; }

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

        // Get SQL statement
        $func_name = "date_sub('$from_date', interval $length $period)";
        if ($return_datestamp === false) { $func_name = 'unix_timestamp(' . $func_name . ')'; }

        // Get and return date
        return $this->getField("SELECT $func_name");
    }

}

