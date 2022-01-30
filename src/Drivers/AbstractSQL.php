<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers;

use Apex\Db\Connections;
use Apex\Db\Drivers\SqlParser;
use Apex\Db\Mapper\{FromInstance, ToInstance};
use Apex\Db\Interfaces\DbInterface;
use Apex\Debugger\Interfaces\DebuggerInterface;
use Apex\Db\Exceptions\{DbTableNotExistsException, DbColumnNotExistsException, DbNoInsertDataException, DbObjectNotExistsException, DbPrepareException, DbQueryException, DbBeginTransactionException, DbCommitException, DbRollbackException};
use PDO;


/**
 * Abstract SQL class to handle the similarities between drivers.
 */
class AbstractSQL
{

    // Properties
    public $formatter;
    public DbInterface $db;
    public Connections $connect_mgr;
    public ?DebuggerInterface $debugger;

    // Array properties
    protected array $prepared = [];
    protected array $tables = [];
    protected array $columns = [];
    protected array $primary_keys = [];

    // Force write properties
    protected bool $force_write = false;
    protected bool $force_write_transaction = false;
    protected bool $force_write_always = false;
    protected int $in_transaction = 0;


    /**
     * Set the formatter object.  Used internally.
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set debugger instance.  Used internally.
     */
    protected function setDebugger(?DebuggerInterface $debugger):void
    {
        $this->debugger = $debugger;
    }

    /**
     * Set db instance.  Used internally.
     */
    protected function setDb(DbInterface $db):void
    {
        $this->db = $db;
    }

    /**
     * Execute SQL statement against the database.
     */
    public function query(string $sql, ...$args):\PDOStatement
    { 

        // Get connection
        $conn_type = $this->determineConnType($sql);
        $conn = $this->connect_mgr->getConnection($conn_type);

        //Format SQL
        list($sql, $raw_sql, $values) = $this->formatter::stmt($conn, $sql, $args);
        $hash = 's' . crc32($sql);
        $stmt = $this->prepared[$hash] ?? '';

        // Add debug item, if available
        $this->debugger?->addItem('sql', $raw_sql, 3);

        // Prepare statement, if needed
        if ($stmt == '') { 
            $options = $conn_type == 'read' ? [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL] : [];
            if (str_ends_with($this->db::class, 'SQLite')) { 
                $options = [];
            }

            try {
                $stmt = $conn->prepare($sql, $options);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $this->prepared[$hash] = $stmt;
            } catch (\PDOException $e) { 
                throw new DbPrepareException("Unable to prepare SQL statement, $sql with error: " . $e->getMessage());
            }
        }

        // Execute SQL
        try {
            $stmt->execute($values);
        } catch (\PDOException $e) {
            $this->debugger?->finish();
            throw new DbQueryException("Unable to execute SQL statement with error: " . $e->getMessage() . "<br /><br />SQL: $raw_sql");
        }

        // Return
        return $stmt;
    }

    /**
     * Insert new record(s) into a database table.
     */
    public function insert(string $table_name, ...$args):void
    { 

        // Check if table exists
        if (!$this->db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform insert, as database table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders) = array([], []);
        $columns = $this->db->getColumnNames($table_name, true);

        // Get sets of args, in case of multi-row insert
        $arg_sets = [];
        foreach ($args as $arg) { 
            $arg_sets[] = is_object($arg) ? FromInstance::map($arg, $columns) : $arg;
        }

        // Ensure we have insert data
        if (count($arg_sets) == 0) { 
            throw new DbNoInsertDataException("Unable to perform insert, as no values to insert were specified.");
        }

        // Get insert columns, ensure they exist
        $insert_columns = array_keys($arg_sets[0]);
        foreach ($insert_columns as $col_name) { 
            if (!isset($columns[$col_name])) { 
                throw new DbColumnNotExistsException("Unable to perform insert, as the column '$col_name' does not exist in the table '$table_name'");
            }
        }

        // Get primary key
        $primary_key = $this->getPrimaryKey($table_name);

        // Generate value sets
        list($has_id, $value_sets) = [false, []];
        foreach ($arg_sets as $set) { 

            // GO through key-value pairs
            $placeholders = [];
            foreach ($set as $column => $value) { 

                if ($column == $primary_key && (int) $value == 0) { 
                    continue; 
                } elseif ($column == $primary_key) { 
                    $has_id = true;
                }

                // Add to value set
                $placeholders[] = $this->formatter::getPlaceholder($columns[$column]);
                $values[] = $value;
            }
            $value_sets[] = '(' . implode(", ", $placeholders) . ')';
        }

        // Remove id from insert columns, if needed
        if ($has_id === false && (false !== ($key = array_search($primary_key, $insert_columns)))) {
            array_splice($insert_columns, $key, 1);
        }

        // Finish SQL
        $sql = "INSERT INTO $table_name (" . implode(', ', $insert_columns) . ") VALUES ";
        $sql .= implode(', ', $value_sets);

        // Execute SQL
        $this->db->query($sql, ...$values);
    }

    /**
     * Insert or update a record into a database table.
     */
    public function insertOrUpdate(string $table_name, ...$args):void
    { 

        // Check if table exists
        if (!$this->db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform insert_or_update as table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders, $update_values, $update_placeholders) = array([], [], [], []);
        $columns = $this->db->getColumnNames($table_name, true);

        // Map object, if needed
        if (is_object($args[0])) { 
            $args[0] = FromInstance::map($args[0], $columns);
        }

        // Get primary key
        $primary_key = $this->db->getPrimaryKey($table_name);

        // Check for id = 0, and insert
        if (isset($args[0][$primary_key]) && (int) $args[0][$primary_key] == 0) { 
            $this->insert($table_name, $args[0]);
            return;
        }

        // Generate SQL
        $sql = "INSERT INTO $table_name (" . implode(', ', array_keys($args[0])) . ") VALUES (";
        foreach ($args[0] as $column => $value) { 

            // Check if column exists
            if (!isset($columns[$column])) { 
                throw new DbColumnNotExistsException("Unable to perform insert_or_update, as column '$column' does not exist in the table '$table_name'");
            }

            // Add variables to sql
            $placeholders[] = $this->formatter::getPlaceholder($columns[$column]);
            $update_placeholders[] = $column . ' = ' . $this->formatter::getPlaceholder($columns[$column]);
            $values[] = $value;
            $update_values[] = $value;
        }

        // Finish SQL
        if (str_ends_with($this->db::class, 'PostgreSQL')) { 
            $sql .= implode(", ", $placeholders) . ') ON CONFLICT (id) DO UPDATE SET ' . implode(', ', $update_placeholders);
        } else { 
            $sql .= implode(", ", $placeholders) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $update_placeholders);
        }

        // Execute SQL
        $this->db->query($sql, ...$values, ...$update_values);
    }

    /**
     * Update one or more records within a database table.
     */
    public function update(string $table_name, array | object $updates, ...$args):void
    { 

        // Check if table exists
        if (!$this->db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform update, as table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders) = array([], []);
        $columns = $this->db->getColumnNames($table_name, true);

        // Map object, if needed
        if (is_object($updates)) { 

            // Get primary key
            if (!$primary_key = $this->db->getPrimaryKey($table_name)) { 
                throw new DbObjectNotExistsException("Unable to perform update as table '$table_name' does not have a primary key.");
            }

            // Map object
            $record_id = FromInstance::getObjectId($updates, $primary_key);
            $updates = FromInstance::map($updates, $columns);
            $args = ["$primary_key = %i", $record_id];
        }

        // Generate SQL
        $sql = "UPDATE $table_name SET ";
        foreach ($updates as $column => $value) { 

            // Ensure column exists in table
            if (!isset($columns[$column])) { 
                throw new DbColumnNotExistsException("Unable to perform update as column '$column' does not exist in the table '$table_name'");
            }

            // Set SQL variables
            $placeholders[] = "$column = " . $this->formatter::getPlaceholder($columns[$column]);
            $values[] = $value;
        }

        // Finish SQL
        $sql .= implode(", ", $placeholders);
        if (isset($args[0]) && isset($args[1])) { 
            $sql .= " WHERE " . array_shift($args);
        }

        // Execute  SQL
        $this->db->query($sql, ...$values, ...$args);
    }

    /**
     * Delete one or more records within a database table.
     */
    public function delete(string $table_name, string | object $where_clause, ...$args):void
    { 

        // Check if table exists
        if (!$this->db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform delete as table does not exist, $table_name");
        }
        $sql = "DELETE FROM $table_name";

        // Map from object, if needed
        if (is_object($where_clause)) { 

            // GEt primary key
            if (!$primary_key = $this->db->getPrimaryKey($table_name)) { 
                throw new DbObjectNotExistsException("Unable to perform delete as table '$table_name' does not have a primary key.");
            }

            // Get id# of object
            if (!$id = FromInstance::getObjectId($where_clause, $primary_key)) { 
                throw new DbObjectNotExistsException("Unable to perform delete, as no 'id' variable exists within the provided object.");
            }
            $sql .= " WHERE $primary_key = %i";
            $args = [$id];

        // String based where clause
        } elseif ($where_clause != '') { 
            $sql .= " WHERE $where_clause";
        }

        // Execute SQL
        $this->db->query($sql, ...$args);
    }

    /**
     * Get the first row retrived as an associative array.
     */
    public function getRow(string $sql, ...$args):?array
    { 

        // Get first row
        $result = $this->query($sql, ...$args);
        if (!$row = $this->fetchAssoc($result)) { 
            return null;
        }
        $result->closeCursor();

        // Return
        return $row;
    }

    /**
     * Get the first row retrived mapped to an object.
     */
    public function getObject(string $class_name, string $sql, ...$args):?object
    { 

        // Get first row
        if (!$row = $this->getRow($sql, ...$args)) { 
            return null;
        }

        // Map ot object, and return
        $obj = ToInstance::map($class_name, $row);
        return $obj;
    }

    /**
     * Get a single row by value of the id / primary key column as an associative array.
     */
    public function getIdRow(string $table_name, string | int $id, string $id_col = ''):?array
    { 

        // Check if table exists
        if (!$this->db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform insert, as database table does not exist, $table_name");
        }

        // Get primary key, if needed
        if ($id_col == '' && isset($this->primary_keys[$table_name])) { 
            $id_col = $this->primary_keys[$table_name];
        } elseif ($id_col == '') { 
            $id_col = $this->db->getPrimaryKey($table_name);
            $this->primary_keys[$table_name] = $id_col;
        }

        // Get first row
        if (!$row = $this->getRow("SELECT * FROM $table_name WHERE $id_col = %s ORDER BY $id_col LIMIT 1", $id)) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Get a single row by the value of the table's id / primary key column mapped to an object.
     */
    public function getIdObject(string $class_name, string $table_name, string | int $id, string $id_col = ''):?object
    { 

        // Get row
        if (!$row = $this->getIdRow($table_name, $id, $id_col)) { 
            return null;
        }

        // Map and return
        $obj = ToInstance::map($class_name, $row);
        return $obj;
    }

    /**
     * Get a one dimensional array of a single column within a database table.
     */
    public function getColumn(string $sql, ...$args):array
    { 

        // Get column
        $cvalues = [];
        $result = $this->query($sql, ...$args);
        while ($row = $this->fetchArray($result)) { 
            $cvalues[] = $row[0];
        }
    $result->closeCursor();

        // Return
        return $cvalues;
    }

    /**
     * Get an associative array of two columns within a database table.
     */
    public function getHash(string $sql, ...$args):array
    { 

        // Get hash
        $vars = [];
        $result = $this->query($sql, ...$args);
        while ($row = $this->fetchArray($result)) { 
            $vars[$row[0]] = $row[1];
        }
        $result->closeCursor();

        // Return
        return $vars;
    }

    /**
     * Get value of a single column within the first row retrived.
     */
    public function getField(string $sql, ...$args):mixed
    { 

        // Execute SQL query
        $result = $this->query($sql, ...$args);
        if (!$row = $this->fetchArray($result)) { 
            return null;
        }
        $result->closeCursor();

        // Return
        return $row[0];
    }

    /**
     * Evaluate SQL statement.  Used to include SQL statement within queries (eg. now(), et al)
     */
    public function eval(string $sql):mixed
    {
        return $this->get_field("SELECT $sql");
    }

    /**
     * Get the next row of a query result as a numbered array.
     */
    public function fetchArray(\PDOStatement $stmt, int $position = null):?array
    { 

        // Get row
        if ($position === null) { 
            $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT);
        } else { 
            $row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, $position);
        }

        // Get row
        if (!$row) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Get he next row of a query result as an associative array with column names.
     */
    public function fetchAssoc(\PDOStatement $stmt, int $position = null):?array
    { 

        // Get row
        if ($position === null) { 
            $row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
        } else { 
            $row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $position);
        }

        // Get row
        if (!$row) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Get the next row of a query result mapped to an object.
     */
    public function fetchObject(\PDOStatement $stmt, string $class_name, int $position = null):?object
    {

        // Get row
        if (!$row = $this->db->fetchAssoc($stmt, $position)) { 
            return null;
        }

        // Map to object, and return
        return ToInstance($class_name, $row);
    }

    /**
     * Get the number of rows affected by a query result.
     */
    public function numRows(\PDOStatement $stmt):int
    { 
        return $stmt->rowCount();
    }

    /**
     * Get value of the last auto incremented column generated during an insert.
     */
    public function insertId():?int
    {
        $conn = $this->connect_mgr->getConnection('write');
        return (int) $conn->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(bool $force_write = false):void
    {

        // Return if already in transaction
        if ($this->in_transaction > 0) { 
            $this->in_transaction++;
            return;
        }

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Begin transaction 
        try {
            $conn->beginTransaction();
        } catch (\PDOException $e) { 
            throw new DbBeginTransactionException("Unable to begin database transaction, error: " . $e->getMessage());
        }

        // Set force write
        $this->force_write_transaction = $force_write;
        $this->in_transaction++;
    }

    /**
     * Commit a database transaction.
     */
    public function commit():void
    { 

        // Check if in transaction
        if ($this->in_transaction > 1) { 
            $this->in_transaction--;
            return;
        } elseif ($this->in_transaction == 0) { 
            return;
        }

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Commit transaction
        try {
            $conn->commit();
        } catch (\PDOException $e) { 
            throw new DbCommitException("Unable to commit database transaction, error: " . $e->getMessage());
        }
        $this->force_write_transaction = false;
    $this->in_transaction--;

    }

    /**
     * Rollback a database transaction.
     */
    public function rollback():void
    { 

        // Check if in transaction
        if ($this->in_transaction > 1) { 
            $this->in_transaction--;
            return;
        } elseif ($this->in_transaction == 0) { 
            return;
        }

        // Get connection
        $conn = $this->connect_mgr->getConnection('write');

        // Rollback transaction
        try {
            $conn->rollback();
        } catch (\PDOException $e) { 
            throw new DbRollbackException("Unable to rollback database transaction, error: " . $e->getMessage());
        }
        $this->force_write_transaction = false;
        $this->in_transaction--;

    }

    /**
     * Execute all SQL code within a file on the on the local machine.
     */
    public function executeSqlFile(string $filename):void
    {

        // Execute SQL file
        $sql_lines = SqlParser::parse(file_get_contents($filename));
        foreach ($sql_lines as $sql) { 
            $this->db->query($sql);
        }
    }

    /**
     * Force all SQL queries to the master / write database connection, and never connect to the read-only connection.
     */
    public function forceWrite(bool $always = false):void
    {
        $this->first_write_next = true;
        $this->force_write_always = $always;
    }

    /**
     * Upon retrieving table or column names, the results will be cached for future call to the functin during the request.  Use this to clear that cache.
     */
    public function clearCache()
    {
        $this->columns = [];
        $this->tables = [];
    }

    /**
     * Check whether or not a database table exists.
     */
    public function checkTable(string $table_name):bool
    { 
        $tables = $this->getTableNames();
        return in_array($table_name, $tables);
    }


    /**
     * Determine if a query is read-only or requires write access.
     *
     */
    protected function determineConnType(string $sql):string
    {

        // Check force write properties
        if ($this->force_write === true || $this->force_write_always === true || $this->force_write_transaction === true) { 
            $conn_type = 'write';
            if ($this->force_write_always === false && $this->force_write_transaction === false) { 
                $this->force_write_next = false;
            }

        // Check for read statement
        } elseif (preg_match("/^(select|show|describe) /i", $sql)) { 
            $conn_type = 'read';
        } else { 
            $conn_type = 'write';
        }

        // Return
        return $conn_type;
    }

    /**
     * Close all currently open cursors within the database.
     */
    public function closeCursors():void
    {

        // Close cursors
        foreach ($this->prepared as $stmt) { 
            if ($stmt instanceof \PDOStatement) { 
                $stmt->closeCursor();
            }
        }

    }

    /**
     * Drop database table including any child tables with foreign key constraints to the table.
     */
    public function dropTable(string $table_name):void
    {

        // Check table exists
        $this->clearCache();
        if (!$this->checkTable($table_name)) {
            return;
        }

        // Get referenced foreign keys
        $keys = $this->db->getReferencedForeignKeys($table_name);

        // Go through foreign keys
        foreach ($keys as $alias => $vars) {
            $this->dropTable($vars['ref_table']);
        }

        // Drop table
        $this->query("DROP TABLE $table_name");
    }

    /**
     * Drop all tables within the database in proper order so as to not receive foreign key constraint errors.
     */
    public function dropAllTables():void
    {

        // Drop all views
        $views = $this->db->getViewNames();
        foreach ($views as $view) {
            $this->query("DROP VIEW $view");
        }

        // Go through all tables
        $tables = $this->db->getTableNames();
        foreach ($tables as $table_name) {
            $this->dropTable($table_name);
            $this->clearCache();
        }

    }

}


