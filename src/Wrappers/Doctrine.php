<?php
declare(strict_types = 1);

namespace Apex\Db\Wrappers;

use \Doctrine\ORM\Tools\Setup;
use \Doctrine\ORM\EntityManager;
use \PDO;

class Doctrine {
	public static function init($conn, array $entityPaths = array(), array $opts = array()) {
		$isDevMode = (isset($opts['isDevMode']) ? $opts['isDevMode'] : false);
		$proxyDir = (isset($opts['proxyDir']) ? $opts['proxyDir'] : null);
		$cache = (isset($opts['cache']) ? $opts['cache'] : null);
		$useSimpleAnnotationReader = (isset($opts['useSimpleAnnotationReader']) ? $opts['useSimpleAnnotationReader'] : null);
		$config = Setup::createAnnotationMetadataConfiguration($entityPaths, $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);

		$conn_opts = ['pdo' => $conn];

		return EntityManager::create($conn_opts, $config);
	}
}
?>