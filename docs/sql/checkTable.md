
# checkTable

**Description:** Check whether or not a table exists within the database.

> `bool $db->checkTable(string $table_name)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to check existence of.


**Return Values:** Returns a boolean whether or not the table exists.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Check table
if (!$db->checkTable('some_table')) { 
    die("No, the table does not exist.");
} else { 
    echo "Yes, the table is here\n";
}
~~~

