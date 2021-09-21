
# dropAllTables

**Description:** Drop all tables within the database in correct order so as to not receive foreign key constraint errors.

> `void $db->dropAllTables()`

**Return Values:** Returns nothing.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Drop all tables
$db->dropAllTables();
~~~


