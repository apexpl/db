<?php
declare(strict_types = 1);

use Apex\Db\ConnectionManager;
use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Drivers\PostgreSQL\PostgreSQL;
use Apex\Db\Drivers\SQLite\SQLite;
use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class redis_test extends TestCase
{

    /**
     * Setup
     */
    public function setUp():void
    {
        require_once(__DIR__ . '/utils.php');
    }

    /**
     * Test tables
     */
    public function test_redis()
    {

        // Get params
        $params = getTestParams();
        if (!isset($params['user'])) { $params['user'] = 'test'; }
        $redis = getTestRedis();

        // Set connection manager
        $manager = new ConnectionManager($redis);
        $manager->deleteAll();
        $manager->addDatabase('write', $params);

        // Check redis
        $vars = $redis->hgetall('config:db.master');
        $this->assertIsArray($vars);

        // Connect to database
        $driver = $_SERVER['test_sql_driver'];
        if ($driver == 'sqlite') { 
            $db = new SQLite([], [], $redis);
        } elseif ($driver == 'postgresql') { 
            $db = new PostgreSQL([], [], $redis);
        } else { 
            $db = new mySQL([], [], $redis);
        }

        // Create table
        $db->query("DROP TABLE IF EXISTS test_redis");
        $db->query("CREATE TABLE test_redis (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL)");

        // Test database
        $db->insert('test_redis', ['name' => 'Matt'], ['name' => 'Mike']);
        $rows = $db->query("SELECT * FROM test_redis");
        $this->assertCount(2, $rows);

        // Drop table
        $db->query("DROP TABLE test_redis");
    }

}


