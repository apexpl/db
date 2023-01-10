<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use Apex\Db\Interfaces\DbInterface;
use Apex\Db\Wrappers\Eloquent\Manager;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use \PDO;

/**
 * Initialization wrapper for Eloquent.
 */
class Eloquent 
{

    /**
     * Get an Eloquent instance.
     */
    public static function init(DbInterface $db, array $opts = []):manager
    {
        return new Manager($db->connect_mgr->getConnection('write'));
    }

    /**
     * Import
     */
    public static function import(DbInterface $db, $manager)
    {

        // Get PDO object
        $pdo = $manager->getConnection()->getRawPdo();
        if (!$pdo instanceof \PDO) { 
            throw new DbWrapperException("Unable to import Eloquent instance, as did not get a PDO instance.  Got a " . $pdo::class . " instance instead.");
        }

        // Import connection
        $db->connect_mgr->importConnection($pdo);
    }

}


