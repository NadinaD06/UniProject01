<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Image Upload Functionality</h2>";

try {
    // Get the test user we created
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        die("<p style='color: red;'>No test user found. Please run test_registration.php first.</p>");
    }
    
    echo "<h3>Test User:</h3>";
    echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
    
    // Create a test post with image
    $testPost = [
        'user_id' => $user['id'],
        'content' => 'This is a test post with an image at ' . date('Y-m-d H:i:s'),
        'image_url' => '/assets/images/test-image.jpg',  // This would normally be uploaded
        'location' => 'Test Location',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, content, image_url, location, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $testPost['user_id'],
        $testPost['content'],
        $testPost['image_url'],
        $testPost['location'],
        $testPost['created_at']
    ]);
    
    $postId = $pdo->lastInsertId();
    echo "<p style='color: green;'>Test post with image created successfully!</p>";
    
    // Display the post with image
    echo "<h3>Test Post with Image:</h3>";
    
    // Get post details
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Post by " . htmlspecialchars($post['username']) . "</strong></p>";
    echo "<p>" . htmlspecialchars($post['content']) . "</p>";
    if ($post['image_url']) {
        echo "<p>Image URL: " . htmlspecialchars($post['image_url']) . "</p>";
    }
    if ($post['location']) {
        echo "<p>Location: " . htmlspecialchars($post['location']) . "</p>";
    }
    echo "<p>Created: " . htmlspecialchars($post['created_at']) . "</p>";
    echo "<p>Likes: " . htmlspecialchars($post['like_count']) . "</p>";
    echo "<p>Comments: " . htmlspecialchars($post['comment_count']) . "</p>";
    echo "</div>";
    
    // Display recent posts with images
    echo "<h3>Recent Posts with Images:</h3>";
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.image_url IS NOT NULL
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    foreach ($posts as $post) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>Post by " . htmlspecialchars($post['username']) . "</strong></p>";
        echo "<p>" . htmlspecialchars($post['content']) . "</p>";
        if ($post['image_url']) {
            echo "<p>Image URL: " . htmlspecialchars($post['image_url']) . "</p>";
        }
        if ($post['location']) {
            echo "<p>Location: " . htmlspecialchars($post['location']) . "</p>";
        }
        echo "<p>Created: " . htmlspecialchars($post['created_at']) . "</p>";
        echo "<p>Likes: " . htmlspecialchars($post['like_count']) . "</p>";
        echo "<p>Comments: " . htmlspecialchars($post['comment_count']) . "</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 