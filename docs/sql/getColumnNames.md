
# getColumnNames

**Description:** Get all column names within a database table.

> `array $db->getColumnNames(string $table_name, [bool $return_types = false])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to get column names of.

`$return_types` | No | bool | Whether or not to include the column types in response.  Defaults to false.


**Return Values:** Either a one-dimensinoal array of column names if `$return_types` is false, or an associative array of column names with their corresponding types if `$return_types` is true. 


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Get column anems
$cols = $db->getColumnNames('users');
print_r($cols);


// Get column names, including types
$cols = $db->getColumnNames('users', true);
print_r($cols);
~~~


