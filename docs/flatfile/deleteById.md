
# deleteById

**Description:** Delete single record by unique id#.

> `void $db->deleteById(string $table_name, int | string $id)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to delet erecord from.
`$id` | Yes | int / string | The unique id# to delete.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Delete user id# 1955
$userid = 1955;
$db->deleteById('users', $userid);
~~~


