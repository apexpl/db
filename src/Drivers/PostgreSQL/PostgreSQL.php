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
     * Get view names
     */
    public function getViewNames():array
    {

        // Get views
        $views = [];
        $result = $this->query("SELECT table_name FROM INFORMATION_SCHEMA.views WHERE table_schema = 'public'");
        while ($row = $this->fetchArray($result)) {
            $views[] = $row[0];
        }

        // Return
        return $views;
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
     * Get column defaults
     */
    public function getColumnDetails(string $table_name):array
    {

        // Initialize
        $primary_col = $this->getPrimaryKey($table_name);
        $unique_columns = $this->getColumn("SELECT pg_attribute.attname FROM pg_index, pg_class, pg_attribute, pg_namespace WHERE pg_class.oid = %s::regclass AND indrelid = pg_class.oid AND nspname = 'public' AND pg_class.relnamespace = pg_namespace.oid AND pg_attribute.attrelid = pg_class.oid AND pg_attribute.attnum = any(pg_index.indkey) AND indisunique", $table_name);
        $results = [];

        // Go through table columns
        $result = $this->query("SELECT column_name, data_type, column_default, is_nullable, character_maximum_length, numeric_precision, numeric_precision_radix FROM information_schema.columns WHERE table_name = '$table_name'");
        while ($row = $this->fetchAssoc($result)) { 

            // Set variables
            $col_name = $row['column_name'];
            $key = match (true) { 
                $primary_col == $col_name ? true : false => 'pri',
                in_array($col_name, $unique_columns) ? true : false => 'uni',
                default => ''
            };

            // Get column type
            $type = match($row['data_type']) {
                'character varying' => 'varchar',
                'integer' => 'int',
                'numeric' => 'decimal',
                default => $row['data_type']
            };

            // Check for decimal type
            if ($type == 'decimal') {
                $decimals = ($row['numeric_precision'] - $row['numeric_precision_radix']);
                $row['character_maximum_length'] = $row['numeric_precision'] . ',' . $decimals;
                $type .= '(' . $row['character_maximum_length'] . ')';
            } elseif ($type == 'varchar') { 
                $type .= '(' . $row['character_maximum_length'] . ')';
            }

        // Get default / is_auto_incremnet
            $is_auto_increment = false;
            $default = $row['column_default'] === null ? '' : $row['column_default'];
            if (preg_match("/^nextval/", $default)) { 
                $is_auto_increment = true;
                $default = '';
            }
            $default = trim(preg_replace("/::.*$/", "", $default), "'");

            // Default for boolean
            if ($type == 'boolean') { 
                $default = $default == 'true' ? true : false;
            }

            // Set in results
            $results[$col_name] = [
                'type' => $type,
                'length' => $row['character_maximum_length'],
                'is_primary' => $primary_col == $col_name ? true : false,
                'is_unique' => in_array($col_name, $unique_columns),
                'is_auto_increment' => $is_auto_increment,
                'key' => $key,
                'allow_null' => strtolower($row['is_nullable']) == 'yes' ? true : false, 
                'default' => $default
            ];
        }

        // Return
        return $results;
    }

    /**
     * Get foreign keys
     */
    public function getForeignKeys(string $table_name):array
    {

        // Initialize
        $foreign_keys = [];
        $columns = $this->getColumnDetails($table_name);

        // Get keys
        $result = $this->query("SELECT tc.table_schema, tc.constraint_name, tc.table_name, kcu.column_name, ccu.table_schema AS foreign_table_schema, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = %s", $table_name);
        while ($row = $this->fetchAssoc($result)) {

            // Get column info
            $col = $columns[$row['column_name']];
            $ref_columns = $this->getColumnDetails($row['foreign_table_name']);
            $ref = $ref_columns[$row['foreign_column_name']];

            // Get type
            $type = $col['is_primary'] === true || $col['is_unique'] === true ? 'one_to_' : 'many_to_';
            $type .= ($ref['is_primary'] === true || $ref['is_unique'] === true ? 'one' : 'many');

            // Add to keys
            $foreign_keys[$row['column_name']] = [
                'table' => $row['foreign_table_name'],
                'column' => $row['foreign_column_name'],
                'type' => $type
            ]; 


        }

        // Return
        return $foreign_keys;
    }

    /**
     * Get referenced foreign keys
     */
    public function getReferencedForeignKeys(string $table_name):array
    {

        // Initialize
        $foreign_keys = [];
        $ref_columns = $this->getColumnDetails($table_name);

        // Go through keys
        $result = $this->query("SELECT tc.table_schema, tc.constraint_name, tc.table_name, kcu.column_name, ccu.table_schema AS foreign_table_schema, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema WHERE tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name = %s", $table_name);
        while ($row = $this->fetchAssoc($result)) { 

            // Get column info
            $columns = $this->getColumnDetails($row['table_name']);
            $col = $columns[$row['column_name']];
            $ref = $ref_columns[$row['foreign_column_name']];

            // Get type
            $type = $col['is_primary'] === true || $col['is_unique'] === true ? 'many_to_' : 'one_to_';
            $type .= ($ref['is_primary'] === true || $ref['is_unique'] === true ? 'many' : 'one');

            // Add to keys
            $alias = $row['table_name'] . '.' . $row['column_name'];
            $foreign_keys[$alias] = [
                'table' => $row['foreign_table_name'],
                'column' => $row['foreign_column_name'],
                'type' => $type,
                'ref_table' => $row['table_name'],
                'ref_column' => $row['column_name']
            ]; 

        }

        // Return
        return $foreign_keys;
    }



    /**
     * Get database size in mb
     */
    public function getDatabaseSize():float
    {
        $dbname = $this->getField("SELECT current_database()");
        $size = $this->getField("SELECT (pg_database_size('$dbname') / 1024)");
        return (float) sprintf("%.2f", ($size / 1024));
    }

    /**
     * Reset auto increment column
     */
    public function truncate(string $table_name):void
    {

        // Delete from table
        $this->query("TRUNCATE $table_name");

        // Reset sequence
        $primary_key = $this->getPrimaryKey($table_name);
        $seq_name = $table_name . '_' . $primary_key . '_seq'; 
        $this->query("ALTER SEQUENCE $seq_name RESTART WITH 1;");
    }

    /**
     * Get primary key of table
     */
    public function getPrimaryKey(string $table_name):?string
    {

        // Get primary index
        if (!$key = $this->getField("SELECT pg_attribute.attname FROM pg_index, pg_class, pg_attribute, pg_namespace WHERE pg_class.oid = %s::regclass AND indrelid = pg_class.oid AND nspname = 'public' AND pg_class.relnamespace = pg_namespace.oid AND pg_attribute.attrelid = pg_class.oid AND pg_attribute.attnum = any(pg_index.indkey) AND indisprimary", $table_name)) { 
            return null;
        }

        // Return
        return $key;
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

