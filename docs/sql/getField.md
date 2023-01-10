
# getField

**Description:** Retrieve the first column of the first record returned by the SQL query.

> `string | null $db->getField(string $sql, [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$sql` | Yes | string | The SQL query to execute.
`$args` | No | iterable | The values of the placeholders within the SQL query.  See the [Placeholders](../placeholders.md) page for details.


**Return Values:** The first column of the first record retrived by the SQL query.  Returns null if no record was found.


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
if (!$username = $db->getField("SELECT username FROM users WHERE email = %s", $email)) { 
    die("No user with the e-mail address, $email");
}
echo "Username is: $username\n";
~~~



