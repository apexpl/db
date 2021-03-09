<?php
declare(strict_types = 1);

use Apex\Db\Mapper\ToInstance;
use Apex\Db\Test\UserModel;
use PHPUnit\Framework\TestCase;


/**
 * SQL tests
 */
class object_mapping_test extends TestCase
{

    /**
     * Setup
     */
    public function setUp():void
    {
        require_once(__DIR__ . '/utils.php');
        require_once(__DIR__ . '/classes/UserModel.php');
    }

    /**
     * Test tables
     */
    public function test_objects()
    {

        // Connect
        $db = getTestConnection();

        // Create test table
        $db->query("DROP TABLE IF EXISTS test_users");
        $db->query("CREATE TABLE test_users (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, username VARCHAR(100) NOT NULL, full_name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL)");
        $this->assertTrue($db->checkTable('test_users'));

        // Insert single object
        $user = new UserModel('jsmith', 'John Smith', 'jsmith@domain.com');
        $db->insert('test_users', $user);
        $rows = $db->query("SELECT * FROM test_users");
        $this->assertCount(1, $rows);

        // Get single user
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'jsmith'");
        $user = ToInstance::map(UserModel::class, $row);
        $this->assertIsObject($user);
        $this->assertEquals('jsmith', $user->getUsername());
        $this->assertEquals('John Smith', $user->getFullName());


        // Add multiple users
        $user2 =new UserModel('mike', 'Mike Jacobs', 'mike@gmail.com');
        $user3 = new UserModel('grant', 'Grant McDermon', 'grant@domain.com');
        $db->insert('test_users', $user2, $user3);

        // Check multiple adds
        $usernames = $db->getColumn("SELECT username FROM test_users");
        $this->assertCount(3, $usernames);
        $this->assertContains('grant', $usernames);

        // Add mulitple users, iterable
        $new_users = [
            new UserModel('brad', 'Brad M', 'bradley@domain.com'), 
            new UserModel('leanne', 'Leanne Gibbons', 'leanne@domain.com'), 
            new UserModel('jason', 'Jason Landman', 'jason@domain.com')
        ];
        $db->insert('test_users', ...$new_users);

        // Check new inserts
        $hash = $db->getHash("SELECT id,username FROM test_users");
        $this->assertCount(6, $hash);
        $this->assertEquals('jsmith', $hash['1']);

        // Get single user
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'jsmith'");
        $user = ToInstance::map(UserModel::class, $row);
        $this->assertIsObject($user);
        $this->assertEquals('jsmith', $user->getUsername());
        $this->assertEquals('John Smith', $user->getFullName());

        // Update user
        $user->setEmail('new.email@domain.com');
        $db->update('test_users', $user);
        $row = $db->getRow("SELECT * FROM test_users WHERE username = 'jsmith'");
        $user = ToInstance::map(UserModel::class, $row);
        $this->assertIsObject($user);
        $this->assertEquals('jsmith', $user->getUsername());
        $this->assertEquals('new.email@domain.com', $user->getEmail());

        // Check count
        $count = $db->getField("SELECT count(*) FROM test_users");
        $this->assertEquals(6, $count);

        // Delete
        $count = 0;
        $db->delete('test_users', $user);
        $rows = $db->query("SELECT * FROM test_users");
        foreach ($rows as $row) { 
            $user = ToInstance::map(UserModel::class, $row);
            $this->assertIsObject($user);
            $this->assertNotEquals('jsmith', $user->getUsername());
            $count++;
        }
        $this->assertEquals(5, $count);

        // Insert or update
        if ($_SERVER['test_sql_driver'] != 'sqlite') { 
            $user = new UserModel('kim', 'Kim Gibbons', 'kim@domain.com');
            $db->insertOrUpdate('test_users', $user);

            // Check kim
            $row = $db->getIdRow('test_users', 7);
            $user = ToInstance::map(UserModel::class, $row);
            $this->assertIsObject($user);
            $this->assertEquals('kim', $user->getUsername());
            $this->assertEquals('kim@domain.com', $user->getEmail());

            // Insert or update, but update
            $user->setEmail('new.kim@domain.com');
            $db->insertOrUpdate('test_users', $user);
            $rows = $db->query("SELECT * FROM test_users");
            $this->assertCount(6, $rows);
        }

        // Drop table
        //$db->query("DROP TABLE test_users");
    }

}


