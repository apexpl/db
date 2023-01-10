
# beginTransaction()

**Description:** Begin a database transaction.  Not applicable for SQLite databases.

> `voi $db->beginTransaction(bool $force_write = false)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$force_write` | No | bool | if set to true, will force all SQL statements performed during the transaction to the write connection until a commit or rollback is executed.  Defaults to false.


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


**Parameters**

