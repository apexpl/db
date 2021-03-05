
# Flat File Databases (JSON and CSV)

There is support for JSON databases via the SleekDB package, and CSV files via the league/csv package.  Running either is as simple as passing a directory name to the constructor of the drivers.  For example, to manage a JSON database:

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

$db = new SleekDB('/path/to/data');

// Insert
$userid = $db->insert('users', [
    'username' =>' jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@gmail.com']
);

// Get row
$row = $db->selectById('users', $userid);
print_r($row);
~~~

Using CSV is the exact same, except change the driver being used:

~~~php
use Apex\Db\Drivers\CSV\CSV;

$db = new CSV(
    datadir: '/path/to/data'
);

$db->insert('users', [
    'username' => 'jsmith', 
    'full_name' => 'John Smith', 
    'email' => 'jsmith@gmail.com']
);
~~~

**NOTE:** As of this writing, the CSV implementation does not support updating or deleting records.  I apologize, and didn't realize the league/csv package didn't support this functionality until everything else was done.  I will leave it as is for now, and come back to it later.


## Available Methods

The following methods are available to all flat file database drivers:

* [__construct()](./flatfile/construct.md)
* [createTable()](./flatfile/createTable.md)
* [dropTable()](./flatfile/dropTable.md)
* [insert()](./flatfile/insert.md)
* [insertMany()](./flatfile/insertMany.md)
* [select()](./flatfile/select.md)
* [selectAll()](./flatfile/selectAll.md)
* [selectById()](./flatfile/selectById.md)
* [update()](./flatfile/update.md)
* [updateById()](./flatfile/updateById.md)
* [delete()](./flatfile/delete.md)
* [deleteById()](./flatfile/deleteById.md)



