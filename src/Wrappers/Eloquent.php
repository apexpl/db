<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use Apex\Db\Wrappers\Eloquent\Manager;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

use \PDO;

class Eloquent {
	public static function init($conn, array $opts = array()) {
		return new Manager($conn);
	}
}
?>