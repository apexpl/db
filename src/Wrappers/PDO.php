<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use Apex\Db\Interfaces\DbInterface;

/**
 * PDO wrapper
 */
class PDO
{

    /**
     * Get PDO instance
     */
    public static function init(DbInterface $db, string $conn_type = 'write'):\PDO
    {
        return $db->connect_mgr->getConnection($conn_type);
    }

}


