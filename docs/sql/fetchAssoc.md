
# fetchAssoc

**Description:** Gets a single record from a result row as an named based associative array.

> `array $db->fetchArray(PDOStatement $stmt, [int $position = null])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$stmt` | Yes | PDOStatement | The result returned by the [query()](query.md) method.
`$position` | No | int | The position within the result set of which to retrieve the record.  Defaults to null, meaning the next record as you're traversing through them.


**Return Values:** A name based associative array of the returned record.  Returns null if no record exists.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);
$group_id = 2;

// Go through users
$rows = $db->query("SELECT * FROM users WHERE group_id = %i", $group_id);
while ($row = $db->fetchAssoc($result)) { 
    print_r($row);
}
~~~

