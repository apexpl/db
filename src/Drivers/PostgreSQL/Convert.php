<?php
declare(strict_types = 1);

namespace Apex\Db\Drivers\PostgreSQL;


/**
 * This class converts any standard mySQL query as necessary 
 * to be executed against a PostgreSQL database.
 */
class Convert
{

    /**
     *  Convert mySQL to PostgreSQL
     */
    public static   function convert(\PDO $conn, string $sql):string
    {

        // Set replacement
        $replacements = [
            'INT NOT NULL PRIMARY KEY AUTO_INCREMENT' => 'SERIAL PRIMARY KEY', 
            'tinyint(1)' => 'SMALLINT', 
            'tinyint' => 'SMALLINT', 
            'datetime' => 'TIMESTAMP', 
            'longtext' => 'TEXT', 
            'longblob' => 'BYTEA', 
            'blob' => 'BYTEA', 
            'rand()' => 'RANDOM()'
        ];

        // Initial replacements
        foreach ($replacements as $key => $value) { 
            $sql = str_ireplace($key, $value, $sql);
        }

        // Check for LIMIT clause
        if (preg_match("/ LIMIT (\d+?)\,(\d+)/i", $sql, $match)) { 
            $sql = str_replace($match[0], " OFFSET $match[1] LIMIT $match[2] ", $sql);
        }

        // Check for create table
        if (preg_match("/create table (.+?)\s/i", $sql, $match)) { 
            $sql = self::create_table($sql, ($match[1]), $conn);

        // Alter table
        } elseif (preg_match("/^alter table (.+?)\s(ADD|DROP|CHANGE|RENAME)\s(.+?)/i", $sql, $match)) { 
            $sql = self::alter_table($sql, $match[1], $match[2], $match[3], $conn);
        }

        // Return
        return $sql;

    }

    /**
     * Create table
     */
    protected static function create_table(string $sql, string $table_name, \PDO $conn):string
    {

        // Additional replacements
        $sql = preg_replace("/engine(\s*?)=(\s*?)InnoDB/i", "", $sql);
        $sql = preg_replace("/ DEFAULT CHARACTER SET=utf8/i", "", $sql);

        // Process enums
        $sql = self::process_enums($sql, $table_name, $conn);

        // Return
        return $sql;
    }

    /**
     * Alter table
     */
    protected static function alter_table(string $sql, string $table_name, string $action, string $end_sql, \PDO $conn):string
    {

        // Initialize
        $action = trim(strtolower($action));

        // Add column
        if ($action == 'add') { 
            $sql = preg_replace("/\s(AFTER|BEFORE)(.+)$/i", "", $sql);
        } elseif ($action == 'change' && preg_match("/^(.+?)\s(.+?)\s(.+)$/i", trim($end_sql), $match)) { 

            // Rename,if needed
            if (trim($match[1]) != trim($match[2])) { 
                db::query("ALTER TABLE $table_name RENAME COLUMN " . trim($match[1]) . ' TO ' . trim($match[2]));
                $col_name = trim($match[2]);
            } else {
                $col_name = trim($match[1]);
            }

            // Set not null, if needed
            if (preg_match("/^(.+?)\snot null(.+)$/i", $match[3], $null_match)) { 
                db::query("ALTER TABLE $table_name ALTER COLUMN $col_name SET NOT NULL");
                $type = $null_match[1];
            } else { 
                $type = $match[3];
            }

            // Set SQL
            $sql = "ALTER TABLE $table_name ALTER COLUMN $col_name TYPE $type";
        }

        // Process enums
        $sql = self::process_enums($sql, $table_name);

        // Return
        return $sql;
    }

    /**
     * Process ENUMs
     */
    protected static function process_enums(string $sql, string $table_name, \PDO $conn):string
    {

        // Check for ENUMs
        preg_match_all("/(\w+?)(\s+?)ENUM(\s*?)\((.*?)\)(.*?)\,/si", $sql, $enum_match, PREG_SET_ORDER);
        foreach ($enum_match as $match) { 

            // Set variables
            $col_name = $match[1];
            $type_name = 'enum_' . $table_name . '_' . $col_name;

            // Add to SQL
            $conn->query("DROP TYPE IF EXISTS $type_name");
            $conn->query("CREATE TYPE $type_name AS ENUM (" . $match[4] . ")");
            $sql = str_replace($match[0], "$col_name $type_name $match[5],", $sql);
        }

        // Return
        return $sql;
    }

}


