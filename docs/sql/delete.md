
# delete

**Description:** Delete one or more records from a database table.  

> `void $db->delete(string $table_name, [object $object | string $where_cluase, [iterable $args]])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The table name to delete from.
`$object` / `$where_clause` | No | object / string | This can either be an object if you wish to delete that specific object from the database, or a string that acts as the `WHERE` clause.
`$args` | No | iterable | The values of the placeholders within the WHERE clause, only applicable of not deleting an object.  See the [Placeholders](../placeholders.md) page for details.

Please note, if deleting an object the object must have a `$id` propertiy, and the database table must have a `id` column.  This is the record that will be deleted.


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

// Delete
$group_id = 2;
$db->delete('users', 'group_id = %i', $group_id);


// Delete object
$user = new UserModel(1855);
$db->delete('users', $user);
~~~



