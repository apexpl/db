<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use \Doctrine\ORM\Tools\Setup;
use \Doctrine\ORM\EntityManager;
use Apex\Db\Exceptions\DbWrapperException;
use Apex\Db\Interfaces\DbInterface;
use \PDO;


/**
 * Wrapper initialization class for Doctrine.
 */
class Doctrine 
{

    /**
     * Init DOctrine instance
     */
    public static function init(DbInterface $db, array $entityPaths = [], array $opts = []):EntityManager
    {

        // Set default options
        $isDevMode = $opts['isDevMode'] ?? false;
        $proxyDir = $opts['proxyDir'] ?? null;
        $cache = $opts['cache'] ?? null;
        $useSimpleAnnotationReader = $opts['useSimpleAnnotationReader'] ?? null;

        // Create config
        $config = Setup::createAnnotationMetadataConfiguration($entityPaths, $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);
        $conn_opts = ['pdo' => $db->connect_mgr->getConnection('write')];

        // Init and return
        return EntityManager::create($conn_opts, $config);
    }

    /**
     * Import
     */
    public static function import(DbInterface $db, EntityManager $manager)
    {

        // Get PDO object
        $pdo = $manager->getConnection()->getWrappedConnection();
        if (!$pdo instanceof \PDO) { 
            throw new DbWrapperException("Unable to import Doctrine instance, as did not get a PDO instance.  Got a " . $pdo::class . " instance instead.");
        }

        // Import connection
        $db->connect_mgr->importConnection($pdo);
    }

}



