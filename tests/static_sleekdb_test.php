<?php
declare(strict_types = 1);

use Apex\Db\Db;
use Apex\Db\Drivers\SleekDB\SleekDB;
use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class static_sleekdb_test extends TestCase
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
        $db = new SleekDB(__DIR__ . '/data');
        Db::init($db);

        // Create table
        Db::createTable('users', ['username', 'group_id', 'email']);

        // Insert
        $id = Db::insert('users', ['username' => 'jsmith', 'group_id' => 2, 'email' => 'jsmith@domain.com']);
        $row = Db::selectById('users', $id);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('email', $row);
        $this->assertEquals('jsmith@domain.com', $row['email']);
        $this->assertEquals(2, $row['group_id']);

        // Insert many
        $new_users = [
            ['username' => 'mike', 'group_id' => 1, 'email' => 'mike@domain.com'], 
            ['username' => 'alex', 'group_id' => 2, 'email' => 'alex@domain.com']
        ];
        Db::insertMany('users', $new_users);

        // Select all
        $rows = Db::selectAll('users');
        $this->assertCount(3, $rows);

        // Select
        $rows = Db::select('users', ['group_id =~ 2'], 'username');
        $this->assertCount(2, $rows);
        $this->assertEquals('alex', $rows[0]['username']);

        // Update
        Db::updateById('users', $id, ['email' => 'new.email@domain.com']);
        $row = Db::selectById('users', $id);
        $this->assertEquals('new.email@domain.com', $row['email']);
        $this->assertEquals('jsmith', $row['username']);

        // Delete
        Db::deleteById('users', $id);
        $row = Db::selectById('users', $id);
        $this->assertNull($row);

    }

}



