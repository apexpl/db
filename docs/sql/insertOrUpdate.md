
# insertOrUpdate

**Description:** Insert a record into a database table, or if record already exists, update it accordingly.

> `void $db->insertOrUpdate(string $table_name, array | object $values, [string $where_clause], [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to update.
`$values` | Yes | array | object | Either an associative array of values to insert / update, or an object with updated property values to use.
`$where_clause` | No | string | Only applicable if `$values` is an array, and allows you to define an optional `WHERE` clause for the SQL statement, defining exactly which record(s) to update.
`$args` | No | iterable | The values of the placeholders within the where clause.  See the [Placeholders](../placeholders.md) page for details.


**NOTE:** If the `$values` is an object, it must contain a `$id` property, and the database table must also contain an `id` column.  This is the record within the database that will be inserted / updated.  However, the `$id` property can be left at 0, in which case a new record will be inserted with an auto incrementing id#.


#### Example - Associative Array

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);
$userid = 5811;

// Insert or update
$db->insertOrUpdate('users', 
    'id' => $userid, 
    'full_name' => 'Johnathan Smith', 
    'status' => 'inactive'], 
"id = %i", $userid);
~~~

The above example would update two columns within the `users` table of the record id# 5811.


#### Example - Object

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL($connect_params);

// Load user, update e-mail
$user = new userModel();
$user->setUsername('jsmith');
$user->setEmail('new.email@domain.com');

// Update database
$db->insertOrUpdate('users', $user);
~~~


