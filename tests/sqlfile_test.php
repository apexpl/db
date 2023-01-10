<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class sqlfile_test extends TestCase
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
    public function test_sqlfile()
    {

        // Connect
        $db = getTestConnection();

        // Import SQL file
        $db->executeSqlFile(__DIR__ . '/import.sql');
        $this->assertTrue($db->checkTable('test_import'));

        $users = $db->getColumn("SELECT username FROM test_import");
        $this->assertCount(4, $users);
        $this->assertContains('brad', $users);

        // Drop table
        $db->closeCursors();
        $db->query("DROP TABLE test_import");
    }

}


