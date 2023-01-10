
# getHash

**Description:** Returns an associative array of the first two columns returned by the SQL query.

> `array $db->getHash(string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:** An associative array of the first two columns returned by the SQL query.


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
$grouP_id = 2;

// Get all e-mail addresses by username
$usernames = $db->getHash("SELECT username,email FROM users WHERE group_id = %i", $group_id);
print_r($usernames);
~~~

