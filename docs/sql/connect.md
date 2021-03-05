
# connect

**Description:** Establish a connection with the database.  You should never run this function manually, and instead pass connection parameters to the [constructor](construct.md).

> `object $db->connect(string $dbname, string $user, string $password, string $localhost, int $port)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$dbname` | Yes | string | The name of the database.  If SQLite, the full path to the database file.
`$user` | No | string | Database username.  Required unless using SQLite.
`$password` | No | string | Database password.
`$host` | No | string | Database host, defaults to "localhost".  Not applicable for SQLite.
`$port` | No | int | Database port, defaults to 3306 for mySQL and 5432 for PostgreSQL.  Not applicable for SQLite.

**Return Values:**  The database connection object.

**NOTE:** Again, you should never use this method.  Instead, pass your connection parameters to the [constructor](construct.md), and the driver will call this method as necessary when a connection needs to be established.



