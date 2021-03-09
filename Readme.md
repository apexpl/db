
# ADL - Apex Database Layer

A lightweight database layer designed for simplicity and ease of use, providing a middle ground between larger ORMs and base PHP functions (ie. mysqli_*).  Supports multiple database engines, object mapping, connection load balancing, and an optional wrapper allowing methods to be accessed statically.  It supports:

* Supports mySQL, PostgreSQL, and SQLite with ability to easily implement other engines.
* Automated mapping to / from objects.
* Automated preparing of ALL sql queries to protect against SQL injection.
* Typed, sequential and named placeholders
* Optional secondary read-only connection parameters, which automatically switch to write connection when necessary SQL query is executed.
* Optional redis support with connection manager allowing both, easy maintainability of connection information across multiple server instances, and multiple read-only connections with automated load balancing via round robin.
* Command line tool (and PHP class) to easily manage connection information within redis.
* Optional built-in support for [Apex Debugger](https://github.com/apexpl/debugger) which will log all SQL queries executed during a request into the debug session for later analysis.
* Wrapper class allowing methods to be accessed statically for improved efficiency and simplicity.


## Installation

Install via Composer with:

> `composer require apex/db`


## Table of Contents

1. [SQL Database Methods](https://github.com/apexpl/db/blob/master/docs/sql.md)
    1. [Database Connections](https://github.com/apexpl/db/blob/master/docs/connections.md)
    2. [Placeholders](https://github.com/apexpl/db/blob/master/docs/placeholders.md)
    3. [Object Mapping](https://github.com/apexpl/db/blob/master/docs/object_mapping.md)
    4. [SQL Parser for Large SQL Files](https://github.com/apexpl/db/blob/master/docs/sql_parser.md)
2. [Using redis and the Connection Manager](https://github.com/apexpl/db/blob/master/docs/connect_mgr.md)
3. [Utilizing Apex Debugger](https://github.com/apexpl/db/blob/master/docs/debugger.md)
4. [Db Wrapper for Statically Accessing Methods](https://github.com/apexpl/db/blob/master/docs/static_wrapper.md) 


## Basic Usage

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Insert a record
$db->insert('users', [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@domain.com'
);
$userid = $db->insertId();

// Get single user by id#
if (!$profile = $db->getIdRow('users', $userid)) { 
    die("No user at id# $userid");
}

// Get single field
if (!$email = $db->getField("SELECT email FROM users WHERE id = %i", $userid)) { 
    die("No e-mail exists for user id# $userid");
}
echo "E-mail is: $email\n";

// Go through all users with @domain.com e-mail
$domain = '@domain.com';
$rows = $db->query("SELECT * FROM users WHERE email LIKE %ls", $domain);
foreach ($rows as $row) { 
    echo "Found: $row[username] - $row[full_name]\n";
}
~~~


## Object Mapping

Allows mapping to and from objects with ease by simply passing the objects to write methods, and one static call to map results to an object.  For example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Mapper\ToInstance;
use MyApp\Models\UserModel;

$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);

// Get users
$rows = $db->"SELECT * FROM users WHERE group_id = 2");
foreach ($rows as $row) {
    $user = ToInstance::map(UserModel::class, $row);
    // $user is a UserModel object, injected and instantiated
}

// Get specific user
$userid = 5811;
$row = $db->getIdRow('users', $userid);    /// $user  is a UserModel object
$user = ToInstance::map(UserModel::class, $row);

// Insert new user
$user = new UserModel($my, $constructor, $params);
$db->insert('users', $user);

// Unsure if inserting or updating?  No problem.
$db->insertOrUpdate('users', $user);
~~~


## Follow Apex

Loads of good things coming in the near future including new quality open source packages, more advanced articles / tutorials that go over down to earth useful topics, et al.  Stay informed by joining the <a href="https://apexpl.io/">mailing list</a> on our web site, or follow along on Twitter at <a href="https://twitter.com/mdizak1">@mdizak1</a>.



