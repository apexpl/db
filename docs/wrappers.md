
# Initialization Wrappers for Doctrine, Eloquent, and PDO

ADL includes initialization wrappers allowing you to instantly turn any database instance into an instance of Doctrine, Eloquent or PDO.  This is beneficial if multiple people are on the same project, as it allows developers to use their ORM of choice.


## Using Doctrine

You may convert any `DbInterface` object into a Doctrine `EntityManager` object with the following code:

~~~php
use Apex\Db\Drivers\mySQL\mYSQL;

// Connect as normal
$db = new mySQL($connect_params);

// Convert to Doctrine
$doctrine = Apex\Db\Wrappers\Doctrine::init($db);
~~~


That's it.  The above `init()` method allows the following parameters:

Variable | Required | Type | Description
------------- |------------- |------------- |------------- 
`$db` | Yes | DbInterface | A database object created by one of the ADL drivers.
`$entityPaths` | No | array | Optional entity paths to create Doctrine instance with.
`$opts` | No | array | Additional optional configuration variables.  This array allows for the keys:  `isDevMode`, `proxyDir`, `cache` and `useSimpleAnnotationReader`.


## Using Eloquent

You can convert any `DbInterface` object into an Eloquent in much the same way, and for example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect as normal
$db = new mySQL($connect_params);

// Convert to Eloquent
$eloquent = \Apex\Db\Wrappers\Eloquent::init($db);
~~~


## Using PDO

Although ADL does use PDO connection objects within its drivers, you may easily get the actul PDO connection with the below code:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect as normal
$db = new mySQL($connect_params);

// Get PDO connection
$pdo = \Apex\Db\Wrappers\PDO::init($db);
~~~


## Importing Connections

All three wrappers also contain a static `import()` method allowing you to do the reverse, and import a Doctrine / Eloquent / PDO connection into ADL.  This may be useful if using the <a href="https://github.com/apexpl/armor">Apex Armor</a> package or similar, and you wish to take advantage of the package while remaining within your preferred ORM.  For example:

~~~php
use Apex\Db\Drivers\PostgreSQL\PostgreSQL;
use Apex\Db\Wrappers\Doctrine;
use \Doctrine\ORM\EntityManager;

// Instance of Doctrine EntityManager
$manager = ...;

// Import into ADL
$db = new PostgreSQL();
Doctrine::import($db, $manager);
~~~

That's it, and the `$db` instance will now have the Doctrine database connection imported into it, and ready for use.


