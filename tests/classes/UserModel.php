<?php
declare(strict_types = 1);

namespace Apex\Db\Test;


/**
 * Test user model.
 */
class UserModel
{

    /**
     * Constructor
     */
    public function __construct(
        private string $username, 
        private string $full_name, 
        private string $email, 
        private string $phone = '', 
        private int $id = 0
    ) {

    }

    /**
     * Getters
     */
    public function getUsername() { return $this->username; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }

}


