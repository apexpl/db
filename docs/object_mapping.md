
# Object Mapping

ADL fully supports easy mapping to and from objects for both, retrieving records from the database, and inserting / updating / deleting  records.  There is no entity mapping per-se, and instead you simply pass the desired class name when retrieving records, while the insert / update / delete methods will accept any object.


## Retrieving Objects as Records

To retrieve objects instead of arrays when retrieving records, you may optionally pass a full class name as the first argument to the following methods:

* [getIdRow()](./sql/getIdRow.md)
* [getRow()](./docs/getRow.md)
* [query()](./sql/query.md)

If you call any of the above methods with a full class name as the first argument, objects of that class fully instantiated and injected with the values of the records will be returned instead of arrays.  For example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
'   password' => 'mydb_password']
]);

// Get all users
$group_id = 2;
$users = $db->query(userModel::class, "SELECT * FROM users WHERE group_id = %i", $group_id);
foreach ($users as $user) { 
    // $user is a UserModel object instance, instantiated and injected
}

// Get single by id#
$user = $db->getIdRow(USERModel::class, 'users', 5811);  // $user is a UserModel object of the user id# 5811

// Get single row
$username = 'johndoe';
$user = $db->getRow(userModel::class, "SELECT * FROM users WHERE username = %s", $username);
~~~


## Writing Objects to Records

You may also pass objects instead of associative arrays when conducting an insert, update or delete to the database via the following methods:

* [insert()](./sql/insert.md)
* [insertOrUpdate()](./sql/insertOrUpdate.md)
* [update()](./sql/update.md)
* [delete()](./sql/delete.md)

The first argument to all four methods remains the same and is the name of the database table, while all following arguments can be objects instead of an array.  All properties of the object will be retrieved, and mapped to the column names of the database table.  For example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use MyApp\Models\UserModel;

// Connect to db
$db = new mySQL(['dbname' => 'mydb', 'user' => 'myuser', 'password' => 'password']);

// Set user
$user = new UserInstance('jsmith', 'John Smith');
$user->setEmail('jsmith@domain.com');

// Insert to users table
$db->insert('users', $user);

// Update jsmith user
$user->setEmail('new.email@domain.com');
$db->update('users', $user);

// Unsure if we're updating or insering?
$db->insertOrUpdate('users', $user);

// Delete user
$db->delete('users', $user);
~~~


