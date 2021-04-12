
# getObject

**Description:** Retrieve the first row mapped to an object returned by the SQL statement.

> `object $db->getObject(string $class_name, string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$class_name` | Yes | string | The full class name which to instantiate and map the database record to.
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:** An object fully instantiated and injected of the class name provided.


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
$email = 'jsmith@domain.com';
if (!$user = $db->getObject(UserModel::class, "SELECT * FROM users WHERE email = %s", $email)) { 
    die("No user at the e-mail address, $email");
}
echo "Username: " . $user->getUsername() . "\n";
~~~




