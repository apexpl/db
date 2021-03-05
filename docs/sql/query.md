
# query

**Description:** Perform a SQL query on the database.

> `SqlQueryResult $db->query([string $map_class], string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$map_class` | No | string | Optional full class name, and if present all records returned from this query will be automatically mapped to instances of this class.  See the [Object Mapping](../object_mapping.md) page for details.
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:**  A `SqlQueryResult` object, which is a custom iterator that can be traversed using `foreach` and `while` loops as normal.  Each element will either be an array or an object if you passed a `$map_class` to the method.


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
$users = $db->query(userModel::class, "SELECT * FROM users WHERE status = %s AND group_id = %i", $status, $group_id);
foreach ($users as $user) { 
    echo "Username: " . $user->getUsername() . "\n";
    echo "E-Mail: " . $user->getEmail() . "\n";
}

// Update
$db->query("UPDATE users SET email = %s WHERE id = %i", 'new.email@domain.com', 3814);
~~~



