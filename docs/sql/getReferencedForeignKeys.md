
# getReferencedForeignKeys

**Description:** Get details on all referenced foreign keys of a table within the database.  Instead of the foreign keys of the table itself, it will return the foreign keys of any tables that have foreign key constraints created against the table (ie. child tables).

> `array $db->getReferencedForeignKeys(string $table_name])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to get referenced foreign keys of.


**Return Values:** An associative array, the keys being the column name, and the value being an associative array of the referenced foreign key.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Get column details
$foreign_keys = $db->getReferencedForeignKeys('users');
print_r($foreign_keys);
~~~


