<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load required files
require_once 'get_db_connection.php';
require_once 'auth.php';

// Require login
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;
    $content = trim($_POST['content'] ?? '');
    
    if ($post_id && !empty($content)) {
        try {
            // Add comment
            $stmt = $pdo->prepare("
                INSERT INTO comments (user_id, post_id, content)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([getCurrentUserId(), $post_id, $content]);
            
            // Get post owner
            $stmt = $pdo->prepare("
                SELECT user_id 
                FROM posts 
                WHERE id = ?
            ");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if ($post && $post['user_id'] != getCurrentUserId()) {
                // Create notification
                createNotification(
                    $post['user_id'],
                    getCurrentUserId(),
                    'comment',
                    'commented on your post',
                    $post_id
                );
            }
            
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to add comment'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid post ID or empty content'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?> 