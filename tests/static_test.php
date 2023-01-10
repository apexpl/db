<?php
declare(strict_types = 1);

use Apex\Db\Db;
use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class static_test extends TestCase
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
        Db::init($db);

        // Create test table
        Db::query("DROP TABLE IF EXISTS test_users");
        Db::query("CREATE TABLE test_users (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, status VARCHAR(10) NOT NULL, group_id INT NOT NULL, name VARCHAR(100) NOT NULL)");

        // Set rows to insert
        $rows = [
            ['status' => 'active', 'group_id' => 2, 'name' => 'Matt Dizak'], 
            ['status' => 'active', 'group_id' => 1, 'name' => 'Mike Jacobs'], 
            ['status' => 'inactive', 'group_id' => 2, 'name' => 'Brad Fritt']
        ];
        Db::insert('test_users', ...$rows);

        // Check table
        $this->assertTrue(Db::checkTable('test_users'));
        $this->assertContains('test_users', Db::getTableNames());

        // Get columns
        $columns = Db::getColumnNames('test_users', true);
        $this->assertArrayHasKey('status', $columns);
        $this->assertArrayHasKey('name', $columns);
        $col_type = $_SERVER['test_sql_driver'] == 'postgresql' ? 'character varying' : 'varchar(100)';
        $this->assertEquals($col_type, strtolower($columns['name']));

        // Get column
        $names = Db::getColumn("SELECT name FROM test_users");
        $this->assertCount(3, $names);
        $this->assertContains('Matt Dizak', $names);

        // Get field
        $name = Db::getField("SELECT name FROM test_users WHERE status = %s AND group_id = %i", 'active', 2);
        $this->assertEquals('Matt Dizak', $name);

        // GEt row
        $row = Db::getRow("SELECT * FROM test_users WHERE status = %s AND group_id = %i", 'active', 2);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Matt Dizak', $row['name']);

        // Get id row
        $row = Db::getIdRow('test_users', $row['id']);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Matt Dizak', $row['name']);

        // Update
        Db::update('test_users', [
            'status' => 'pending'], 
        "name = %s", 'Matt Dizak');

        // Get id row
        $row = Db::getIdRow('test_users', $row['id']);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('status', $row);
        $this->assertEquals('Matt Dizak', $row['name']);
        $this->assertEquals('pending', $row['status']);

        // Delete
        Db::delete('test_users', "status = %s", 'pending');
        $rows = Db::query("SELECT * FROM test_users");
        $this->assertEquals(2, $db->getSelectCount($rows));

        // Drop table
        $db->closeCursors();
        Db::query("DROP TABLE test_users");
        $this->assertTrue(Db::checkTable('test_users'));
        Db::clearCache();
        $this->assertfalse(Db::checkTable('test_users'));

    }

}


