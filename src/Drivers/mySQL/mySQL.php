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
     * Instantiate a new database connection object.
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
     * Used internally to lazy connect to the database upon first SQL statement being performed.
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

        // Set connection info
        $this->setConnectionInfo($dbname, $user, $password, $host, $port);

        // Return
        return $conn;
    }

    /**
     * Get a list of all table names within the database.
     */
    public function getTableNames():array
    { 

        // Check if tables already retrieved
    //if (count($this->tables) > 0) { 
            //return $this->tables;
        //}

        // Get tables
        $result = $this->query("SHOW TABLES");
        while ($row = $this->fetchArray($result)) { 
            $this->tables[] = $row[0];
        }

        // Return
        return $this->tables;
    }

    /**
     * Get view names
     */
    public function getViewNames():array
    {

        // Go through rows
        $views = [];
        $result = $this->query("SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW';");
        while ($row = $this->fetchArray($result)) {
            $views[] = $row[0];
        }

        // Return
        return $views;
    }

    /**
     * Get all column names within a database table, optionally with column type.
     */
    public function getColumnNames(string $table_name, bool $include_types = false):array
    { 

        // Check if we already have columns
        //if (isset($this->columns[$table_name]) && is_array($this->columns[$table_name]) && count($this->columns[$table_name]) > 0) { 
            //return $include_types === true ? $this->columns[$table_name] : array_keys($this->columns[$table_name]);
        //}

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
     * Get details on all columns within a database table including type, whether it's primary or unique, allows null, et al.
     */
    public function getColumnDetails(string $table_name):array
    { 

        // Get column names
        $details = [];
        $result = $this->query("DESCRIBE $table_name");
        while ($row = $this->fetchArray($result)) { 

            $details[$row[0]] = [
                'type' => $row[1] == 'tinyint(1)' ? 'boolean' : $row[1],
                'length' => preg_match("/\((.+?)\)/", $row[1], $m) ? $m[1] : '',
                'is_primary' => strtolower($row[3]) == 'pri' ? true : false,
                'is_unique' => strtolower($row[3]) == 'uni' ? true : false,
                'is_auto_increment' => strtolower($row[5]) == 'auto_increment' ? true : false,
                'key' => strtolower($row[3]), 
                'allow_null' => strtolower($row[2]) == 'yes' ? true : false, 
                'default' => $row[4]
            ];
        }

        // Return
        return $details;
    }

    /**
     * Get all foreign key constraints of a database table.
     */
    public function getForeignKeys(string $table_name):array
    {

        // Initialize
        $foreign_keys = [];
        $columns = $this->getColumnDetails($table_name);

        // Go through indexes
        $result = $this->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s", $table_name);
        while ($row = $this->fetchArray($result)) { 

            // Get column info
            $col = $columns[$row[1]];
            $ref_columns = $this->getColumnDetails($row[3]);
            $ref = $ref_columns[$row[4]];

            // Get type
            $type = $col['is_primary'] === true || $col['is_unique'] === true ? 'one_to_' : 'many_to_';
            $type .= ($ref['is_primary'] === true || $ref['is_unique'] === true ? 'one' : 'many');

            // Add to keys
            $foreign_keys[$row[1]] = [
                'table' => $row[3],
                'column' => $row[4],
                'type' => $type
            ]; 
        }

        // Return
        return $foreign_keys;
    }

    /**
     * Get the foreign key constraints that other database tables have on a given database table.
     */
    public function getReferencedForeignKeys(string $table_name):array
    {

        // Initialize
        $foreign_keys = [];
        $ref_columns = $this->getColumnDetails($table_name);

        // Go through indexes
        $result = $this->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = %s", $table_name);
        while ($row = $this->fetchArray($result)) { 

            // Get column info
            $columns = $this->getColumnDetails($row[0]);
            $col = $columns[$row[1]];
            $ref = $ref_columns[$row[4]];

            // Get type
            $type = $col['is_primary'] === true || $col['is_unique'] === true ? 'many_to_' : 'one_to_';
            $type .= ($ref['is_primary'] === true || $ref['is_unique'] === true ? 'many' : 'one');

            // Add to keys
            $alias = $row[0] . '.' . $row[1];
            $foreign_keys[$alias] = [
                'table' => $row[3],
                'column' => $row[4],
                'type' => $type,
                'ref_table' => $row[0],
                'ref_column' => $row[1]
            ]; 
        }

        // Return
        return $foreign_keys;
    }

    /**
     * Get the size of the database.
     */
    public function getDatabaseSize():float
    {
        $size = $this->getField("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = DATABASE()");
        return (float) $size;
    }

    /**
     * Delete all rows in a database table, and reset auto increment column.
     */
    public function truncate(string $table_name):void
    {
        $this->query("TRUNCATE $table_name");
        $this->query("ALTER TABLE $table_name AUTO_INCREMENT = 1;");
    }

    /**
     * Get the name of the primary key column of a database table.
     */
    public function getPrimaryKey(string $table_name):?string
    {

        // Get primary index
        if (!$row = $this->getRow("SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'")) { 
            return null;
        }

        // Return
        return $row['Column_name'] ?? null;
    }

    /**
     * Get total number of rows container within result of a select statement.
     */
    public function getSelectCount(\PDOStatement $stmt):int
    {
        return $this->numRows($stmt);
    }

    /**
     * Add a time period and interval to another time.
     */
    public function addTime(string $period, int $length, string $from_date = '', bool $return_datestamp = true):string
    {

        // Check for valid period
        if (!in_array($period, ['second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year'])) { 
            throw new DbInvalidArgumentException("Invalid time period specified, $period.  Supported values are:  second, minute, hour, day, week, month, quarter, year");
        }

        // Get current date, if needed
        if ($from_date == '') { 
            $from_date = $this->getField("SELECT now()");
        }

        // Get SQL statement
        $func_name = "date_add('$from_date', interval $length $period)";
        if ($return_datestamp === false) { $func_name = 'unix_timestamp(' . $func_name . ')'; }

        // Get and return date
        return $this->getField("SELECT $func_name");
    }

    /**
     * Subtract a time period and interval from another time.
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

