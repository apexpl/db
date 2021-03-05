<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\CSV;

use Apex\Db\Interfaces\FlatFileDbInterface;
use Apex\Db\Exceptions\{DbInvalidSelectConditionException, DbTableNotExistsException};
use League\Csv\{Reader, Writer, Statement};


/**
 * CSV database driver, using the league/csv package.
 */
class CSV implements FlatFileDbInterface
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

        // Get columns
        $reader = $this->getReader($table_name);
        $columns = $reader->getHeader();

        // GEt values
        $values = [$this->getNextId($table_name)];
        foreach ($columns as $col) { 
            if ($col == 'id') { continue; }
            $values[] = $row[$col] ?? '';
        }

        // Get writer
        $writer = $this->getWriter($table_name);
        $writer->insertOne($values);

        // Return
        return (int) $values[0];
    }

    /**
     * Insert many
     */
    public function insertMany(string $table_name, array $rows):void
    {

        // Get columns
        $reader = $this->getReader($table_name);
        $columns = $reader->getHeader();

        // Create records to insert
        $records = [];
        foreach ($rows as $row) { 

            // GEt values
            $values = [$this->getNextId($table_name)];
            foreach ($columns as $col) { 
                if ($col == 'id') { continue; }
                $values[] = $row[$col] ?? '';
            }
        $records[] = $values;
        }

        // Get writer
        $writer = $this->getWriter($table_name);
        $writer->insertAll($records);
    }

    /**
     * Select
     */
    public function select(string $table_name, array $conditions = [], string $order_by = 'id', int $limit = -1, int $offset = 0):?iterable
    {

        // Initialize
        $reader = $this->getReader($table_name);
        $columns = $reader->getHeader();

        // Set variables
        $this->conditions = $conditions;
        if (preg_match("/^(.+)\s(asc|desc)$/", $order_by, $match)) { 
            $this->sort_by = $match[1];
            $this->sort_dir = $match[2];
        } else { 
            $this->sort_by = $order_by;
            $this->sort_dir = 'asc';
        }

        // Execute statement
        $stmt = (new Statement())->offset($offset)->limit($limit)->where([$this, 'checkCondition'])->orderBy([$this, 'checkOrderBy']);
        $rows = $stmt->process($reader);

        // Return
        return $rows;
    }

    /**
     * Check condition
     */
    public function checkCondition($row, $id, $iter):bool
    {

        // Go through conditions
        $ok=true;
        foreach ($this->conditions as $line) { 

            // Check if correct format
            if (!preg_match("/^(.+?)(=|\!=|=~|\!~|>=|<=|>|<)\s(.+)$/", $line, $match)) { 
                throw new DbInvalidSelectConditionException("Invalid select condition, $line.  Supported operands are: =, !=, =~, !~, >=, <=, >, <");
            }
            list($col, $opr, $value) = [trim($match[1]), trim($match[2]), trim($match[3])];
        $chk = $row[$col] ?? '';

            // Check if line ok
            $line_ok = match(true) { 
                ($opr == '=' && $value != $chk) => false, 
                ($opr == '!=' && $value == $chk) => false, 
                ($opr == '>' && $value <= $chk) => false, 
                ($opr == '<' && $value >= $chk) => false, 
                ($opr == '>=' && $value < $chk) => false,  
                ($opr == '<=' && $value > $chk) => false,
                ($opr == '=~' && !str_contains($chk, $value)) === true => false, 
                ($opr == '!~' && str_contains($chk, $value)) === true => false, 
                default => true
            };
            if ($line_ok === false) { 
                $ok = false;
                break;
            }
        }

        // Return
        return $ok;

    }

    /**
     * Check order by
     */
    public function checkOrderBy(array $a, array $b):int
    {

        $col = $this->sort_by;
        if ($this->sort_dir == 'desc') { 
            return strcmp($b[$col], $a[$col]);
        } else { 
            return strcmp($a[$col], $b[$col]);
        }
    }

    /**
     * Select all
     */
    public function selectAll(string $table_name):?iterable
    {
        $reader = $this->getReader($table_name);
        return $reader->getRecords();
    }

    /**
     * Get id row
     */
    public function selectById(string $table_name, int | string $id):?array
    {

        // Get table
        $result = $this->select($table_name, ['id = ' . $id]);
        $row = $result->fetchOne(0);

        // Return
        return $row;
    }

    /**
     * Update by id
     */
    public function updateById(string $table_name, string | int $id, array $updates):void
    {


    }

    /**
     * Update
     */
    public function update(string $table_name, array $conditions, array $updates):void
    {

    }

    /**
     * Delete by id
     */
    public function deleteById(string $table_name, string | int $id):void
    {

    }

    /**
     * Delete
     */
    public function delete(string $table_name, array $conditions):void
    {


    }

    /**
     * Create table
     */
    public function createTable(string $table_name, array $columns = [])
    {

        // Check if exists
        $csv_file = $this->datadir . '/' . $table_name . '.csv';
        if (file_exists($csv_file)) { 
            return;
        }

        // Get columns
        $cols = ['id'];
        foreach ($columns as $column) { 
            if (strtolower($column) == 'id') { continue; }
            $cols[] = strtolower($column);
        }

        // Create CSV file
        $writer = Writer::createFromPath($csv_file, 'w+');
        $writer->insertOne($cols);

        // Add to tables.json
        $json = [];
        if (file_exists($this->datadir . '/tables.json')) { 
        $json = json_decode(file_get_contents($this->datadir . '/tables.json'), true);
        }

        // Save JSON file
        $json[$table_name] = 0;
        file_put_contents($this->datadir . '/tables.json', json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * Drop table
     */
    public function dropTable(string $table_name):void
    {

        // Delete CSV file
        $csv_file = $this->datadir . '/' . $table_name . '.csv';
        if (file_exists($csv_file)) {
            @unlink($csv_file);
        }

        // Check if tables.json exists
        if (!file_exists($this->datadir . '/tables.json')) { 
            return;
        }

        // Delete from tables.json
        $json = json_decode(file_get_contents($this->datadir . '/tables.json'), true);
        unset($json[$table_name]);
        file_put_contents($this->datadir . '/tables.json', json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * Get next autoincrementing id#
     */
    public function getNextId(string $table_name)
    {

        // Open file
        $fh = fopen($this->datadir . '/tables.json', 'r+');
        flock($fh, LOCK_EX);

        // Get contents
    $contents = '';
        while (!feof($fh)) { 
            $contents .= fgets($fh, 4096);
        }

        // Update json
        $json = json_decode(trim($contents), true);
        if (!isset($json[$table_name])) { 
            $json[$table_name] = 1;
            $id = 1;
        } else { 
            $id = ++$json[$table_name];
        }

        // Write new contents
        ftruncate($fh, 0);
        fwrite($fh, json_encode($json, JSON_PRETTY_PRINT));
        fflush($fh);

        // Close file
        flock($fh, LOCK_UN);    // release the lock
        fclose($fh);

        // Return
        return $id;
    }

    /**
     * Get reader
     */
    private function getReader(string $table_name)
    {

        // Check file exists
        $csv_file = $this->datadir . '/' . $table_name . '.csv';
        if (!file_exists($csv_file)) { 
            throw new DbTableNotExistsException("The table $table_name does not exist.  Please create it first with the createTable() method.");
        }

        // Start reader
        $reader = Reader::createFromPath($csv_file, 'r');
        $reader->setHeaderOffset(0);

        // Return
        return $reader;
    }

    /**
     * Get reader
     */
    private function getWriter(string $table_name)
    {

        // Check file exists
        $csv_file = $this->datadir . '/' . $table_name . '.csv';
        if (!file_exists($csv_file)) { 
            throw new DbTableNotExistsException("The table $table_name does not exist.  Please create it first with the createTable() method.");
        }

        // Start reader
        $writer = Writer::createFromPath($csv_file, 'a+');
        return $writer;
    }


}



