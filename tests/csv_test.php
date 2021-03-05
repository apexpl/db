<?php
declare(strict_types = 1);

use Apex\Db\Drivers\CSV\CSV;
use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class csv_test extends TestCase
{

    /**
     * Setup
     */
    public function setUp():void
    {
        require_once(__DIR__ . '/utils.php');

        $datadir = __DIR__ . '/data';
        system("rm -rf $datadir");
        mkdir($datadir);
    }

    /**
     * Test tables
     */
    public function test_objects()
    {

        // Init
        $db = new CSV(__DIR__ . '/data');

        // Create table
        $db->createTable('users', ['username', 'group_id', 'email']);

        // Insert
        $id = $db->insert('users', ['username' => 'jsmith', 'group_id' => 2, 'email' => 'jsmith@domain.com']);
        $row = $db->selectById('users', $id);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('email', $row);
        $this->assertEquals('jsmith@domain.com', $row['email']);
        $this->assertEquals(2, $row['group_id']);

        // Insert many
        $new_users = [
            ['username' => 'mike', 'group_id' => 1, 'email' => 'mike@domain.com'], 
            ['username' => 'alex', 'group_id' => 2, 'email' => 'alex@domain.com']
        ];
        $db->insertMany('users', $new_users);

        // Select all
        $rows = $db->selectAll('users');
        $this->assertCount(3, $rows);

        // Select
        $rows = $db->select('users', ['group_id =~ 2'], 'username');
        $this->assertCount(2, $rows);

    }

}



