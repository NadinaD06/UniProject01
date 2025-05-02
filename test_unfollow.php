<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Unfollow Functionality</h2>";

try {
    // Get two test users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 2");
    $users = $stmt->fetchAll();
    
    if (count($users) < 2) {
        die("<p style='color: red;'>Need at least 2 users for unfollow test. Please run test_registration.php twice.</p>");
    }
    
    $follower = $users[0];
    $following = $users[1];
    
    echo "<h3>Test Users:</h3>";
    echo "<p>Follower: " . htmlspecialchars($follower['username']) . "</p>";
    echo "<p>Following: " . htmlspecialchars($following['username']) . "</p>";
    
    // First, ensure there's a follow relationship
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM follows 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$follower['id'], $following['id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Create follow relationship if it doesn't exist
        $stmt = $pdo->prepare("
            INSERT INTO follows (follower_id, following_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$follower['id'], $following['id']]);
        echo "<p style='color: green;'>Created follow relationship for testing</p>";
    }
    
    // Display current follow relationships
    echo "<h3>Before Unfollow:</h3>";
    $stmt = $pdo->prepare("
        SELECT f.*, 
               follower.username as follower_username,
               following.username as following_username
        FROM follows f
        JOIN users follower ON f.follower_id = follower.id
        JOIN users following ON f.following_id = following.id
        WHERE f.follower_id = ? AND f.following_id = ?
    ");
    $stmt->execute([$follower['id'], $following['id']]);
    $follow = $stmt->fetch();
    
    if ($follow) {
        echo "<div style='padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;'>";
        echo "<p><strong>" . htmlspecialchars($follow['follower_username']) . "</strong> follows <strong>" . htmlspecialchars($follow['following_username']) . "</strong></p>";
        echo "<p>Since: " . htmlspecialchars($follow['created_at']) . "</p>";
        echo "</div>";
    }
    
    // Perform unfollow
    echo "<h3>Performing Unfollow:</h3>";
    $stmt = $pdo->prepare("
        DELETE FROM follows 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$follower['id'], $following['id']]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>Successfully unfollowed!</p>";
    } else {
        echo "<p style='color: orange;'>No follow relationship found to unfollow.</p>";
    }
    
    // Verify unfollow
    echo "<h3>After Unfollow:</h3>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM follows 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$follower['id'], $following['id']]);
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<p style='color: green;'>✓ Verified: Follow relationship has been removed</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: Follow relationship still exists</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 