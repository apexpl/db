<?php
declare(strict_types = 1);

namespace Apex\Db\Cli;

use Apex\Db\ConnectionManager;
use Apex\Db\Cli\Commands;
use redis;

/**
 * Handles all CLI functionality for the connection manager to manage connections via redis.
 */
class Cli
{

    // Properties
    public static array $args = [];
    public static array $options = [];

    /**
     * Run CLI command
     */
    public static function run(redis $redis)
    {

        // Get arguments
        list($args, $opt) = self::getArgs(['dbname', 'user', 'password', 'host', 'port']);
        $method = array_shift($args) ?? '';
        $alias = array_shift($args) ?? '';
        $method = str_replace('-', '_', $method);

        // Check for command
        $client = new Commands($redis);
        if (!method_exists($client, $method)) { 
            self::showHelp();
        } else { 
            $client->$method($opt, $alias);
        }

    }

    /**
     * Show help
     */
    public static function showHelp():void
    {

        // Send header
        self::sendHeader('Available Commands');

        self::send("    set-master             Set the master database info.\n");
        self::send("    get-master             Display the current master database info.\n");
        self::send("    set-readonly ALIAS     Add / update a read-only database connection.\n");
        self::send("    get-readonly ALIAS     Display connection information for specified read-only database.\n");
        self::send("    del-readonly ALIAS     Delete specified read-only database.\n");
        self::send("    list-readonly     List all currently read-only databases.\n");
        self::send("    delete-all             Delete all database connection information within redis.\n\n");

        // Exit
        exit(0);
    }

    /**
     * Get command line arguments and options
     */
    public static function getArgs(array $has_value = []):array
    {

        // Initialize
        global $argv;
        list($args, $options, $tmp_args) = [[], [], $argv];
        array_shift($tmp_args);

        // Go through args
        while (count($tmp_args) > 0) { 
            $var = array_shift($tmp_args);

            // Long option with =
            if (preg_match("/^--(\w+?)=(.+)$/", $var, $match)) { 
                $options[$match[1]] = $match[2];

            } elseif (preg_match("/^--(.+)$/", $var, $match) && in_array($match[1], $has_value)) { 


                $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                if ($value == '=') { 
                    $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                }
                $options[$match[1]] = $value;

            } elseif (preg_match("/^--(.+)/", $var, $match)) { 
                $options[$match[1]] = true;

            } elseif (preg_match("/^-(\w+)/", $var, $match)) { 
                $chars = str_split($match[1]);
                foreach ($chars as $char) { 
                    $options[$char] = true;
                }

            } else { 
                $args[] = $var;
            }
        }

        // Set properties
        self::$args = $args;
        self::$options = $options;

        // Return
        return array($args, $options);
    }

    /**
     * Get input from the user.
     */
    public static function getInput(string $label, string $default_value = ''):string
    { 

        // Echo label
        self::send($label);

        // Get input
        $value = strtolower(trim(fgets(STDIN)));
        if ($value == '') { $value = $default_value; }

        // Check quit / exist
        if (in_array($value, ['q', 'quit', 'exit'])) { 
            self::send("Ok, goodbye.\n\n");
            exit(0);
        }

        // Return
        return $value;
    }

    /**
     * Send output to user.
     */
    public static function send(string $data):void
    {
        fputs(STDOUT, $data);
    }

    /**
     * Send header to user
     */
    public static function sendHeader(string $label):void
    {
        self::send("------------------------------\n");
        self::send("-- $label\n");
        self::send("------------------------------\n\n");
    }

}

