<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * Placeholder tests
 */
class placeholders_test extends TestCase
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
    public function test_placeholders()
    {

        // Connect
        $db = getTestConnection();
        $conn = $db->connect_mgr->getConnection('read');

        // Typed placeholders
        $args = ["SELECT * FROM users WHERE status = %s AND group_id = %i", 'active', 2];
        $sql = array_shift($args);
        list($sql, $raw_sql, $values) = $db->formatter::stmt($conn, $sql, $args);
        if ($_SERVER['test_sql_driver'] == 'sqlite') { 
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        } else { 
            $this->assertEquals("SELECT * FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT * FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        }
        $this->assertEquals('active', $values[0]);
        $this->assertEquals('2', $values[1]);

        // Named placeholders
        $args = ["SELECT * FROM users WHERE status = {status} AND group_id = {group_id}", ['status' => 'active', 'group_id' => 2]];
        $sql = array_shift($args); 
        list($sql, $raw_sql, $values) = $db->formatter::stmt($conn, $sql, $args);
        if ($_SERVER['test_sql_driver'] == 'sqlite') { 
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        } else { 
            $this->assertEquals("SELECT * FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT * FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        }
        $this->assertEquals('active', $values[0]);
        $this->assertEquals('2', $values[1]);

        // Sequential placeholders
        $args = ["SELECT * FROM users WHERE status = {1} AND group_id = {2}", 'active', 2];
        $sql = array_shift($args);
        list($sql, $raw_sql, $values) = $db->formatter::stmt($conn, $sql, $args);
        if ($_SERVER['test_sql_driver'] == 'sqlite') { 
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT rowid,* FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        } else { 
            $this->assertEquals("SELECT * FROM users WHERE status = ? AND group_id = ?", $sql);
            $this->assertEquals("SELECT * FROM users WHERE status = 'active' AND group_id = '2'", $raw_sql);
        }
        $this->assertEquals('active', $values[0]);
        $this->assertEquals('2', $values[1]);

    }

}


