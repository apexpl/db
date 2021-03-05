
# eval

**Description:** Evaluates and returns the results of a database function.

> `string $db->eval(string $sql_function)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql_function` | Yes | string | The SQL function to execute.


**Return Values:** The result of the SQL function.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);


// Get current date / time
$date = $db->eval('now()');
echo "Current datetime is $date\n";
~~~

