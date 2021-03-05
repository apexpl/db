<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class sqltest extends TestCase
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

        // Create test table
        $db->query("DROP TABLE IF EXISTS test_users");
        $db->query("CREATE TABLE test_users (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, status VARCHAR(10) NOT NULL, group_id INT NOT NULL, name VARCHAR(100) NOT NULL)");

        // Set rows to insert
        $rows = [
            ['status' => 'active', 'group_id' => 2, 'name' => 'Matt Dizak'], 
            ['status' => 'active', 'group_id' => 1, 'name' => 'Mike Jacobs'], 
            ['status' => 'inactive', 'group_id' => 2, 'name' => 'Brad Fritt']
        ];
        $db->insert('test_users', ...$rows);

        // Check table
        $this->assertTrue($db->checkTable('test_users'));
        $this->assertContains('test_users', $db->getTableNames());

        // Get columns
        $columns = $db->getColumnNames('test_users', true);
        $this->assertArrayHasKey('status', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertEquals('varchar(100)', strtolower($columns['name']));

        // Get column
        $names = $db->getColumn("SELECT name FROM test_users");
        $this->assertCount(3, $names);
        $this->assertContains('Matt Dizak', $names);

        // Get field
        $name = $db->getField("SELECT name FROM test_users WHERE status = %s AND group_id = %i", 'active', 2);
        $this->assertEquals('Matt Dizak', $name);

        // GEt row
        $row = $db->getRow("SELECT * FROM test_users WHERE status = %s AND group_id = %i", 'active', 2);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Matt Dizak', $row['name']);

        // Get id row
        $row = $db->getIdRow('test_users', $row['id']);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Matt Dizak', $row['name']);

        // Update
        $db->update('test_users', [
            'status' => 'pending'], 
        "name = %s", 'Matt Dizak');

        // Get id row
        $row = $db->getIdRow('test_users', $row['id']);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('status', $row);
        $this->assertEquals('Matt Dizak', $row['name']);
        $this->assertEquals('pending', $row['status']);

        // Delete
        $db->delete('test_users', "status = %s", 'pending');
        $rows = $db->query("SELECT * FROM test_users");
        $this->assertCount(2, $rows);

        // Drop table
        $db->query("DROP TABLE test_users");
        $this->assertTrue($db->checkTable('test_users'));
        $db->clearCache();
        $this->assertfalse($db->checkTable('test_users'));

    }

}


