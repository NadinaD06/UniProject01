<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing User Profile Functionality</h2>";

try {
    // Get a test user
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        die("<p style='color: red;'>No test user found. Please run test_registration.php first.</p>");
    }
    
    echo "<h3>User Profile:</h3>";
    echo "<div style='padding: 20px; border: 1px solid #ccc; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
    echo "<p><strong>Member Since:</strong> " . htmlspecialchars($user['created_at']) . "</p>";
    echo "</div>";
    
    // Get user statistics
    echo "<h3>User Statistics:</h3>";
    
    // Count posts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $postCount = $stmt->fetch()['count'];
    
    // Count followers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $stmt->execute([$user['id']]);
    $followerCount = $stmt->fetch()['count'];
    
    // Count following
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
    $stmt->execute([$user['id']]);
    $followingCount = $stmt->fetch()['count'];
    
    // Count total likes received
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM likes l 
        JOIN posts p ON l.post_id = p.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $likesReceived = $stmt->fetch()['count'];
    
    echo "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0;'>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Posts:</strong> " . $postCount . "</p>";
    echo "</div>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Followers:</strong> " . $followerCount . "</p>";
    echo "</div>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Following:</strong> " . $followingCount . "</p>";
    echo "</div>";
    echo "<div style='padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<p><strong>Likes Received:</strong> " . $likesReceived . "</p>";
    echo "</div>";
    echo "</div>";
    
    // Get recent posts
    echo "<h3>Recent Posts:</h3>";
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $posts = $stmt->fetchAll();
    
    if (!empty($posts)) {
        foreach ($posts as $post) {
            echo "<div style='padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;'>";
            echo "<p>" . htmlspecialchars($post['content']) . "</p>";
            if ($post['image_url']) {
                echo "<img src='" . htmlspecialchars($post['image_url']) . "' style='max-width: 300px; margin: 10px 0;'>";
            }
            if ($post['location']) {
                echo "<p><small>📍 " . htmlspecialchars($post['location']) . "</small></p>";
            }
            echo "<p><small>Posted: " . htmlspecialchars($post['created_at']) . "</small></p>";
            echo "<p><small>❤️ " . $post['like_count'] . " likes | 💬 " . $post['comment_count'] . " comments</small></p>";
            echo "</div>";
        }
    } else {
        echo "<p>No posts yet.</p>";
    }
    
    // Get recent followers
    echo "<h3>Recent Followers:</h3>";
    $stmt = $pdo->prepare("
        SELECT u.username, f.created_at
        FROM follows f
        JOIN users u ON f.follower_id = u.id
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $followers = $stmt->fetchAll();
    
    if (!empty($followers)) {
        foreach ($followers as $follower) {
            echo "<div style='padding: 5px; margin: 5px 0;'>";
            echo "<p><strong>" . htmlspecialchars($follower['username']) . "</strong> started following you on " . htmlspecialchars($follower['created_at']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No followers yet.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 