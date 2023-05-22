<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use \Doctrine\ORM\Tools\Setup;
use \Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;
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
        $useSimpleAttributeReader = $opts['useSimpleAttributeReader'] ?? null;

        // Create config
        //$config = Setup::createAnnotationMetadataConfiguration($entityPaths, $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);
        $config = Setup::createAttributeMetadataConfiguration($entityPaths, $isDevMode, $proxyDir, $cache, $useSimpleAttributeReader);

        // Get connection
        $conn_opts = [
            'pdo' => $db->connect_mgr->getConnection('write'),
            'driver' => 'pdo_mysql'
        ];
        $connection = DriverManager::getConnection($conn_opts);

        // Init and return
        $manager = new EntityManager($connection, $config);
        return $manager;
    }

    /**
     * Import
     */
    public static function import(DbInterface $db, EntityManager $manager):DbInterface
    {

        // Get PDO object
        $pdo = $manager->getConnection()->getParams()['pdo'];
        if (!$pdo instanceof \PDO) { 
            throw new DbWrapperException("Unable to import Doctrine instance, as did not get a PDO instance.  Got a " . $pdo::class . " instance instead.");
        }

        // Import connection
        $db->connect_mgr->importConnection($pdo);
        return $db;
    }

}



