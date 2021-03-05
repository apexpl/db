<?php
declare(strict_types = 1);

namespace Apex\Db\Cli;

use Apex\Db\ConnectionManager;
use Apex\Db\Cli\Cli;
use Apex\Db\Exceptions\DbConnectionManagerException;
use redis;

/**
 * Handles all command line functionality.
 */
class Commands
{

    /**
     * Constructor
     */
    public function __construct(private redis $redis)
    {

    }

    /**
     * Set master info
     */
    public function set_master(array $opt = [])
    {

        // Get database info
        $dbinfo = $this->getDatabaseInfo($opt);

        // Add connection
        $manager = new ConnectionManager($this->redis);
        $manager->addDatabase('write', $dbinfo);

        // Send response
        Cli::send("Successfully set master database information.\n\n");
    }

    /**
     * Get master database
     */
    public function get_master(array $opt):void
    {

        // Get
        $manager = new ConnectionManager($this->redis);
        if (!$info = $manager->getDatabase('write')) { 
            Cli::send("There is no master database configured within redis.  You may set one using the 'set-master' command.\n");
        } else { 
            Cli::sendHeader('Master Database');
            Cli::send("Name: $info[dbname]\n");
            Cli::send("User: $info[user]\n");
            Cli::send("Pass: $info[password]\n");
            Cli::send("Host:  $info[host]\n");
        Cli::send("Port: $info[port]\n\n");
        }

    }

    /**
     * Add read-only database
     */
    public function set_readonly(array $opt = [], string $alias = '')
    {

        // Get database info
        $dbinfo = $this->getDatabaseInfo($opt);

        // Get alias, if needed
        if ($alias == '') { 
            $alias = Cli::getInput('Instance Name (eg. db2, nyc3, etc.): ');
        }

        // Add connection
        $manager = new ConnectionManager($this->redis);
        $manager->addDatabase('read', $dbinfo, $alias);

        // Send response
        Cli::send("Successfully added new read-only database.\n\n");
    }

    /**
     * Get master database
     */
    public function get_readonly(array $opt, string $alias):void
    {

        // Check
        if ($alias == '') { 
            throw new DbConnectionManagerException("You must specify the instance to get a real-only database.  Use the command: dbmgr get-readonly NAME");
        }

        // Get
        $manager = new ConnectionManager($this->redis);
        if (!$info = $manager->getDatabase('read', $alias)) { 
            Cli::send("There is no read-only database configured with the instance name '$alias'.  You may set it with the 'set-readonly' command.\n\n");
        } else { 
            Cli::sendHeader("Read-Only Database - $alias");
            Cli::send("Name: $info[dbname]\n");
            Cli::send("User: $info[user]\n");
            Cli::send("Pass: $info[password]\n");
            Cli::send("Host:  $info[host]\n");
        Cli::send("Port: $info[port]\n\n");
        }

    }

    /**
     * Delete read-only database
     */
    public function del_readonly(array $opts, string $alias = '') 
    {

        // CHeck
        if ($alias == '') { 
            throw new DbConnectionManagerException("You did not specify the read-only database to delete.  Use the command: del-readonly NAME");
        }

        // Delete
        $manager = new ConnectionManager($this->redis);
        $manager->deleteDatabase($alias);

        // Send message
        Cli::send("Successfully deleted read-only database with the name $alias");
    }

    /**
     * List read-only databaess
     */
    public function list_readonly(array $opt, string $alias = ''):void
    {

        // Get databases
        $manager = new ConnectionManager($this->redis);
        $dbs = $manager->listReadonly();

        // GO through databasse
        Cli::sendHeader('Read-Only Databases');
        foreach ($dbs as $alias => $name) { 
            Cli::send("    [$alias]  $name\n");
        }

    }

    /**
     * Delete all
     */
    public function delete_all(array $opts, string $alias = ''):void
    {

        // Delete all
        $manager = new ConnectionManager($this->redis);
        $manager->deleteAll();

        // Send message
        Cli::send("Successfully deleted all database connection information from redis.\n\n");
    }

    /**
     * Get database info
     */
    private function getDatabaseInfo(array $info):array
    {

        // Set vars
        $vars = [
            'dbname' => 'Database Name', 
            'user' => 'Username', 
            'password' => 'Password', 
            'host' => 'Host', 
            'port' => 'Port'
        ];

        // Get info as needed
        foreach ($vars as $var => $name) { 

            // Skip, if we have it
            if (isset($info[$var]) && $info[$var] != '') { 
                Cli::send($vars[$var] . ': ' . $info[$var] . "\n");
                continue;
            }

            // Get default
            $default = $var == 'host' ? 'localhost' : '';
            if ($var == 'port') {
                $default = '3306';
            }

            // Get info
            $info[$var] = Cli::getInput($vars[$var] . "[$default]: ", $default);
        }

        // Return
        return $info;
    }

}


