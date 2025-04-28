<?php
/**
 * tests/Models/UserModelTest.php
 */

namespace Tests\Models;

use Tests\TestCase;
use App\Models\User;

class UserModelTest extends TestCase {
    private $userModel;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Create test tables
        $this->createTestTables();
        
        // Initialize model
        $this->userModel = new User();
    }
    
    protected function tearDown(): void {
        // Drop test tables
        $this->dropTestTables();
        
        parent::tearDown();
    }
    
    public function testCreateUser() {
        // Test data
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'full_name' => 'Test User'
        ];
        
        // Create user
        $userId = $this->userModel->create([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'full_name' => $userData['full_name']
        ]);
        
        // Assert user was created
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        
        // Retrieve user
        $user = $this->userModel->find($userId);
        
        // Assert user data matches
        $this->assertEquals($userData['username'], $user['username']);
        $this->assertEquals($userData['email'], $user['email']);
        $this->assertEquals($userData['full_name'], $user['full_name']);
    }
    
    public function testAuthenticate() {
        // Test data
        $userData = [
            'username' => 'authuser',
            'email' => 'auth@example.com',
            'password' => 'secure123'
        ];
        
        // Create user
        $userId = $this->userModel->create([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT)
        ]);
        
        // Test authentication with username
        $user = $this->userModel->authenticate($userData['username'], $userData['password']);
        
        // Assert authentication succeeds
        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id']);
        
        // Test authentication with email
        $user = $this->userModel->authenticate($userData['email'], $userData['password']);
        
        // Assert authentication succeeds
        $this->assertIsArray($user);
        $this->assertEquals($userId, $user['id']);
        
        // Test authentication with wrong password
        $user = $this->userModel->authenticate($userData['username'], 'wrongpassword');
        
        // Assert authentication fails
        $this->assertFalse($user);
    }
}