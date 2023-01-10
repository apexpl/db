
# getTableNames

**Description:** Get list of all table names within the database.

> `array $db->getTableNames()`

**Return Values:** Returns a one-dimensional array of all table names within the database.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Get tables
$tables = $db->getTableNames();
print_r($tables);
~~~


