<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Messaging System</h2>";

try {
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for messaging test. Please run test_registration.php twice.</p>");
    }
    
    $userA = $users[0];
    $userB = $users[1];
    
    echo "<h3>Test Users:</h3>";
    echo "<p>User A: " . htmlspecialchars($userA['username']) . "</p>";
    echo "<p>User B: " . htmlspecialchars($userB['username']) . "</p>";
    
    // Send a test message from User A to User B
    $testMessage = "Hello! This is a test message sent at " . date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$userA['id'], $userB['id'], $testMessage]);
    echo "<p style='color: green;'>Test message sent from " . htmlspecialchars($userA['username']) . " to " . htmlspecialchars($userB['username']) . "</p>";
    
    // Send a reply from User B to User A
    $replyMessage = "Hi! This is a reply sent at " . date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$userB['id'], $userA['id'], $replyMessage]);
    echo "<p style='color: green;'>Reply sent from " . htmlspecialchars($userB['username']) . " to " . htmlspecialchars($userA['username']) . "</p>";
    
    // Display the conversation
    echo "<h3>Conversation History:</h3>";
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sender.username as sender_username,
               receiver.username as receiver_username
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        JOIN users receiver ON m.receiver_id = receiver.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    
    $stmt->execute([
        $userA['id'], $userB['id'],
        $userB['id'], $userA['id']
    ]);
    
    $messages = $stmt->fetchAll();
    
    if (!empty($messages)) {
        echo "<div style='max-width: 600px; margin: 20px auto;'>";
        foreach ($messages as $message) {
            $isSender = $message['sender_id'] == $userA['id'];
            $style = $isSender ? 
                "background-color: #e3f2fd; margin-left: 20%;" : 
                "background-color: #f5f5f5; margin-right: 20%;";
            
            echo "<div style='padding: 10px; margin: 10px 0; border-radius: 10px; " . $style . "'>";
            echo "<p><strong>" . htmlspecialchars($message['sender_username']) . "</strong> to <strong>" . htmlspecialchars($message['receiver_username']) . "</strong></p>";
            echo "<p>" . htmlspecialchars($message['content']) . "</p>";
            echo "<p style='font-size: 0.8em; color: #666;'>Sent: " . htmlspecialchars($message['sent_at']) . "</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Display message statistics
    echo "<h3>Message Statistics:</h3>";
    
    // Count total messages
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$userA['id'], $userB['id'], $userB['id'], $userA['id']]);
    $totalMessages = $stmt->fetch()['count'];
    
    // Count messages sent by each user
    $stmt = $pdo->prepare("
        SELECT sender_id, COUNT(*) as count 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        GROUP BY sender_id
    ");
    $stmt->execute([$userA['id'], $userB['id'], $userB['id'], $userA['id']]);
    $messageCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0;'>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Total Messages:</strong> " . $totalMessages . "</p>";
    echo "</div>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Messages from " . htmlspecialchars($userA['username']) . ":</strong> " . ($messageCounts[$userA['id']] ?? 0) . "</p>";
    echo "</div>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Messages from " . htmlspecialchars($userB['username']) . ":</strong> " . ($messageCounts[$userB['id']] ?? 0) . "</p>";
    echo "</div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 