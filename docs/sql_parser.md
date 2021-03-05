
# SQL Parser for Large SQL Files

ADL comes bundled with the `Apex\Db\Drivers\SqlParser` class, developed by Principe Orazio (orazio.principe@gmail.com).  This is an excellent and easy to use class that makes parsing of large SQL files very simple and straight forward.

For example:


~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Import SQL file
$sql_file = '/path/to/import.sql';
$this->executeSqlFile($sql_file);
~~~

That's all there is to it, and you can now easily execute large SQL files against your database without worry.


## Manually Using SqlParser

If preferred, you may manually parse a SQL file using the SqlParser class, for example:

~~~php
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Drivers\SqlParser;

// Connect
$db = new mySQL($connect_params);

// Parse SQL file
$sql_code = file_get_contents('/path/to/file.sql');
$sql_lines = SqlParser::parse($sql_code);

// Execute all lines
foreach ($sql_lines as $sql) { 
    $db->query($sql);
}
~~~



