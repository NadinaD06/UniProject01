<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Feed Functionality</h2>";

try {
    // Get the current user
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 1");
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) {
        die("<p style='color: red;'>No test user found. Please run test_registration.php first.</p>");
    }
    
    echo "<h3>Current User:</h3>";
    echo "<p>Username: " . htmlspecialchars($currentUser['username']) . "</p>";
    
    // Get users that the current user follows
    $stmt = $pdo->prepare("
        SELECT u.id, u.username
        FROM follows f
        JOIN users u ON f.following_id = u.id
        WHERE f.follower_id = ?
    ");
    $stmt->execute([$currentUser['id']]);
    $following = $stmt->fetchAll();
    
    echo "<h3>Following:</h3>";
    if (!empty($following)) {
        echo "<ul>";
        foreach ($following as $user) {
            echo "<li>" . htmlspecialchars($user['username']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Not following anyone yet.</p>";
    }
    
    // Get feed posts (posts from followed users and own posts)
    echo "<h3>Feed Posts:</h3>";
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.username,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ? 
           OR p.user_id IN (
               SELECT following_id 
               FROM follows 
               WHERE follower_id = ?
           )
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id']]);
    $posts = $stmt->fetchAll();
    
    if (!empty($posts)) {
        foreach ($posts as $post) {
            echo "<div style='padding: 15px; margin: 15px 0; border: 1px solid #ccc; border-radius: 5px;'>";
            echo "<p><strong>" . htmlspecialchars($post['username']) . "</strong></p>";
            echo "<p>" . htmlspecialchars($post['content']) . "</p>";
            
            if ($post['image_url']) {
                echo "<img src='" . htmlspecialchars($post['image_url']) . "' style='max-width: 300px; margin: 10px 0;'>";
            }
            
            if ($post['location']) {
                echo "<p><small>📍 " . htmlspecialchars($post['location']) . "</small></p>";
            }
            
            echo "<p><small>Posted: " . htmlspecialchars($post['created_at']) . "</small></p>";
            
            // Like button
            $likeClass = $post['is_liked'] ? 'color: red;' : 'color: gray;';
            echo "<p><span style='" . $likeClass . "'>❤️</span> " . $post['like_count'] . " likes | 💬 " . $post['comment_count'] . " comments</p>";
            
            // Show comments
            $stmt = $pdo->prepare("
                SELECT c.*, u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at ASC
                LIMIT 3
            ");
            $stmt->execute([$post['id']]);
            $comments = $stmt->fetchAll();
            
            if (!empty($comments)) {
                echo "<div style='margin-left: 20px; padding: 10px; background-color: #f5f5f5; border-radius: 5px;'>";
                echo "<p><strong>Recent Comments:</strong></p>";
                foreach ($comments as $comment) {
                    echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . htmlspecialchars($comment['content']) . "</p>";
                }
                echo "</div>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<p>No posts in feed yet. Try following some users or creating posts!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 