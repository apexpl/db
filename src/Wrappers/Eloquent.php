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

}


