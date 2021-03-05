
# dropTable

**Description:** Deletes a table.

> `void $db->deleteTable(string $table_name)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to delete.


#### Example

~~~php
use Apex\Db\Drivers\CSV\CSV;

// Connect
$db = new CSV(
    datadir: '/path/to/data'
);

// Drop table
$db->dropTable('users');
~~~


