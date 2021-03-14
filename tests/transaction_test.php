<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class transactiontest extends TestCase
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
    public function test_transaction()
    {

        // Connect
        $db = getTestConnection();

        // Check for SQLite
        if ($_SERVER['test_sql_driver'] == 'sqlite') { 
            $this->assertTrue(true);
            return;
        }

        // Drop test tables
        $db->query("DROP TABLE IF EXISTS test_users");
        $db->query("DROP TABLE IF EXISTS test_orders");

        // Create test tables
        $db->query("CREATE TABLE test_users (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, username VARCHAR(100) NOT NULL)");
        $db->query("CREATE TABLE test_orders (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, userid INT NOT NULL, amount DECIMAL(12,2) NOT NULL)");

        // Begin transaction
        $db->beginTransaction(true);
        $db->insert('test_users', ['username' => 'jsmith']);
        $row = $db->getRow("SELECT * FROM test_users WHERE username = %s", 'jsmith');
        $this->assertIsArray($row);
        $this->assertArrayHasKey('username', $row);
        $this->assertEquals('jsmith', $row['username']);

        // Rollback
        $db->rollback();
        $row = $db->getRow("SELECT * FROM test_users WHERE username = %s", 'jsmith');
        $this->assertNull($row);


        // Degin and commit
        $db->beginTransaction();
        $db->insert('test_users', ['username' => 'mike']);
        $userid = $db->insertId();
        $this->assertGreaterThan(0, $userid);

        // ADd order
        $db->insert('test_orders', ['userid' => $userid, 'amount' => 15]);
        $db->commit();
        $row = $db->getRow("SELECT * FROM test_users WHERE username = %s", 'mike');
        $this->assertIsArray($row);
        $this->assertEquals($userid, $row['id']);

        // Get orders
        $order = $db->getRow("SELECT * FROM test_orders WHERE userid = %i", $userid);
        $this->assertIsArray($order);
        $this->assertEquals($userid, $order['userid']);
        $this->assertEquals(15.00, $order['amount']);

        // Delete tables
        $db->query("DROP TABLE IF EXISTS test_orders");
        $db->query("DROP TABLE IF EXISTS test_users");
    }

}



