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
     * Set formatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set debugger
     */
    protected function setDebugger(?DebuggerInterface $debugger):void
    {
        $this->debugger = $debugger;
    }

    /**
     * Set db
     */
    protected function setDb(DbInterface $db):void
    {
        $this->db = $db;
    }

    /**
     * Query SQL statement
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
            throw new DbQueryException("Unable to execute SQL statement, $raw_sql <br /><br />Error: " . $e->getMessage());
        }

        // Return
        return $stmt;
    }

    /**
     * Insert row(s)
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

        // Generate value sets
        list($has_id, $value_sets) = [false, []];
        foreach ($arg_sets as $set) { 

            // GO through key-value pairs
            $placeholders = [];
            foreach ($set as $column => $value) { 

                if ($column == 'id' && (int) $value == 0) { 
                    continue; 
                } elseif ($column == 'id') { 
                    $has_id = true;
                }

                // Add to value set
                $placeholders[] = $this->formatter::getPlaceholder($columns[$column]);
                $values[] = $value;
            }
            $value_sets[] = '(' . implode(", ", $placeholders) . ')';
        }

        // Remove id from insert columns, if needed
        if ($has_id === false && $key = array_search('id', $insert_columns)) { 
            array_splice($insert_columns, $key, 1);
        }

        // Finish SQL
        $sql = "INSERT INTO $table_name (" . implode(', ', $insert_columns) . ") VALUES ";
        $sql .= implode(', ', $value_sets);

        // Execute SQL
        $this->db->query($sql, ...$values);
    }

    /**
     * Insert or update
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

        // Check for id = 0, and insert
        if (isset($args[0]['id']) && (int) $args[0]['id'] == 0) { 
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
     * Update
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
     * Delete
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
     * Get single / first row
     */
    public function getRow(string $sql, ...$args):?array
    { 

        // Get first row
        $result = $this->query($sql, ...$args);
        if (!$row = $this->fetchAssoc($result)) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Get single / first row
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
     * Get single row by id#
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
     * Get single object by id#
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
     * Get single column
     */
    public function getColumn(string $sql, ...$args):array
    { 

        // Get column
        $cvalues = [];
        $result = $this->query($sql, ...$args);
        while ($row = $this->fetchArray($result)) { 
            $cvalues[] = $row[0];
        }

        // Return
        return $cvalues;
    }

    /**
     * Get two column hash 
     */
    public function getHash(string $sql, ...$args):array
    { 

        // Get hash
        $vars = [];
        $result = $this->query($sql, ...$args);
        while ($row = $this->fetchArray($result)) { 
            $vars[$row[0]] = $row[1];
        }

        // Return
        return $vars;
    }

    /**
     * Get single field / value
     */
    public function getField(string $sql, ...$args):mixed
    { 

        // Execute SQL query
        $result = $this->query($sql, ...$args);
        if (!$row = $this->fetchArray($result)) { 
            return null;
        }

        // Return
        return $row[0];
    }

    /**
     * Eval
     */
    public function eval(string $sql):mixed
    {
        return $this->get_field("SELECT $sql");
    }

    /**
     * Fetch array
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
     * Fetch assoc
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
     * Fetch object
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
     * Number of rows affected
     */
    public function numRows(\PDOStatement $stmt):int
    { 
        return $stmt->rowCount();
    }

    /**
     * Last insert id
     */
    public function insertId():?int
    {
        $conn = $this->connect_mgr->getConnection('write');
        return (int) $conn->lastInsertId();
    }

    /**
     * Begin transaction
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
     * Commit transaction 
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
     * Rollback transaction
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
     * Execute SQL file
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
     * Force write connection on next query.
     */
    public function forceWrite(bool $always = false):void
    {
        $this->first_write_next = true;
        $this->force_write_always = $always;
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
     * Check if table exists
     */
    public function checkTable(string $table_name):bool
    { 
        $tables = $this->getTableNames();
        return in_array($table_name, $tables);
    }


    /**
     * Determine connection type for SQL query.
     *
     * @return string - Either 'read' or 'write'
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
     * Close all open cursors
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

}


