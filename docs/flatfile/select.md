
# select

**Description:** Select rows from a table.

> `iterable $db->select(string $table_name, array $conditions, [string $order_by = 'id'], [int $limit = 0], [int $offset = 0])`


**Parameters**

Param | Required | Type | Description
------------- |------------- |------------- |------------- 
`$table_name` | Yes | string | The name of the table to retrieve records from.
`$conditions` | Yes | array | The conditions to search for.  See below.
`$order_by` | No | string | The column to sort by.  May be suffixed with "desc" to sort in descending order (eg. "username desc").
`$limit` | No | int | The maximum number of record to return.
`$offset` | No | int | The offset of the result set.


**Return Values:** An iterable with each element being an associative array of one record.


### Conditions

The `$conditions` parameter must be a one-dimensional array, with each element being an array with three elements:

* Field name to search within.
* Operand
* Conditional value that must be met.

The following operands are supported:

Operand | Description
------------- |------------- 
`=` | Equals to
`!=` | Not equals to
`>` | Greater than
`<` | Less than
`>=` | Grather than or equal to.
`<=` | Less than or equal to.
`=~` | Contains the conditional string.
`!~` | Does not contain the conditional string.

For example:

~~~php

// All records with 'status' of active and 'group_id' of 2
$conditions = [
    'status', '=', 'active'], 
    ['group_id', '=', 2]
];

// All records with 'balance' less than 10.
$conditions = [
    ['balance', '<', 10]
];
~~~


#### Example

~~~php
use Apex\Db\Drivers\SleekDB\SleekDB;

// Connect
$db = new SleekDB(
    datadir: '/path/to/data'
);

// Get username 'grant'
$rows = $db->select('users', ['username', '=', 'grant']);
print_r($rows[0]);


// Get all users og group_id = 2, sorted by full name descending
$rows = $db->select('users', ['group_id', '=', 2], 'full_name desc');
~~~


