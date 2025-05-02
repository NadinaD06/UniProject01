<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\User;
use App\Models\Post;
use App\Models\Message;
use App\Models\Block;
use App\Models\Report;

class SiteTester {
    private $userModel;
    private $postModel;
    private $messageModel;
    private $blockModel;
    private $reportModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->postModel = new Post();
        $this->messageModel = new Message();
        $this->blockModel = new Block();
        $this->reportModel = new Report();
    }
    
    public function runTests() {
        echo "Starting site tests...\n";
        
        // Test user creation
        $this->testUserCreation();
        
        // Test post creation
        $this->testPostCreation();
        
        // Test messaging
        $this->testMessaging();
        
        // Test blocking
        $this->testBlocking();
        
        // Test reporting
        $this->testReporting();
        
        echo "All tests completed!\n";
    }
    
    private function testUserCreation() {
        echo "Testing user creation...\n";
        
        $userData = [
            'username' => 'testuser' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => 'password123'
        ];
        
        $userId = $this->userModel->create($userData);
        if ($userId) {
            echo "✅ User creation successful\n";
        } else {
            echo "❌ User creation failed\n";
        }
    }
    
    private function testPostCreation() {
        echo "Testing post creation...\n";
        
        $postData = [
            'user_id' => 1,
            'content' => 'Test post ' . time(),
            'location' => '{"lat": 51.5074, "lng": -0.1278}'
        ];
        
        $postId = $this->postModel->create($postData);
        if ($postId) {
            echo "✅ Post creation successful\n";
        } else {
            echo "❌ Post creation failed\n";
        }
    }
    
    private function testMessaging() {
        echo "Testing messaging...\n";
        
        $messageData = [
            'sender_id' => 1,
            'receiver_id' => 2,
            'content' => 'Test message ' . time()
        ];
        
        $messageId = $this->messageModel->create($messageData);
        if ($messageId) {
            echo "✅ Message creation successful\n";
        } else {
            echo "❌ Message creation failed\n";
        }
    }
    
    private function testBlocking() {
        echo "Testing blocking...\n";
        
        $result = $this->blockModel->blockUser(1, 2);
        if ($result) {
            echo "✅ User blocking successful\n";
        } else {
            echo "❌ User blocking failed\n";
        }
    }
    
    private function testReporting() {
        echo "Testing reporting...\n";
        
        $reportData = [
            'reporter_id' => 1,
            'reported_id' => 2,
            'reason' => 'Test report ' . time()
        ];
        
        $reportId = $this->reportModel->create($reportData);
        if ($reportId) {
            echo "✅ Report creation successful\n";
        } else {
            echo "❌ Report creation failed\n";
        }
    }
}

// Run tests
$tester = new SiteTester();
$tester->runTests(); 