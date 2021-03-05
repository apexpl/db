
# insertMany

**Description:** Insert multiple records into table at once.

> `void $db->insertMany(string $table_name, array $records)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to insert records into.
`$records` | Yes | array | An array of associative arrays, each being one record to insert.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Define multiple records
$records = [
    [
        'username' => 'jsmith', 
        'fulL_name' => 'John Smith', 
        'email' => 'jsmith@domain.com'
    ], [
        'username' => 'leanne', 
        'full_name' => 'Leanne Bristol', 
        'email' => 'lbristol@domain.com'
    ], [
        'username' => 'mike', 
        'full_name' => 'Mike Jacobs', 
        'email' => 'mike@domain.com'
    ]
];

// Insert records
$db->insertMany('users', $records);
~~~


