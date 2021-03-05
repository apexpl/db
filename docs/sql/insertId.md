
# insertId

**Description:** Gets the id# of the last inserted row.

> `int $db->insertId()`

**Return Values:** The unique id# of the last inserted row.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Insert user
$db->insert('users', [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@domain.com']
);

// Get id# of new user
$userid = $db->insertId();
echo "New user id# $userid\n";
~~~


