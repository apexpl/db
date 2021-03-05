
# createTable

**Description:** Creates a new table.  If using SleekDB, you may ignore this method as you don't need it.  This is only needed for CSV.

> `void $db->createTable(string $table_name, array $columns)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to create.
`$columns` | No | array | Only used for CSV, and a one-dimensional array of column names.


#### Example

~~~php
use Apex\Db\Drivers\CSV\CSV;

// Connect
$db = new CSV(
    datadir: '/path/to/data'
);

// Create table
$db->createTable('users', ['username', 'full_name', 'email']);
~~~


