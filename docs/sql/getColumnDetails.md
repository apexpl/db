
# getColumnDetails

**Description:** Get details of all columns within a database table including column type, length, whether or not it's an index or primary key, et al.

> `array $db->getColumnDetails(string $table_name])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to get column names of.


**Return Values:** An associative array, the keys being the column alias, and the value being an associative array of the details of said column.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Get column details
$details = $db->getColumnDetails('users');
print_r($details);
~~~


