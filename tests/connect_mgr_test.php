<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class connect_mgr_test extends TestCase
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
    public function test_objects()
    {

        // Connect
        $db = getTestConnection();
        $this->assertTrue(true);

    }

}


