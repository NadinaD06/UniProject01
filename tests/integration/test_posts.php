<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing Post Functionality</h2>";

try {
    // Get the test user we created
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        die("<p style='color: red;'>No test user found. Please run test_registration.php first.</p>");
    }
    
    echo "<h3>Test User:</h3>";
    echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
    
    // Create a test post
    $testPost = [
        'user_id' => $user['id'],
        'content' => 'This is a test post created at ' . date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, content, created_at)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $testPost['user_id'],
        $testPost['content'],
        $testPost['created_at']
    ]);
    
    $postId = $pdo->lastInsertId();
    echo "<p style='color: green;'>Test post created successfully!</p>";
    
    // Add a test comment
    $testComment = [
        'post_id' => $postId,
        'user_id' => $user['id'],
        'content' => 'This is a test comment',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO comments (post_id, user_id, content, created_at)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $testComment['post_id'],
        $testComment['user_id'],
        $testComment['content'],
        $testComment['created_at']
    ]);
    
    echo "<p style='color: green;'>Test comment added successfully!</p>";
    
    // Add a test like
    $stmt = $pdo->prepare("
        INSERT INTO likes (post_id, user_id, created_at)
        VALUES (?, ?, NOW())
    ");
    
    $stmt->execute([$postId, $user['id']]);
    echo "<p style='color: green;'>Test like added successfully!</p>";
    
    // Display the post with its comments and likes
    echo "<h3>Test Post Details:</h3>";
    
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
    echo "<p>Created: " . htmlspecialchars($post['created_at']) . "</p>";
    echo "<p>Likes: " . htmlspecialchars($post['like_count']) . "</p>";
    echo "<p>Comments: " . htmlspecialchars($post['comment_count']) . "</p>";
    
    // Get comments
    $stmt = $pdo->prepare("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();
    
    echo "<h4>Comments:</h4>";
    foreach ($comments as $comment) {
        echo "<div style='margin-left: 20px; border-left: 2px solid #eee; padding-left: 10px;'>";
        echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong> said:</p>";
        echo "<p>" . htmlspecialchars($comment['content']) . "</p>";
        echo "<p>At: " . htmlspecialchars($comment['created_at']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Display table structures
    echo "<h3>Posts Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE posts");
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