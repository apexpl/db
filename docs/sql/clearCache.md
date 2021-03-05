
# clearCache

**Description:** Clears the cache of table and column names, which are stored within properties when retrived.  Should be called after creating new tables for example, if you need to check the table exists later in the request.

> `void $db->clearCache()`


#### Example

~~~php
use Apex\Db\Drivers\mySQL\mySQL;

// Connect
$db = new mySQL($connect_params);

// Clear cache
$db->clearCache();
~~~

