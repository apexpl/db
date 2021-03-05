<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\mySQL;

use Apex\Db\Connections;
use Apex\Db\Drivers\{AbstractSQL, SqlQueryResult};
use Apex\Db\Drivers\mySQL\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbPrepareException, DbBindParamsException, DbQueryException, DbBeginTransactionException, DbCommitException, DbRollbackException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;


/**
 * mySQL database driver.
 */
class mySQL extends AbstractSQL implements DbInterface
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
    public function connect(string $dbname, string $user, string $password = '', string $host = 'localhost', int $port = 3306)
    { 

        // Connect
        if (!$conn = @mysqli_connect($host, $user, $password, $dbname, $port)) { 
            throw new DbConnectException("Unable to connect to mySQL database using supplied information.  Please double check credentials, and try again.");
        }

        // Set timezone to UTC
        mysqli_query($conn, "SET TIME_ZONE = '+0:00'");

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
        list($sql, $raw_sql, $bind_params, $values) = Format::stmt($conn, $args);

        // Add debug item, if available
        $this->debugger?->addItem('sql', $raw_sql, 3);

        // Prepare SQL statement, if needed
        $hash = crc32($sql);
        if ((!isset($this->prepared[$hash])) && (!$this->prepared[$hash] = mysqli_prepare($conn, $sql))) { 
            throw new DbPrepareException("Unable to prepare SQL statement, $sql with error: " . mysqli_error($conn));
        }

        // Bind params
        if (count($values) > 0 && !mysqli_stmt_bind_param($this->prepared[$hash], $bind_params, ...$values)) { 
            throw new DbBindParamsException("Unable to bind parameters '$bind_params' to within SQL statement, $raw_sql .  Error: " . mysqli_error($conn));
        }

        // Execute SQL
        if (!mysqli_stmt_execute($this->prepared[$hash])) { 
            throw new DbQueryException("Unable to execute SQL statement, $raw_sql <br /><br />Error: " . mysqli_error($conn));
        }
        $result = mysqli_stmt_get_result($this->prepared[$hash]);

        // Return
    return new SqlQueryResult($this, $result, $map_class);
    }

    /**
     * Fetch array
     */
    public function fetchArray(SqlQueryResult $result, int $position = null):?array
    { 

        // Seek position, if needed
        if ($position !== null && !mysqli_data_seek($result->getResult(), $position)) { 
            return null;
        }

        // Get row
        if (!$row = mysqli_fetch_array($result->getResult())) { 
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

        // Seek position, if needed
        if ($position !== null && !mysqli_data_seek($result->getResult(), $position)) { 
            return null;
        }

        // Get row
        if (!$row = mysqli_fetch_assoc($result->getResult())) { 
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
        if (!$num = mysqli_num_rows($result)) { 
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
        return (int) mysqli_insert_id($conn);
    }

    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date = '', bool $return_datestamp = true):string
    {

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

        // Get SQL statement
        $func_name = "date_sub('$from_date', interval $length $period)";
        if ($return_datestamp === false) { $func_name = 'unix_timestamp(' . $func_name . ')'; }

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

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Begin transaction 
        if (!mysqli_begin_transaction($conn)) { 
            throw new DbBeginTransactionException("Unable to begin database transaction, error: " . mysqli_error($conn));
        }

    }

    /**
     * Commit transaction 
     */
    public function commit():void
    { 

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Commit transaction
        if (!mysqli_commit($conn)) { 
            throw new DbCommitException("Unable to commit database transaction, error: " . mysqli_error($onn));
        }

    }

    /**
     * Rollback transaction
     */
    public function rollback():void
    { 

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Rollback transaction
        if (!mysqli_rollback($conn)) { 
            throw new DbRollbackException("Unable to rollback database transaction, error: " . mysqli_error($conn));
        }

    }

    /**
     * Execute SQL file
     */
    public function executeSqlFile(string $filename):void
    {
        $this->executeSqlFileDo($this, $filename);
    }

}

