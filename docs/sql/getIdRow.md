
# getIdRow

**Description:** Get the single record identified by the unique id#.

> `array | object | null $db->getIdRow([string $map_class], string $table_name, string $id_number)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$map_class` | No | string | Optional full class name, and if present the record returned from this query will be automatically mapped to an instance of this class.  See the [Object Mapping](../object_mapping.md) page for details.
`$table_name` | Yes | string | The table name to retrieve record from.
`$id_number` | Yes | string | The unique id# of the record to retrieve.  Must by the `id` column of the database table.


**Return Values:** An object fully instantiated and injected if the `$map_class` argument was passed, otherwise an associative array of the first row found.  Returns null if no record was found.


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

// Get user
$userid = 3199;
if (!$row = $db->getIdRow('users', $userid)) { 
    die("No user at the id# $userid");
}
echo "Username: " . $row['username'] . "\n";


// Same query, but get an object this time
if (!$user = $db->getIdRow(UserModel::class, 'users', $userid)) { 
    die("No user at the id# $userid");
}
echo "Username: " . $user->getUsername() . "\n";
~~~

