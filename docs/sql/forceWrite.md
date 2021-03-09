
# forceWrite

**Description:** For the next SQL statement(s) to the write connection, avoiding the read-only connection if it's available.

> `void $db->forceWrite(bool $always = false)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$always~ | No | bool | Whether or not to force all future SQL statements during this request to use the write connection.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Force write for next statement
$db->forceWrite();
~~~

