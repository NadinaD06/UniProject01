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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = $_GET['post_id'] ?? null;
    
    if ($post_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at ASC
            ");
            
            $stmt->execute([$post_id]);
            $comments = $stmt->fetchAll();
            
            // Format dates
            foreach ($comments as &$comment) {
                $comment['created_at'] = date('M d, Y H:i', strtotime($comment['created_at']));
            }
            
            echo json_encode($comments);
            
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch comments'
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