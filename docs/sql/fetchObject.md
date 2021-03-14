
# fetchObject

**Description:** Gets a single record from a result row and returns an object with its properties injected with the record's values.

> `object $db->fetchObject(PDOStatement $stmt, string $class_name, [int $position = null])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$stmt` | Yes | PDOStatement | The result returned by the [query()](query.md) method.
`$class_name` | Yes | string | The full class name of the object to return an instance of.
`$position` | No | int | The position within the result set of which to retrieve the record.  Defaults to null, meaning the next record as you're traversing through them.


**Return Values:** A An instance of the class name provided, injected and instanciated with the record's values.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);
$group_id = 2;

// Go through users
$rows = $db->query("SELECT * FROM users WHERE group_id = %i", $group_id);
while ($user = $db->fetchObject($result, UserModel::class)) { 
    // $user is an instance of UserModel object.
}
~~~

