<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Messaging Functionality</h2>";

try {
    // First, check if messages table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() == 0) {
        die("<p style='color: red;'>Messages table does not exist. Please run setup_messages_table.php first.</p>");
    }

    // Get table structure
    $stmt = $pdo->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for messaging test. Please run test_registration.php twice.</p>");
    }
    
    $sender = $users[0];
    $receiver = $users[1];
    
    echo "<h3>Test Users:</h3>";
    echo "<p>Sender: " . htmlspecialchars($sender['username']) . "</p>";
    echo "<p>Receiver: " . htmlspecialchars($receiver['username']) . "</p>";
    
    // Send a test message
    $testMessage = [
        'sender_id' => $sender['id'],
        'receiver_id' => $receiver['id'],
        'content' => 'This is a test message sent at ' . date('Y-m-d H:i:s')
    ];
    
    // Build the SQL query dynamically based on available columns
    $columns = array_keys($testMessage);
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO messages (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($testMessage));
    
    echo "<p style='color: green;'>Test message sent successfully!</p>";
    
    // Display the conversation
    echo "<h3>Conversation between " . htmlspecialchars($sender['username']) . " and " . htmlspecialchars($receiver['username']) . ":</h3>";
    
    $stmt = $pdo->prepare("
        SELECT m.*, 
               s.username as sender_username,
               r.username as receiver_username
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        JOIN users r ON m.receiver_id = r.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.id ASC
    ");
    
    $stmt->execute([
        $sender['id'], $receiver['id'],
        $receiver['id'], $sender['id']
    ]);
    
    $messages = $stmt->fetchAll();
    
    if (!empty($messages)) {
        echo "<div style='max-width: 600px; margin: 20px auto;'>";
        foreach ($messages as $message) {
            $isSender = $message['sender_id'] == $sender['id'];
            $style = $isSender ? 
                "background-color: #e3f2fd; margin-left: 20%;" : 
                "background-color: #f5f5f5; margin-right: 20%;";
            
            echo "<div style='padding: 10px; margin: 10px 0; border-radius: 10px; " . $style . "'>";
            echo "<p><strong>" . htmlspecialchars($message['sender_username']) . "</strong> to <strong>" . htmlspecialchars($message['receiver_username']) . "</strong></p>";
            echo "<p>" . htmlspecialchars($message['content']) . "</p>";
            if (isset($message['created_at'])) {
                echo "<p style='font-size: 0.8em; color: #666;'>" . htmlspecialchars($message['created_at']) . "</p>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Display messages table structure
    echo "<h3>Messages Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE messages");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 