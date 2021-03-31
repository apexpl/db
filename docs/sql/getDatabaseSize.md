
# getDatabaseSize

**Description:** Get the size of the current database in MB.

> `float $db->getDatabaseSize()`


**Return Values:** Returns the size of the current database in MB, formatted to two decimals.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Get size
$size = $db->getDatabaseSize();
echo "Current db size is $size MB\n";
~~~



