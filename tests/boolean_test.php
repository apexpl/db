<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * Boolean test
 */
class boolean_test extends TestCase
{

    /**
     * Setup
     */
    public function setUp():void
    {
        require_once(__DIR__ .'/utils.php');
    }

    /**
     * Test placeholders
     */
    public function test_basic()
    {

        // Connect
        $db = getTestConnection();
        $db->query("DROP TABLE IF EXISTS test_users");
        $db->query("CREATE TABLE test_users (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, username VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL DEFAULT true, is_admin BOOLEAN NOT NULL DEFAULT false)");

        // Insert
        $db->insert('test_users', ['username' => 'jsmith']);
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'jsmith'");
        $this->assertIsArray($row);
        $this->assertEquals('jsmith', $row['username']);
        $this->assertTrue((bool) $row['is_active']);
        $this->assertFalse((bool) $row['is_admin']);
        $this->assertEquals(1, $row['is_active']);
        $this->assertEquals(0, $row['is_admin']);

        // Close
        $db->closeCursors();
    }

    /**
     * Test insert booleans
     */
    public function test_insert_booleans()
    {

        // Start
        $db = getTestConnection();
        $db->insert('test_users', [
            'username' => 'luke', 
            'is_active' => false, 
            'is_admin' => true]
        );

        // Insert
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'luke'");
        $this->assertIsArray($row);
        $this->assertEquals('luke', $row['username']);
        $this->assertFalse((bool) $row['is_active']);
        $this->assertTrue((bool) $row['is_admin']);
        $this->assertEquals(0, $row['is_active']);
        $this->assertEquals(1, $row['is_admin']);

        // Close
        $db->closeCursors();
    }

    /**
     * Test insert ints
     */
    public function test_insert_int()
    {

        // Start
        $db = getTestConnection();
        $db->insert('test_users', [
            'username' => 'grant', 
            'is_active' => 0, 
            'is_admin' => 1]
        );

        // Insert
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'grant'");
        $this->assertIsArray($row);
        $this->assertEquals('grant', $row['username']);
        $this->assertFalse((bool) $row['is_active']);
        $this->assertTrue((bool) $row['is_admin']);
        $this->assertEquals(0, $row['is_active']);
        $this->assertEquals(1, $row['is_admin']);

        // Close
        $db->closeCursors();
        $db->query("DROP TABLE test_users");
    }

}


