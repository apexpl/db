
# Utilizing Apex Debugger

Built-in support for the [Apex Debugger](https://github.com/apexpl/debugger) is included, which will automatically log all SQL queries to the debug session for later analysis.  To utilize this functionality, first install the debugger via Composer with:

> `composer require apex/debugger`

You then instantiate the debugger, and pass it as the fourth `$debugger` argument to the constructor of any SQL driver, for example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Debugger\Debugger;

// Start debugger
$debugger = new Debugger(3);

// Set connection params
$params = [
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
];

// Connect to db
$db = new mySQL($params, [], null, $debugger);

// Perform request...
$rows = $db->query("SELECT * FROM users");

// At the end, finish the debugger session
$debugger->finish();
~~~

With the above in place, all SQL queries will be logged at debug level 3, plus will also be added under an extra item category of "sql", both of which can be easily viewed when reviewing the debug session.  For full information on the debugger, please view the [full documentation](https://github.com/apexpl/debugger/).


