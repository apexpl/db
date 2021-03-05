<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers;

use Apex\Db\Drivers\SqlParser;
use Apex\Db\Mapper\{FromInstance, ToInstance};
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbTableNotExistsException, DbColumnNotExistsException, DbNoInsertDataException, DbObjectNotExistsException};


/**
 * Abstract SQL class to handle the similarities between drivers.
 */
class AbstractSQL
{

    // Properties
    public $formatter;
    protected array $tables = [];
    protected array $columns = [];


    /**
     * Set formatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Insert row(s)
     */
    protected function insertDo(DbInterface $db, ...$args):void
    { 

        // Check if table exists
        $table_name = array_shift($args);
        if (!$db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform insert, as database table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders) = array([], []);
        $columns = $db->getColumnNames($table_name, true);

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
        $value_sets = [];
        foreach ($arg_sets as $set) { 

            // GO through key-value pairs
            $placeholders = [];
            foreach ($set as $column => $value) { 
                $placeholders[] = $this->formatter::getPlaceholder($columns[$column]);
                $values[] = $value;
            }
            $value_sets[] = '(' . implode(", ", $placeholders) . ')';
        }

        // Finish SQL
        $sql = "INSERT INTO $table_name (" . implode(', ', $insert_columns) . ") VALUES ";
        $sql .= implode(', ', $value_sets);

        // Execute SQL
        $db->query($sql, ...$values);
    }

    /**
     * Insert or update
     */
    protected function insertOrUpdateDo(DbInterface $db, ...$args):void
    { 

        // Check if table exists
        $table_name = array_shift($args);
        if (!$db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform insert_or_update as table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders, $update_values, $update_placeholders) = array([], [], [], []);
        $columns = $db->getColumnNames($table_name, true);

        // Map object, if needed
        if (is_object($args[0])) { 
            $args[0] = FromInstance::map($args[0], $columns);
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
        $sql .= implode(", ", $placeholders) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $update_placeholders);

        // Execute SQL
        $db->query($sql, ...$values, ...$update_values);
    }

    /**
     * Update
     */
    protected function updateDo(DbInterface $db, ...$args):void
    { 

        // Check if table exists
        $table_name = array_shift($args);
        if (!$db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform update, as table does not exist, $table_name");
        }

        // Set variables
        list($values, $placeholders) = array([], []);
        $columns = $db->getColumnNames($table_name, true);

        // Map object, if needed
        $updates = array_shift($args);
        if (is_object($updates)) { 
            $record_id = FromInstance::getObjectId($updates);
            $updates = FromInstance::map($updates, $columns);
            $args = ["id = %i", $record_id];
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
        $db->query($sql, ...$values, ...$args);
    }

    /**
     * Delete
     */
    protected function deleteDo(DbInterface $db, ...$args)
    { 

        // Check if table exists
        $table_name = array_shift($args);
        if (!$db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to perform delete as table does not exist, $table_name");
        }
        $sql = "DELETE FROM $table_name";

        // Map from object, if needed
        if (isset($args[0]) && is_object($args[0])) { 

            // Get id# of object
            if (!$id = FromInstance::getObjectId($args[0])) { 
                throw new DbObjectNotExistsException("Unable to perform delete, as no 'id' variable exists within the provided object.");
            }
            $sql .= " WHERE id = %i";
            $args = [$id];

        // String based where clause
        } elseif (isset($args[0]) && $args[0] != '') { 
            $sql .= ' WHERE ' . array_shift($args);
        }

        // Execute SQL
        $db->query($sql, ...$args);
    }

    /**
     * Get single / first row
     */
    protected function getRowDo(DbInterface $db, ...$args):array | object | null
    { 

        // Check for map class
        $map_class = class_exists($args[0]) ? array_shift($args) : '';

        // Get first row
        $result = $db->query(...$args);
        if (!$row = $db->fetchAssoc($result)) { 
            return null;
        }

        // Map to object, if needed
    if ($map_class != '' && class_exists($map_class)) { 
            $row = ToInstance::map($map_class, $row);
        } 

        // Return
        return $row;
    }

    /**
     * Get single row by id#
     */
    protected function getIdRowDo(DbInterface $db, ...$args):array | object | null
    { 

        // Check for map class
        $map_class = class_exists($args[0]) ? array_shift($args) : '';
        list($table_name, $id) = [$args[0], $args[1]];
        $id_col = $args[2] ?? 'id';

        //Check table
        if (!$db->checkTable($table_name)) { 
            throw new DbTableNotExistsException("Unable to get row by id, as table does not exist, $table_name");
        }

        // Get first row
        if (!$row = $db->getRow("SELECT * FROM $table_name WHERE $id_col = %s ORDER BY id LIMIT 1", $id)) { 
            return null;
        }

        // Map to class, if needed
        if ($map_class != '') { 
            $row = ToInstance::map($map_class, $row);
        }

        // Return
        return $row;
    }

    /**
     * Get single column
     */
    protected function getColumnDo(DbInterface $db, ...$args):array
    { 

        // Get column
        $cvalues = [];
        $result = $db->query(...$args);
        while ($row = $db->fetchArray($result)) { 
            $cvalues[] = $row[0];
        }

        // Return
        return $cvalues;
    }

    /**
     * Get two column hash 
     */
    protected function getHashDo(DbInterface $db, ...$args):array
    { 

        // Get hash
        $vars = [];
        $result = $db->query(...$args);
        while ($row = $db->fetchArray($result)) { 
            $vars[$row[0]] = $row[1];
        }

        // Return
        return $vars;
    }

    /**
     * Get single field / value
     */
    protected function getFieldDo(DbInterface $db, ...$args):mixed
    { 

        // Execute SQL query
        $result = $db->query(...$args);
        if (!$row = $db->fetchArray($result)) { 
            return null;
        }

        // Return
        return $row[0];
    }

    /**
     * Execute SQL file
     */
    public function executeSqlFileDo(DbInterface $db, string $filename):void
    {

        // Execute SQL file
        $sql_lines = SqlParser::parse(file_get_contents($filename));
        foreach ($sql_lines as $sql) { 
            $db->query($sql);
        }
    }

}


