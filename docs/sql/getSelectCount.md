
# getSelectCount

**Description:** Get the total number of rows from a select statement.

> `int $db->getSelectRows(PDOStatement $result)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$result` | Yes | &nbsp; | The result provided after performing the SQL query.  Will be a `PDOStatement` object.


**Return Values:** The number of affected rows if a insert / update / delete statement, or the total number of rows within the result set if a select statement.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Get users
$rows = $db->query("SELECT * FROM users WHERE group_id = 2");

$num = $db->getSelectCount($result);
echo "Total rows: $num\n";
~~~

