<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

header('Content-Type: application/json');

try {
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        throw new Exception("Need at least 2 users for notification test");
    }
    
    $userA = $users[0];
    $userB = $users[1];
    
    // Random notification types
    $types = ['message', 'like', 'comment', 'follow'];
    $type = $types[array_rand($types)];
    
    // Content based on type
    $content = match($type) {
        'message' => 'sent you a message',
        'like' => 'liked your post',
        'comment' => 'commented on your post',
        'follow' => 'started following you',
        default => 'interacted with your content'
    };
    
    // Create notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, actor_id, type, content)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userA['id'],
        $userB['id'],
        $type,
        $content
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test notification created',
        'type' => $type
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 