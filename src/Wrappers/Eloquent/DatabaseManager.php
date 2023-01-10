<?php
namespace Apex\Db\Wrappers\Eloquent;

use Illuminate\Database\DatabaseManager as EloquentDatabaseManager;
use Illuminate\Database\MySqlConnection;

/**
 * Class DatabaseManager
 * @package Apex\Db\Wrappers\Eloquent
 */
class DatabaseManager extends EloquentDatabaseManager
{

	/**
	 * @param \PDO $pdo
	 */
	public function addDefaultConnection(\PDO $pdo)
	{
		$this->connections['default'] = new MySqlConnection($pdo);
	}
}
?>