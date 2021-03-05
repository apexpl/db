
# insert

**Description:** Insert one or more records into a database table.

> `void $db->insert(string $table_name, iterable of array | object $values)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to insert records into.
`$values` | Yes | iterable | Values of the record(s) to insert, either associative array or object.  See below for details.

You may insert either a single or multiple records at one time, and may insert either associative arrays or objects.  After the first `$table_name` argument, you may pass as many associative arrays or objects you would like as arguments, each being a new record to insert.

#### Example - Single Record, Associative Array

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

$db = new mySQL($connect_params);

// Insert
$db->insert('users', [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@domain.com']
);
~~~


#### Example - Single Record, Object

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL($connect_params);

// Create object
$user = new UserModel('jsmith', 'John Smith');
$user->setEmail('jsmith@domain.com');

// Insert
$db->insert('users', $user);
~~~


#### Example - Multiple Records, Associative Arrays

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Set different arrays, one for each record
$user1 = ['username' => 'jsmith', 'full_name' => 'John Smith', 'email' => 'jsmith@domain.com'];
$user2 = ['username' => 'mjacobs', 'full_name' => 'Mike Jacobs', 'email' => 'mike@domain.com'];
$user3 = ['username' => 'leanne', 'full_name' => 'Leanne Bristol', 'email' => 'lbristol@domain.com'];

// Insert three records
$db->insert('users', $user1, $user2, $user3);
~~~


#### Example - Multiple Records, Objects

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL($connect_params);

// Create object instances
$user1 = new UserModel('jsmith', 'John Smith');
$user2 = new UserModel('mike', 'Mike Jacobs');
$user3 = new UserModel('melissa', 'Melissa Collins');

// Insert three objects
$db->insert('users', $user1, $user2, $user3);
~~~


#### Example - Multiple Records, Associative Arrays as Iterable

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Set three records, one array
$users = [
    ['username' => 'jsmith', 'full_name' => 'John Smith', 'email' => 'jsmith@domain.com'], 
    ['username' => 'mjacobs', 'full_name' => 'Mike Jacobs', 'email' => 'mike@domain.com'], 
    ['username' => 'leanne', 'full_name' => 'Leanne Bristol', 'email' => 'lbristol@domain.com']
];

// Insert three records
$db->insert('users', ...$users);
~~~

If you note above, adding three dots "..." to the beginning of `$users` when passing the argument turns it from an array into an iterable.


#### Example - Multiple Records, Objects, Iterable

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL($connect_params);

// Create object instances
$users = [
    new UserModel('jsmith', 'John Smith'), 
    new UserModel('mike', 'Mike Jacobs'), 
    new UserModel('melissa', 'Melissa Collins')
];

// Insert three objects
$db->insert('users', ...$users);
~~~

If you note above, adding three dots "..." to the beginning of `$users` when passing the argument turns it from an array into an iterable.


