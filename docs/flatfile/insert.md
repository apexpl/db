
# insert

**Description:** Insert single record into table.

> `int $db->insert(string $table_name, array $record)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to insert record into.
`$record` | Yes | array | Associative array of the record to insert.

**Return Values:** The unique id# of the newly inserted row.

#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// insert record
$record = [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@domain.com'
];
$userid = $db->insert('user', $record);

echo "User id: $userid\n":
~~~



