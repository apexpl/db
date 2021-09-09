
# truncate

**Description:** Delete all rows from a database table, and reset any needed auto increment as necessary.

> `?void $db->truncate(string $table_name)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to truncate.


**Return Values:** Nothing.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Truncate
$db->truncate('some_table');
~~~

