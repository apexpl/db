
# getIdRow

**Description:** Get the single record identified by the unique id#.

> `array $db->getIdRow(string $table_name, string $id_number)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to retrieve record from.
`$id_number` | Yes | string | The unique id# of the record to retrieve.  Must by the `id` column of the database table.


**Return Values:** An object fully instantiated and injected if the `$map_class` argument was passed, otherwise an associative array of the first row found.  Returns null if no record was found.


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
if (!$row = $db->getIdRow('users', $userid)) { 
    die("No user at the id# $userid");
}
echo "Username: " . $row['username'] . "\n";


// Same query, but get an object this time
if (!$row = $db->getIdRow('users', $userid)) { 
    die("No user at the id# $userid");
}
$user = ToInstance::map(UserModel::class, $row);
echo "Username: " . $user->getUsername() . "\n";
~~~

