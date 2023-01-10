
# numRows

**Description:** Get the number of affected rows from previous statement.

> `int $db->numRows(PDOStatement $result)`


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

// Update users
$result = $db->query("UPDATE users SET status = 'inactive' WHERE status = 'pending'");

$num = $db->numRows($result);
echo "$num rows were updated\n";
~~~

