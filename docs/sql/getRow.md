
# getRow

**Description:** Retrieve the first row returned by the SQL statement.

> `array | object | null $db->getRow([string $map_class], string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$map_class` | No | string | Optional full class name, and if present the record returned from this query will be automatically mapped to an instance of this class.  See the [Object Mapping](../object_mapping.md) page for details.
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


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
$email = 'jsmith@domain.com';
if (!$row = $db->getRow("SELECT * FROM users WHERE email = %s", $email)) { 
    die("No user at the e-mail address, $email");
}
echo "Username: " . $row['username'] . "\n";


// Same query, but get an object this time
if (!$user = $db->getRow(UserModel::class, "SELECT * FROM users WHERE email = %s", $email)) { 
    die("No user at the e-mail address, $email");
}
echo "Username: " . $user->getUsername() . "\n";
~~~

