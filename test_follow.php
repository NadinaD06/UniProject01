<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Follow/Unfollow Functionality</h2>";

try {
    // First, check if follows table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'follows'");
    if ($stmt->rowCount() == 0) {
        // Create follows table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            follows_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (follows_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (user_id, follows_id)
        )";
        $pdo->exec($sql);
        echo "<p style='color: green;'>Follows table created successfully!</p>";
    }

    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for follow test. Please run test_registration.php twice.</p>");
    }
    
    $follower = $users[0];
    $following = $users[1];
    
    echo "<h3>Test Users:</h3>";
    echo "<p>Follower: " . htmlspecialchars($follower['username']) . "</p>";
    echo "<p>Following: " . htmlspecialchars($following['username']) . "</p>";
    
    // Test follow
    try {
        $stmt = $pdo->prepare("
            INSERT INTO follows (user_id, follows_id)
            VALUES (?, ?)
        ");
        
        $stmt->execute([$follower['id'], $following['id']]);
        echo "<p style='color: green;'>Follow relationship created successfully!</p>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo "<p style='color: orange;'>Follow relationship already exists.</p>";
        } else {
            throw $e;
        }
    }
    
    // Display current follow relationships
    echo "<h3>Current Follow Relationships:</h3>";
    
    $stmt = $pdo->prepare("
        SELECT f.*, 
               follower.username as follower_username,
               following.username as following_username
        FROM follows f
        JOIN users follower ON f.user_id = follower.id
        JOIN users following ON f.follows_id = following.id
        WHERE f.user_id = ? OR f.follows_id = ?
        ORDER BY f.created_at DESC
    ");
    
    $stmt->execute([$follower['id'], $following['id']]);
    $follows = $stmt->fetchAll();
    
    if (!empty($follows)) {
        echo "<div style='max-width: 600px; margin: 20px auto;'>";
        foreach ($follows as $follow) {
            echo "<div style='padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;'>";
            echo "<p><strong>" . htmlspecialchars($follow['follower_username']) . "</strong> follows <strong>" . htmlspecialchars($follow['following_username']) . "</strong></p>";
            echo "<p style='font-size: 0.8em; color: #666;'>Since: " . htmlspecialchars($follow['created_at']) . "</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Display follows table structure
    echo "<h3>Follows Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE follows");
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