<?php

namespace Apex\Db\Interfaces;

use Apex\Db\Drivers\SqlQueryResult;


/**
 * Database interface
 */
interface DbInterface 
{

    /**
     * Connect to database
     *
    public function connect(string $dbname, string $dbuser, string $dbpass, string $dbhost, int $dbport):PDO;

    /**
     * Get table names
     */
    public function getTableNames():array;

    /**
     * Get columns of table.
     */
    public function getColumnNames(string $table_name, bool $include_types = false):array;

    /**
     * Clear cache
     */
    public function clearCache();

    /**
     * Get database size in mb
     */
    public function getDatabaseSize():float;

    /**
     * Get primary key of table
     */
    public function getPrimaryKey(string $table_name):?string;


    /**
     * Insert record into database
     */
    public function insert(string $table_name, ...$args):void;

    /**
     Insert or update on duplicate key
     */
    public function insertOrUpdate(string $table_name, ...$args):void;

    /**
     * Update database table
     */
    public function update(string $table_name, array | object $updates, ...$args):void;

    /**
     * Delete rows
     */
    public function delete(string $table_name, string | object $where_clause, ...$args):void;

    /**
     * Get single / first row
     */
    public function getRow(string $sql, ...$args):?array;

    /**
     * Get single row by id#
     */
    public function getIdRow(string $table_name, string | int $id, string $idcol = 'id'):?array;

    /**
     * Get single column
     */
    public function getColumn(string $sql, ...$args):array;

    /**
     * Get two column hash 
     */
    public function getHash(string $sql, ...$args):array;


    /**
     * Get single field / value
     */
    public function getField(string $sql, ...$args):mixed;

    /**
     * Eval
     */
    public function eval(string $sql):mixed;

    /**
     * Query SQL statement
     */
    public function query(string $sql, ...$args):\PDOStatement;


    /**
     * Fetch array
     */
    public function fetchArray(\PDOStatement $stmt, int $position = null):?array;


    /**
     * Fetch assoc
     */
    public function fetchAssoc(\PDOStatement $stmt, int $position = null):?array;

    /**
     * Fetch object
     */
    public function fetchObject(\PDOStatement $stmt, string $class_name, int $position = null):?object;


    /**
     * Number of rows affected
     */
    public function numRows(\PDOStatement $stmt):int;

    /**
     * Get number of rows in select result
     */
    public function getSelectCount(\PDOStatement $stmt):int;


    /**
     * Last insert id
     */
    public function insertId():?int;


    /**
     * Add time
     */
    public function addTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string;


    /**
     * Subtract time
     */
    public function subtractTime(string $period, int $length, string $from_date, bool $return_datestamp = true):string;


    /**
     * Check if table exists
     */
    public function checkTable(string $table_name):bool;


    /**
     * Begin transaction
     */
    public function beginTransaction(bool $force_write = false):void;


    /**
     * Commit transaction 
     */
    public function commit():void;


    /**
     * Rollback transaction
     */
    public function rollback():void;

    /**
     * Execute SQL file
     */
    public function executeSqlFile(string $filename):void;

    /**
     * Force write connection on next query.
     */
    public function forceWrite(bool $always = false):void;


}


