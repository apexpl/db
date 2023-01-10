
# Db Wrapper for Statically Accessing Methods

This package includes the `Apex\Db\Db` class which acts as a singleton and allows methods to be accessed statically, instead of passing the database object from class to class, method to method.  This is done to provide efficiency and simplicity, and also assumes you're only managing one database connection per-request.


## Usage

Simply pass any database object to the `Db::init()` method, and you can access all methods statically throughout your application.  For example:

~~~php
use Apex\Db\Db;
use Apex\Db\Drivers\mySQL\mySQL;

// Init static wrapper
Db::init(new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]));

// Begin accessing database statically
Db::insert('users', [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@domain.com']
);

// Query
$users = Db::query("SELECT * FROM users WHERE group_id = %i", 2);
foreach ($rows as $row) { 
    print_r($row);
}
~~~




