
# updateById

**Description:** Update single record by unique id#.

> `void $db->updateById(string $table_name, int | string $id, array $updates)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to update record in.
`$id` | Yes | int / string | The unique id# to update.
`$updates` | Yes | array | Associative array of record values to update record with.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Update user id# 1955
$userid = 1955;
$db->updateById('users', $userid, ['email' => 'new.email@domain.com']);
~~~


