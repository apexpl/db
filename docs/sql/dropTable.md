
# dropTable

**Description:** Drop a table within the database, including all tables that have referenced foreign key constraints to the table.

> `void $db->dropTable(string $table_name)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to drop.


**Return Values:** Returns nothing.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Drop table
$db->dropTable('some_table');
~~~


