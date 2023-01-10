
# Using redis and the Connection Manager

Support for redis is included which allows for both, centralized storage of connection information for easy maintainability across multiple server instances, and the ability to maintain a pool of read-only database connections which load is distributed amongst evenly in round robin fashion.


## Loadding Connections From redis

Once you have the connection information saved within redis (see below), instead of passing connection parameters to the database driver each time, you can now just pass a redis instance as the third `$redis` argument.  For example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use redis;

// Connect to redis
$redis = new redis();
$redis->connect('127.0.0.1', 6379, 2);
$redis->auth('redis_password');

// Connect to database
$db = new mySQL([], [], $redis);

// Continue with request
$db->query("SELECT * FROM users");
~~~


As you can see above, both sets of connection parameters were left blank, and instead the third argument passed the redis connection.  The necessary connection information will be retrived from redis, and in the case of multiple read-only connections, they will be rotated in round robin fashion with each connection.


## dbmgr Command Line Tool

You first need to save the connection information into redis which can be done via the `Apex\Db\ConnectionManager` class, or the `dbmgr` command line tool that is included.  You may run `dbmgr` with:

`./vendor/bin/dbmgr set-master`

This will prompt you for your database information.  Simply run `dbmgr` with the `help` option for a list of all available commands.


## Connection Manager

You may also save and manage the connection information within redis through the `Apex\Db\ConnectionManager` class, and for example:

~~~php
use Apex\Db\ConnectionManager;
use redis;

// Connect to redis
$redis = new redis();
$redis->connect('127.0.0.1', 6379, 2);
$redis->auth('redis_password');

// Start manager
$manager = new ConnectionManager($redis);

// Save master (write access) connection info
$manager->addDatabase('write', [
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password']
);

// Save a read-only connection
$manager->addDatabase('read', [
    'dbname' => 'readonly', 
    'user' => 'reader', 
    'password' => 'password', 
    'host' => '192.168.0.24'
], 'db3');
~~~

This class supports the following methods:

#### `addDatabase(string $type, array $params, string $alias = '')`
Adds a new database connection to redis.  The `$type` must be either "write" or "read".  Only one write connection is allowed, while multiple "read" connections may be added.  If adding a read connection, you must also specify the `$alias` which is a simple alpha-numeric identifier (eg. db3, nyc2, et al).

#### `deleteDatabase(string $alias)`
Will delete the specified read-only database connection from redis.

#### `array getDatabase(string $type, string $alias = ''`
Will retrive the connection information for the specified connection.


#### `array listReadonly()`
Returns an array of associative arrays, listing all read-only database connections currently within redis. 

#### `deleteAll()`
Will delete all database connection information from redis.



