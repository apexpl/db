<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\SleekDB;

use Apex\Db\Interfaces\FlatFileDbInterface;
use Apex\Db\Exceptions\DbInvalidSelectConditionException;

/**
 * SleekDB database driver
 */
class SleekDB implements FlatFileDbInterface
{

    // Properties
    private array $tables = [];

    /**
     * Constructor
     */
    public function __construct(
        private string $datadir = ''
    ) {

        if ($this->datadir == '') { 
            $this->datadir = '/../../../data';
        }

    }

    /**
     * Insert data
     */
    public function insert(string $table_name, array $row):?int
    {
        $table = $this->createTable($table_name);
        $result = $table->insert($row);

        // Return
        return (int) $result['_id'] ?? null;
    }

    /**
     * Insert many
     */
    public function insertMany(string $table_name, array $rows):void
    {
        $table = $this->createTable($table_name);
        $table->insertMany($rows);
    }

    /**
     * Select
     */
    public function select(string $table_name, array $conditions = [], string $order_by = null, int $limit = null, int $offset = null):?array
    {

        // Initialize
        $table = $this->createTable($table_name);
        $query = $table->createQueryBuilder();

        // Go through conditions
        $where = [];
        foreach ($conditions as $line) { 

            // Check if correct format
            if (!preg_match("/^(.+?)(=|\!=|=~|\!~|>=|<=|>|<)\s(.+)$/", $line, $match)) { 
                throw new DbInvalidSelectConditionException("Invalid select condition, $line.  Supported operands are: =, !=, =~, !~, >=, <=, >, <");
            }

            // Get operator
            $opr = match(trim($match[2])) { 
                '=~' => 'LIKE', 
                '!~' => 'NOT LIKE', 
                default => trim($match[2])
            };

            // Add condition
            $where[] = [trim($match[1]), $opr, trim($match[3])];
        }

        // Get order by
        if ($order_by !== null && preg_match("/^(.+)\sdesc/i", $order_by, $match)) { 
            $order_by = [$match[1] => 'desc'];
        } elseif ($order_by !== null) { 
            $order_by = [$order_by => 'asc'];
        }

        // Search database
        $table = $this->createTable($table_name);
        $rows = $table->findBy($where, $order_by, $limit, $offset);

        // Return
        return $rows;
    }

    /**
     * Select all
     */
    public function selectAll(string $table_name):?array
    {
        $table = $this->createTable($table_name);
        return $table->findAll();
    }

    /**
     * Get id row
     */
    public function selectById(string $table_name, int | string $id):?array
    {

        // Get table
        $table = $this->createTable($table_name);

        // Get row
        if (!$row = $table->findById($id)) { 
            return null;
        }

        // Return
        return $row;
    }

    /**
     * Update by id
     */
    public function updateById(string $table_name, int | string $id, array $updates):void
    {
        $table = $this->createTable($table_name);
        $table->updateById($id, $updates);
    }

    /**
     * Update
     */
    public function update(string $table_name, array $conditions, array $updates):void
    {

        // Initialize
        $table = $this->createTable($table_name);

        // Go through rows
        $rows = $this->select($table_name, $conditions);
        foreach ($rows as $row) { 
            $table->updateById($row['id'], $updates);
        }

    }

    /**
     * Delete by id
     */
    public function deleteById(string $table_name, string | int $id):void
    {
        $table = $this->createTable($table_name);
        $table->deleteById($id);
    }

    /**
     * Delete
     */
    public function delete(string $table_name, array $conditions):void
    {

        // Initialize
        $table = $this->createTable($table_name);

        // Go through rows
        $rows = $this->select($table_name, $conditions);
        foreach ($rows as $row) { 
            $table->deleteById($row['id']);
        }

    }

    /**
     * Create table
     */
    public function createTable(string $table_name, array $columns = [])
    {

        // Create table, if needed
        if (!isset($this->tables[$table_name])) { 
            $this->tables[$table_name] = new \SleekDB\Store($table_name, $this->datadir);
        }

        // Return
        return $this->tables[$table_name];
    }

    /**
     * Drop table
     */
    public function dropTable(string $table_name):void
    {
        $table = $this->createTable($table_name);
        $table->deleteStore();
    }

}

