
# __construct

**Description:** Constructor -- initiates a new database instance.

> `object new SleekDB(string $datadir)`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$datadir` | Yes | string | Full path to directory to store all flat files in.


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);
~~~


