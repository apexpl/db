
# SQL Database Connections

Connection information is always passed in the constructor of the database drivers, although a connection is not established until the first SQL query is executed.  Connection parameters are passed as an associative array, which contains five elements:

Key | Type | Description
------------- |------------- |------------- 
dbname | string | The database name.
user | string | The database username.
password | string | The database password.
host | string | The database port.  Defaults to "localhost".
port | int | The database port.  Defaults to 3306 for mySQL, and 5432 for PostgreSQL.

The constructor of each driver accepts two sets of connection parameters, the primary connection, and an optional read-only connection.  If the read-only connection is defined, it will be used as the default connection for all SQL queries until a write query (insert, update, delete) is executed, at which time it will automatically switch to the primary write connection.


## mySQL Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Single connection
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);


// With read-only connection
$db = new mySQL(
    ['host' => '192.168.0.12', 'dbname' => 'mydb', 'user' => 'myuser', 'password' => 'secret'], 
    ['host' => '192.168.0.8', 'dbname' => 'readonly', 'user' => 'reader', 'password' => 'mydb_pass']
);
~~~

The first example is a standard connection that you would typically make. and As you will see in the second example, a second read-only set of connection parameters is also passed.  This means the driver will connect using the read-only parameters at first, then if and when a write SQL query is executed, it will switch over to the primary connection parameters.


## PostgreSQL Example

Exactly the same as mySQL, except for the class name being used:

~~~php
use Apex\Db\Drivers\PostgreSQL\PostgreSQL;

$db = new PostgreSQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);
~~~


## SQLite Example

With SQLite, the only required connection parameter is `dbname`, which is the full path to the SQLite database file.  If the file does not already exist, it will be created.

~~~php
use Apex\Db\Drivers\SQLite\SQLite;

$db = new SQLite([
    'dbname' => '/path/to/app.db'
]);
~~~






