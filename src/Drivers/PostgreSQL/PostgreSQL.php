<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\PostgreSQL;

use Apex\Db\Connections;
use Apex\Db\Drivers\{AbstractSQL, SqlQueryResult};
use Apex\Db\Drivers\PostgreSQL\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbPrepareException, DbBindParamsException, DbQueryException, DbBeginTransactionException, DbCommitException, DbRollbackException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;


/**
 * PostgreSQL database driver.
 */
class PostgreSQL extends AbstractSQL implements DbInterface
{

    // Properties
    public Connections $connect_mgr;
    private array $prepared = [];

    /**
     * Constructor
     */
    public function __construct(
        array $params = [], 
        array $readonly_params = [], 
        ?redis $redis = null, 
        private ?DebuggerInterface $debugger = null
    ) { 

        // Set formatter
        $this->setFormatter(Format::class);

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
    public function connect(string $dbname, string $user, string $password = '', string $host = 'localhost', int $port = 5432)
    { 

        // Create connection string
        $conn_string = 'dbname=' . $dbname . ' user=' . $user;
        if ($password != '') { $conn_string .= ' password=' . $password; }
        if ($host != 'localhost') { $conn_string .= ' host=' . $host; }
        if ($port != 5432) { $conn_string .= ' port=' . $port; }

        // Connect
        if (!$conn = @pg_connect($conn_string)) { 
            throw new DbConnectException("Unable to connect to PostgreSQL database using supplied information.  Please double check credentials, and try again.");
        }

        // Set timezone to UTC
        pg_query($conn, "SET TIMEZONE TO 'UTC'");
        pg_query($conn, "SET client_min_messages = 'error'");

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
     * Clear cache
     */
    public function clearCache()
    {
        $this->columns = [];
        $this->tables = [];
    }

    /**
     * Insert record into database
     */
    public function insert(...$args):void
    {
        $this->insertDo($this, ...$args);
    }

    /**
     Insert or update on duplicate key
     */
    public function insertOrUpdate(...$args):void
    { 
        $this->insertOrUpdateDo($this, ...$args);
    }

    /**
     * Update database table
     */
    public function update(...$args):void
    {
        $this->updateDo($this, ...$args);
    }

    /**
     * Delete rows
     */
    public function delete(...$args):void
    { 
        $this->deleteDo($this, ...$args);
    }

    /**
     * Get single / first row
     */
    public function getRow(...$args):array | object | null
    { 
        return $this->getRowDo($this, ...$args);
    }

    /**
     * Get single row by id#
     */
    public function getIdRow(...$args):array | object | null
    { 
        return $this->getIdRowDo($this, ...$args);
    }

    /**
     * Get single column
     */
    public function getColumn(...$args):array
    { 
        return $this->getColumnDo($this, ...$args);
    }

    /**
     * Get two column hash 
     */
    public function getHash(...$args):array
    { 
        return $this->getHashDo($this, ...$args);
    }

    /**
     * Get single field / value
     */
    public function getField(...$args):mixed
    { 
        return $this->getFieldDo($this, ...$args);
    }

    /**
     * Eval
     */
    public function eval(string $sql):mixed
    {
        return $this->get_field("SELECT $sql");
    }

    /**
     * Query SQL statement
     */
    public function query(...$args):SqlQueryResult
    { 

        // Get connection
        $conn_type = preg_match("/^(select|show|describe) /i", $args[0]) ? 'read' : 'write';
        $conn = $this->connect_mgr->getConnection($conn_type);

        //Format SQL
        $map_class = class_exists($args[0]) ? array_shift($args) : '';
        list($sql, $raw_sql, $values) = Format::stmt($conn, $args);

        // Add debug item, if available
        $this->debugger?->addItem('sql', $raw_sql, 3);

        // Check for prepared statement
        $hash = 's' . crc32($sql);
        if ((!isset($this->prepared[$hash])) && (!$this->prepared[$hash] = @pg_prepare($conn, $hash, $sql))) { 
            throw new DbPrepareException("Unable to prepare SQL statement, $sql with error: " . pg_last_error($conn));
        }

        // Execute SQL
        try {
            $result = @pg_execute($conn, $hash, $values);
        } catch (Exception $e) {
            throw new DbQueryException("Unable to execute SQL statement, $raw_sql <br /><br />Error: " . pg_lst_error($conn));
        }

        // Return
        return new SqlQueryResult($this, $result, $map_class);
    }

    /**
     * Fetch array
     */
    public function fetchArray(SqlQueryResult $result, int $position = null):?array
    { 

        // Get row
        if (!$row = pg_fetch_array($result->getResult(), $position, PGSQL_NUM)) {  
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Fetch assoc
     */
    public function fetchAssoc(SqlQueryResult $result, int $position = null):?array
    { 

        // Get row
        if (!$row = pg_fetch_array($result->getResult(), $position, PGSQL_ASSOC)) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Number of rows affected
     */
    public function numRows($result):int
    { 

        // Get result
        if ($result instanceof SqlQueryResult) { 
            $result = $result->getResult();
        } elseif (!$result) { 
            return 0;
        }

        // Get num rows
        if (!$num = pg_num_rows($result)) { 
            $num = 0;
        }
        if ($num == '') { $num = 0; }

        // Return
        return (int) $num;
    }

    /**
     * Last insert id
     */
    public function insertId():?int
    {
    $conn = $this->connect_mgr->getConnection('write');
        return $this->getField("SELECT LASTVAL()");
    }

    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string
    {

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

        // Get function name
        $func_name = "DATE('$from_date') - JUSTIFY_INTERVAL('$length $period')";
        if ($return_datestamp === false) { $func_name = 'EXTRACT(EPOCH FROM ' . $func_name . ')'; }

        // Get and return date
        return $this->getField("SELECT $func_name");
    }

    /**
     * Check if table exists
     */
    public function checkTable(string $table_name):bool
    { 
        $tables = $this->getTableNames();
        return in_array($table_name, $tables) ? true : false;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction():void
    { 
        $this->query("BEGIN");
    }

    /**
     * Commit
     */
    public function commit():void
    {
        $this->query("COMMIT");
    }

    /**
     * Rollback
     */
    public function rollback():void
    {
        $this->query("ROLLBACK");
    }

    /**
     * Execute SQL file
     */
    public function executeSqlFile(string $filename):void
    {
        $this->executeSqlFileDo($this, $filename);
    }


}


