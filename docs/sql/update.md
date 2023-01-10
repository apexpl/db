
# update

**Description:** Update one or more records in a table.

> `void $db->update(string $table_name, array | object $values, [string $where_clause], [iterable $args])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the database table to update.
`$values` | Yes | array | object | Either an associative array of values to update, or an object with updated property values to use.
`$where_clause` | No | string | Only applicable if `$values` is an array, and allows you to define an optional `WHERE` clause for the SQL statement, defining exactly which record(s) to update.
`$args` | No | iterable | The values of the placeholders within the where clause.  See the [Placeholders](../placeholders.md) page for details.


**NOTE:** If the `$values` is an object, it must contain a `$id` property, and the database table must also contain an `id` column.  This is the record within the database that will be updated.


#### Example - Associative Array

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

$userid = 5811;

// Update
$db->update('users', 
    'full_name' => 'Johnathan Smith', 
    'status' => 'inactive'], 
"id = %i", $userid);
~~~

The above example would update two columns within the `users` table of the reocrd id# 5811.


#### Example - Object

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect
$db = new mySQL($connect_params);

// Load user, update e-mail
$user = new UserModel(3166);
$user->setEmail('new.email@domain.com');

// Update database
$db->update('users', $user);
~~~



