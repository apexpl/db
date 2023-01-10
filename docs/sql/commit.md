
# commit

**Description:** Commit a database transaction.  Not applicable for SQLite databases.

> `voi $db->commit()`

#### Example

~~php
use Apex\Db\Drivers\mySQL\mySQL;

$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Begin transaction
$db->beginTransaction();

// Insert rows
$db->insert('tblname', ['col1' => 'val1', 'col2' => 'val2']);
$db->insert('tblname2', ['col1' => 'val1', 'col2' => 'val2']);
$db->insert('tblname3', ['col1' => 'val1', 'col2' => 'val2']);

// Commit transaction
$db->commit();
~~~


