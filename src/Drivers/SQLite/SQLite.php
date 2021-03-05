<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\SQLite;

use Apex\Db\Connections;
use Apex\Db\Drivers\{AbstractSQL, SqlQueryResult};
use Apex\Db\Drivers\SQLite\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbPrepareException, DbBindParamsException, DbQueryException, DbBeginTransactionException, DbCommitException, DbRollbackException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use Sqlite3;
use redis;


/**
 * SQLite database driver.
 */
class SQLite extends AbstractSQL implements DbInterface
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
    public function connect(string $dbname = '', string $user = '', string $password = '', string $host = '', int $port = 0)
    { 

        // Connect
        if (!$conn = new Sqlite3($dbname)) { 
            throw new DbConnectException("Unable to connect to SQLite database using supplied information.  Please double check credentials, and try again.");
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
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'");
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
        $result = $this->query("PRAGMA table_info($table_name)");
        while ($row = $this->fetchArray($result)) { 
            $this->columns[$table_name][$row[1]] = $row[2];
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
        $args[] = 'rowid'; 
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

        // Prepare SQL statement, if needed
        $hash = crc32($sql);
        if ((!isset($this->prepared[$hash])) && (!$this->prepared[$hash] = $conn->prepare($sql))) { 
            throw new DbPrepareException("Unable to prepare SQL statement, $sql with error: " . $conn->lastErrorMsg());
        }
        $stmt =& $this->prepared[$hash];

        // Bind params
        foreach ($values as $param => $vars) { 
            if (!$stmt->bindValue($param, $vars[0], $vars[1])) {
                throw new DbBindParamsException("Unable to bind parameters '$bind_params' to within SQL statement, $raw_sql .  Error: " . $conn->lastErrorMsg());
            }
        }

        // Execute SQL
        if (!$result = $stmt->execute()) { 
            throw new DbQueryException("Unable to execute SQL statement, $raw_sql <br /><br />Error: " . $conn->lastErrorMsg());
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
        if (!$row = $result->getResult()->fetchArray(SQLITE3_NUM)) { 
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
        if (!$row = $result->getResult()->fetchArray(SQLITE3_ASSOC)) { 
            return null;
        }

        // Check for 'rowid'
        if (isset($row['rowid'])) { 
            $row['id'] = $row['rowid'];
            unset($row['rowid']);
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
        }

        // Check if a query result
        if ($result?->numColumns() == 0) {
            $conn = $this->connect_mgr->getConnection('write');
            return $conn->changes() ?? 0;
        }

        // Get total rows
        $total = 0;
        while ($row = $result->fetchArray()) { 
            $total++;
        }
        $result->reset();

        // Return
        return $total;
    }

    /**
     * Last insert id
     */
    public function insertId():?int
    {
        $conn = $this->connect_mgr->getConnection('write');
        return $conn->lastInsertRowID() ?? 0;
    }

    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string
    {

        // Get SQL statement
        $func_name = "datetime('$from_date', '$length $period')";
        $date = $this->getField("SELECT $func_name");

        // Get timestamp, if needed
        if ($return_datestamp === false) { 
            list($date, $time) = explode(' ', $date, 2);
            $d = explode('-', $date);
            $t = explode(':', $time);
            $date = mktime((int) $t[0], (int) $t[1], (int) $t[2], (int) $d[1], (int) $d[2], (int) $d[0]);
        }

        // Return
        return $date;
    }

    /**
     * Subtract time
     */
    public function subtractTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string
    {

        // Get SQL statement
        $func_name = "datetime('$from_date', '- " . $length . " $period')";
        $date = $this->getField("SELECT $func_name");

        // Get timestamp, if needed
        if ($return_datestamp === false) { 
            list($date, $time) = explode(' ', $date, 2);
            $d = explode('-', $date);
            $t = explode(':', $time);
            $date = mktime((int) $t[0], (int) $t[1], (int) $t[2], (int) $d[1], (int) $d[2], (int) $d[0]);
        }

        // Return
        return $date;
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
    public function beginTransaction():void { } 
    public function commit():void { }
    public function rollback():void { }

    /**
     * Execute SQL file
     */
    public function executeSqlFile(string $filename):void
    {
        $this->executeSqlFileDo($this, $filename);
    }


}


