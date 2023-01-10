
# getForeignKeys

**Description:** Get details on all foreign keys of a table within the database.

> `array $db->getForeignKeys(string $table_name])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to get foreign keys of.


**Return Values:** An associative array, the keys being the column name, and the value being an associative array of the referenced foreign key.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Get column details
$foreign_keys = $db->getForeignKeys('users');
print_r($foreign_keys);
~~~


