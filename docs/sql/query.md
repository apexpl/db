
# query

**Description:** Perform a SQL query on the database.

> `PDOStatement $db->query(string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:**  A `PDOStatement` object, which is a custom iterator that can be traversed using `foreach` and `while` loops as normal.  Each element will either be an array or an object if you passed a `$map_class` to the method.


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

// Set variables
$group_id = 2;
$status = 'active';

// Get users as associative arrays
$rows = $db->query("SELECT * FROM users WHERE status = %s AND group_id = %i", $status, $group_id);
foreach ($rows as $row) { 
    echo "Username: " . $row['username'] . "\n";
    echo "E-Mail: " . $row['email'] . "\n";
}

// Get users as objects
$stmt = $db->query("SELECT * FROM users WHERE status = %s AND group_id = %i", $status, $group_id);
while ($user = $db->fetchObject($stmt, UserModel::class)) { 
    echo "Username: " . $user->getUsername() . "\n";
    echo "E-Mail: " . $user->getEmail() . "\n";
}

~~~



