<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Notification System</h2>";

try {
    // First, check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        // Create notifications table
        $sql = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            actor_id INT NOT NULL,
            type ENUM('message', 'like', 'comment', 'follow') NOT NULL,
            reference_id INT,
            content TEXT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        echo "<p style='color: green;'>Notifications table created successfully!</p>";
    }
    
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for notification test. Please run test_registration.php twice.</p>");
    }
    
    $userA = $users[0];
    $userB = $users[1];
    
    echo "<h3>Test Users:</h3>";
    echo "<p>User A: " . htmlspecialchars($userA['username']) . "</p>";
    echo "<p>User B: " . htmlspecialchars($userB['username']) . "</p>";
    
    // Create test notifications
    $notifications = [
        [
            'type' => 'message',
            'content' => 'sent you a message',
            'reference_id' => null
        ],
        [
            'type' => 'like',
            'content' => 'liked your post',
            'reference_id' => 1
        ],
        [
            'type' => 'comment',
            'content' => 'commented on your post',
            'reference_id' => 1
        ],
        [
            'type' => 'follow',
            'content' => 'started following you',
            'reference_id' => null
        ]
    ];
    
    echo "<h3>Creating Test Notifications:</h3>";
    foreach ($notifications as $notification) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, reference_id, content)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userA['id'],
            $userB['id'],
            $notification['type'],
            $notification['reference_id'],
            $notification['content']
        ]);
        
        echo "<p style='color: green;'>Created " . $notification['type'] . " notification</p>";
    }
    
    // Display notifications
    echo "<h3>User A's Notifications:</h3>";
    $stmt = $pdo->prepare("
        SELECT n.*, 
               actor.username as actor_username
        FROM notifications n
        JOIN users actor ON n.actor_id = actor.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    
    $stmt->execute([$userA['id']]);
    $userNotifications = $stmt->fetchAll();
    
    if (!empty($userNotifications)) {
        echo "<div style='max-width: 600px; margin: 20px auto;'>";
        foreach ($userNotifications as $notification) {
            $readClass = $notification['is_read'] ? 'background-color: #f5f5f5;' : 'background-color: #e3f2fd;';
            
            echo "<div style='padding: 10px; margin: 10px 0; border-radius: 10px; " . $readClass . "'>";
            echo "<p><strong>" . htmlspecialchars($notification['actor_username']) . "</strong> " . htmlspecialchars($notification['content']) . "</p>";
            echo "<p style='font-size: 0.8em; color: #666;'>Type: " . htmlspecialchars($notification['type']) . " | Created: " . htmlspecialchars($notification['created_at']) . "</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Mark notifications as read
    echo "<h3>Marking Notifications as Read:</h3>";
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = TRUE 
        WHERE user_id = ? AND is_read = FALSE
    ");
    
    $stmt->execute([$userA['id']]);
    $updatedCount = $stmt->rowCount();
    
    echo "<p style='color: green;'>Marked " . $updatedCount . " notifications as read</p>";
    
    // Display notification statistics
    echo "<h3>Notification Statistics:</h3>";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_count,
            type,
            COUNT(*) as type_count
        FROM notifications
        WHERE user_id = ?
        GROUP BY type
    ");
    
    $stmt->execute([$userA['id']]);
    $stats = $stmt->fetchAll();
    
    echo "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0;'>";
    foreach ($stats as $stat) {
        echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
        echo "<p><strong>" . ucfirst($stat['type']) . " Notifications:</strong> " . $stat['type_count'] . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 