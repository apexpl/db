
# selectById

**Description:** Select single record by unique id#

> `array | null $db->selectById(string $table_name, string | int $id)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to retrieve record from.
`$id` | Yes | int / string | The unique id# to retrieve.

**Return Values:** An associative array of the record.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Get user id# 1955
$userid = 1955;
$row = $db->selectById('users', $userid);
print_r($row);
~~~



