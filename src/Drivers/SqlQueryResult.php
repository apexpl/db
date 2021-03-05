<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers;

use Apex\Db\Mapper\ToInstance;
use Apex\Db\Interfaces\DbInterface;


/**
 * Custom iterator / container so they can handle proper 
 * iteration when using as an array, and automated mapping to objects where necessary.
 */ 
class SqlQueryResult implements \Iterator
{

    // Properties
    private int $position = 0;
    private int $total = 0;

    /**
     * Construct
     */
    public function __construct(
        private DbInterface $db, 
        private $result,
        private string $map_class = ''
    ) { 
        $this->total = $db->numRows($result);
    }

    /**
     * Rewind
     */
    public function rewind()
    {
        if ($this->position > 0) { $this->position--; }
    }

    /**
     * Current
     */
    public function current()
    {
        return $this->fetch();
    }

    /**
     * Key
     */
    public function key()
    {
        return $this->key ?? null;
    }

    /**
     * Next
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Valid
     */
    public function valid()
    {
        return $this->position >= ($this->total - 0) ? false : true;
    }

    /**
     * Fetch next row
     */
    private function fetch()
    {

        // Check position
        if ($this->position >= ($this->total - 0)) { 
            return null;
        }

        // Get row
        if (!$row = $this->db->fetchAssoc($this, $this->position)) { 
            return null;
        }

        // Map to object, if needed
        if ($this->map_class != '' && class_exists($this->map_class)) { 
            $row = ToInstance::map($this->map_class, $row);
        }

        // Return
        return $row;
    }

    /**
     * Return the result
     */
    public function getResult()
    {
        return $this->result;
    }


}


