
# delete

**Description:** Delete one or more records within table.

> `void $db->delete(string $table_name, array $conditions)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to delete records from.
`$conditions` | Yes | array | Conditions of records to delete.  Please see the conditions section in the [select()](select.md) page for details on this array.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Delete all users with status of 'inactive'
$db->delete('users', ['status', '=', 'inactive']);
~~~


