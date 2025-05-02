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
    
    if ($post_id) {
        try {
            // Check if already liked
            $stmt = $pdo->prepare("
                SELECT id 
                FROM likes 
                WHERE user_id = ? AND post_id = ?
            ");
            $stmt->execute([getCurrentUserId(), $post_id]);
            
            if ($stmt->rowCount() > 0) {
                // Unlike
                $stmt = $pdo->prepare("
                    DELETE FROM likes 
                    WHERE user_id = ? AND post_id = ?
                ");
                $stmt->execute([getCurrentUserId(), $post_id]);
            } else {
                // Like
                $stmt = $pdo->prepare("
                    INSERT INTO likes (user_id, post_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([getCurrentUserId(), $post_id]);
                
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
                        'like',
                        'liked your post',
                        $post_id
                    );
                }
            }
            
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to process like'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid post ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?> 