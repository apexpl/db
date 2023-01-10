
# getColumn

**Description:** Returns a one-dimensional column of the values of the first column returned by a SQL query.

> `array $db->getColumn(string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:** A one-dimensional array of all values of the first column returned by the SQL query.


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

// Get all e-mail addresses of group id# 2
$group_id = 2;
$emails = $db->getColumn("SELECT email FROM users WHERE group_id = %i", $group_id);
print_r($emails);
~~~


