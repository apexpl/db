<?php

use Apex\Db\Drivers\mySQL\mySQL;
use Apex\Db\Drivers\PostgreSQL\PostgreSQL;
use Apex\Db\Drivers\SQLite\SQLite;

/**
 * Get test connection
 */
function getTestConnection()
{

    // Get params
    $params = getTestParams();

    // Connect
    $driver = $_SERVER['test_sql_driver'];
    if ($driver == 'sqlite') { 
        $db = new SQLite($params);
    } elseif ($driver == 'postgresql') { 
        $db = new PostgreSQL($params);
    } else { 
        $db = new mySQL($params);
    }

    // Return
    return $db;

}


/**
 * Get connection params
 */
function getTestParams()
{


    $driver = $_SERVER['test_sql_driver'];
    if ($driver == 'sqlite') { 
        return ['dbname' => __DIR__ . '/../test.db'];
    }

    // Set params
    $params = [];
    $parts = explode(',', $_SERVER['test_connection_' . $driver]);
    foreach ($parts as $part) { 
        list($key, $value) = explode('=', $part);
        $params[trim($key)] = trim($value);
    }

    // Return
    return $params;
}


/**
 * Get redis
 */
function getTestRedis()
{

    require_once(__DIR__ . '/../redis.conf');

    // Connect
    $redis = new redis();
    $redis->connect(REDIS_HOST, REDIS_PORT, 2);
    if (REDIS_PASSWORD != '') { 
        $redis->auth(REDIS_PASSWORD);
    }
    if (REDIS_DBINDEX != 0) { 
        $redis->select((int) REDIS_DBINDEX);
    }

    // Return
    return $redis;
}


