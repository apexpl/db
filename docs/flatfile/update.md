
# update

**Description:** Update one or more records within table.

> `void $db->update(string $table_name, array $conditions, array $updates)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to update records in.
`$conditions` | Yes | array | Conditions of records to update.  Please see the conditions section in the [select()](select.md) page for details on this array.
`$updates` | Yes | array | Associative array of record values to update records with.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Update all users with status of 'inactive' to group_id = 4
$db->update('users', ['status', '=', 'inactive'], ['group_id' => 4]);
~~~


