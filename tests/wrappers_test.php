<?php
declare(strict_types = 1);

use Apex\Db\Wrappers\{Doctrine, Eloquent};
use Apex\Db\Drivers\mySQL\mySQL;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Apex\Db\Wrappers\Eloquent\Manager;

/**
 * Wrappers test
 */
class wrappers_test extends TestCase
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
    public function test_tables()
    {

        // Connect
        $db = getTestConnection();


        // Doctrine
        $doctrine = Doctrine::init($db);
        $this->assertEquals(\Doctrine\ORM\EntityManager::class, $doctrine::class);
        $methods = get_class_methods($doctrine);
        $this->assertCount(41, $methods);
        $this->assertContains('getClassMetadata', $methods);
        $this->assertContains('getFilters', $methods);
        $this->assertContains('flush', $methods);

        // Import Doctrine
        $db2 = new mySQL();
        Doctrine::import($db2, $doctrine);
        $m = get_class_methods($db2);
        $this->assertContains('getPrimaryKey', $m);
        $this->assertContains('getObject', $m);

        // Eloquent
        $e = Eloquent::init($db);
        $this->assertEquals(Apex\Db\Wrappers\Eloquent\Manager::class, $e::class);
        $m = get_class_methods($e);
        $this->assertCount(16, $m);
        $this->assertContains('getContainer', $m);
        $this->assertContains('getDatabaseManager', $m);

        // Import Eloquent
        $db2 = new mySQL();
        Eloquent::import($db2, $e);
        $m = get_class_methods($db2);
        $this->assertContains('getPrimaryKey', $m);
        $this->assertContains('getObject', $m);


        // PDO
        $pdo = \Apex\Db\Wrappers\PDO::init($db);
        $this->assertEquals(PDO::class, $pdo::class);

        $db->closeCursors();
    }

}


