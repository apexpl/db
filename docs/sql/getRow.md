
# getRow

**Description:** Retrieve the first row returned by the SQL statement.

> `array $db->getRow(string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


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
$email = 'jsmith@domain.com';
if (!$row = $db->getRow("SELECT * FROM users WHERE email = %s", $email)) { 
    die("No user at the e-mail address, $email");
}
echo "Username: " . $row['username'] . "\n";


// Same query, but get an object this time
if (!$row = $db->getRow("SELECT * FROM users WHERE email = %s", $email)) { 
    die("No user at the e-mail address, $email");
}
$user = ToInstance::map(UserModel::class, $row);
echo "Username: " . $user->getUsername() . "\n";
~~~

