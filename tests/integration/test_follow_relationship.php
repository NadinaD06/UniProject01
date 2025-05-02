<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Follow Relationship</h2>";

try {
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for follow test. Please run test_registration.php twice.</p>");
    }
    
    $userA = $users[0];
    $userB = $users[1];
    
    echo "<h3>Example Follow Relationship:</h3>";
    echo "<p>User A: " . htmlspecialchars($userA['username']) . " (ID: " . $userA['id'] . ")</p>";
    echo "<p>User B: " . htmlspecialchars($userB['username']) . " (ID: " . $userB['id'] . ")</p>";
    
    // Create follow relationship: User A follows User B
    try {
        $stmt = $pdo->prepare("
            INSERT INTO follows (follower_id, following_id)
            VALUES (?, ?)
        ");
        
        $stmt->execute([$userA['id'], $userB['id']]);
        echo "<p style='color: green;'>Follow relationship created: " . htmlspecialchars($userA['username']) . " follows " . htmlspecialchars($userB['username']) . "</p>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<p style='color: orange;'>Follow relationship already exists.</p>";
        } else {
            throw $e;
        }
    }
    
    // Display the relationship in the database
    echo "<h3>Follow Relationship in Database:</h3>";
    $stmt = $pdo->prepare("
        SELECT f.*, 
               follower.username as follower_username,
               following.username as following_username
        FROM follows f
        JOIN users follower ON f.follower_id = follower.id
        JOIN users following ON f.following_id = following.id
        WHERE f.follower_id = ? AND f.following_id = ?
    ");
    
    $stmt->execute([$userA['id'], $userB['id']]);
    $follow = $stmt->fetch();
    
    if ($follow) {
        echo "<div style='padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;'>";
        echo "<p><strong>Database Record:</strong></p>";
        echo "<p>ID: " . htmlspecialchars($follow['id']) . "</p>";
        echo "<p>Follower ID: " . htmlspecialchars($follow['follower_id']) . " (" . htmlspecialchars($follow['follower_username']) . ")</p>";
        echo "<p>Following ID: " . htmlspecialchars($follow['following_id']) . " (" . htmlspecialchars($follow['following_username']) . ")</p>";
        echo "<p>Created At: " . htmlspecialchars($follow['created_at']) . "</p>";
        echo "</div>";
    }
    
    // Try to create the same relationship again (should fail)
    echo "<h3>Testing Duplicate Prevention:</h3>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO follows (follower_id, following_id)
            VALUES (?, ?)
        ");
        
        $stmt->execute([$userA['id'], $userB['id']]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<p style='color: green;'>✓ Duplicate follow prevented successfully!</p>";
        } else {
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 