
# Object Mapping

ADL fully supports easy mapping to and from objects for both, retrieving records from the database, and inserting / updating / deleting  records.  There is no entity mapping per-se, and instead you simply call one static method to map database records to an object, while the insert / update / delete methods will accept any object.


## Retrieving Objects as Records

You may map records retrived from the database into objects by simply calling the `ToInstance::map()` method, for example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Mapper\ToInstance;
use MyApp\Models\UserModel;

$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
'   password' => 'mydb_password']
]);

// Get all users
$group_id = 2;
$rows = $db->query("SELECT * FROM users WHERE group_id = %i", $group_id);
foreach ($rows as $row) {

    // Map row to object
    $user = ToInstance::map(userModel::class, $row);  // $user is a UserModel object instance, instantiated and injected

}

// Get single by id#
$row = $db->getIdRow('users', 5811);  // $user is a UserModel object of the user id# 5811
$user = ToInstance::map(USERModel::class, $row);

// Get single row
$username = 'johndoe';
$row = $db->getRow("SELECT * FROM users WHERE username = %s", $username);
$user = ToInstance::map(userModel::class, $row);
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


