
# getPrimaryKey

**Description:** Get the column name of the primary key in a table.

> `?string $db->getPrimaryKey(string $table_name)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to get primary key column of.


**Return Values:** The name of the column of the primary key within the table, or null if no primary key exists.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Get primary key
if ($name = $db->getPrimaryKey('some_table')) { 
    die("The table does not have a primary key");
} else { 
    echo "Yes, the primary key column is $name\n";
}
~~~

