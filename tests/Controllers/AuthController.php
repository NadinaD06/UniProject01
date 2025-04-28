<?php
/**
 * tests/Controllers/AuthControllerTest.php
 */

namespace Tests\Controllers;

use Tests\TestCase;
use App\Controllers\AuthController;
use App\Models\User;

class AuthControllerTest extends TestCase {
    private $controller;
    private $userModel;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Create test tables
        $this->createTestTables();
        
        // Initialize controller and model
        $this->controller = new AuthController();
        $this->userModel = new User();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function tearDown(): void {
        // Drop test tables
        $this->dropTestTables();
        
        // Clear session
        $_SESSION = [];
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        parent::tearDown();
    }
    
    public function testLogin() {
        // Create test user
        $userData = [
            'username' => 'logintest',
            'email' => 'login@example.com',
            'password' => 'password123'
        ];
        
        $this->userModel->create([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT)
        ]);
        
        // Prepare request data
        $_POST = [
            'username' => $userData['username'],
            'password' => $userData['password'],
            'csrf_token' => $_SESSION['csrf_token'] ?? 'test_token'
        ];
        
        // Mock csrf_token in session if not already set
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = 'test_token';
        }
        
        // Capture output
        ob_start();
        $this->controller->login();
        $output = ob_get_clean();
        
        // Decode JSON response
        $response = json_decode($output, true);
        
        // Assert response is successful
        $this->assertTrue($response['success']);
        
        // Assert user is logged in
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($userData['username'], $_SESSION['username']);
    }
    
    public function testLoginWithInvalidCredentials() {
        // Prepare request data with invalid credentials
        $_POST = [
            'username' => 'nonexistent',
            'password' => 'wrongpassword',
            'csrf_token' => $_SESSION['csrf_token'] ?? 'test_token'
        ];
        
        // Mock csrf_token in session if not already set
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = 'test_token';
        }
        
        // Capture output
        ob_start();
        $this->controller->login();
        $output = ob_get_clean();
        
        // Decode JSON response
        $response = json_decode($output, true);
        
        // Assert response is not successful
        $this->assertFalse($response['success']);
        
        // Assert response contains error message
        $this->assertEquals('Invalid credentials', $response['message']);
        
        // Assert user is not logged in
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
}