<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\SQLite;

use Apex\Db\Connections;
use Apex\Db\Drivers\AbstractSQL;
use Apex\Db\Drivers\SQLite\Format;
use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Exceptions\{DbConnectException, DbInvalidArgumentException};
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;
use PDO;


/**
 * SQLite database driver.
 */
class SQLite extends AbstractSQL implements DbInterface
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
        private string $charset = 'utf8mb4',  
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
    public function connect(string $dbname = '', string $user = '', string $password = '', string $host = '', int $port = 0)
    { 

        // Connection string
        $dsn = 'sqlite:' . $dbname;

        // Connect
        try {
            $conn = new PDO($dsn);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            if ($this->onconnect_fail !== null && is_callable($this->onconnect_fail)) { 
                call_user_func($this->onconnect_fail);
            }
            throw new DbConnectException("Unable to connect to database using supplied information.  Error: " . $e->getMessage());
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

        // Check for 'rowid'
        if (isset($row['rowid'])) { 
            $row['id'] = $row['rowid'];
            unset($row['rowid']);
        }

        // Return
        return $row;
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

        // Check for valid period
        if (!in_array($period, ['second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year'])) { 
            throw new DbInvalidArgumentException("Invalid time period specified, $period.  Supported values are:  second, minute, hour, day, week, month, quarter, year");
        }

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

}


