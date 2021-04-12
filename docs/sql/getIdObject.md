
# getIdObject

**Description:** Get the single record mapped to an object identified by the unique id#.

> `object $db->getIdObject(string $class_name, string $table_name, string | int $id_number)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |-------------
`$class_name` | Yes | string | The full class name which to instantiate and map the database record to.
`$table_name` | Yes | string | The table name to retrieve record from.
`$id_number` | Yes | string | The unique id# of the record to retrieve.  Must by the `id` column of the database table.


**Return Values:** An object fully instantiated and injected to the class name provided.  Returns null if record not found.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Mapper\ToInstance;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Get user
$userid = 3199;
if (!$user = $db->getIdObject(UserModel::class, 'users', $userid)) { 
    die("No user at the id# $userid");
}
echo "Username: " . $user->getUsername() . "\n";
~~~



