
# executeSqlFile

**Description:** Execute a plain text file of SQL commands against the database.

> `void $db->executeSqlFile(string $filename)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$filename` | Yes | string | The location of the SQL file to execute.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Execute SQL file
$sql_file = "/path/to/import.sql";
$db->executeSqlFile($sql_file);
~~~

