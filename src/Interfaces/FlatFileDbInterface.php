<?php

namespace Apex\Db\Interfaces;

/**
 * Flat file database interface.
 *
 * Used for both, SleekDB (JSON) and CSV interfaces.
 */
interface FlatFileDbInterface
{

    /**
     * Create table
     */
    public function createTable(string $table_name, array $columns = []);

    /**
     * Drop table
     */
    public function dropTable(string $table_name):void;

    /**
     * Insert data
     */
    public function insert(string $table_name, array $row):?int;

    /**
     * Insert many
     */
    public function insertMany(string $table_name, array $rows):void;

    /**
     * Select
     */
    public function select(string $table_name, array $conditions, string $order_by, int $limit, int $offset):?iterable;

    /**
     * Select all
     */
    public function selectAll(string $table_name):?iterable;

    /**
     * Get id row
     */
    public function selectById(string $table_name, int | string $id):?array;

    /**
     * Update by id
     */
    public function updateById(string $table_name, string | int $id, array $updates):void;

    /**
     * Update
     */
    public function update(string $table_name, array $conditions, array $updates):void;

    /**
     * Delete by id
     */
    public function deleteById(string $table_name, string | int $id):void;

    /**
     * Delete
     */
    public function delete(string $table_name, array $conditions):void;

}


