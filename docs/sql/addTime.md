
# addTime

**Description:** Adds a time interval to any given time stamp.

> `string $db->addTime(string $period, int $length, [string $from_date = ''], [bool $return_timestamp = true])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$period` | Yes | string | The interval period to add.  Must be one of :  second, minute, hour, day, week, month, quarter, year
`$length` | Yes | int | The number of the interval period to add.
`$from_date` | No | string | The starting date to add to, formatted as YYYY-MM-DD HH:II:SS.  If left blank, current date will be used.
`$return_timestamp` | No | bool | Whether or not to return full timestamp formatted in YYYY-MM-DD HH:II:SS.  If set to false, will return UNIX timestamp in seconds.

**Return Values:** Either the timestamp of the new date, or a UNIX timestamp in seconds if `$return_timestamp` is set to false.


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL([
    'dbname' => 'mydb', 
    'user' => 'myuser', 
    'password' => 'mydb_password'
]);


// Add 8 days to current date
$newdate = $db->addTime('day', 8);
~~~



