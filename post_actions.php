// post_actions.php
<?php
session_start();
require_once 'get_db_connection.php';

class PostActions {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function likePost($postId, $userId) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $userId]);
            return ['action' => 'liked'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $stmt = $this->pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$postId, $userId]);
                return ['action' => 'unliked'];
            }
            throw $e;
        }
    }

    public function addComment($postId, $userId, $content) {
        $stmt = $this->pdo->prepare("
            INSERT INTO comments (post_id, user_id, content) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$postId, $userId, $content])) {
            return $this->getComment($this->pdo->lastInsertId());
        }
        return false;
    }

    private function getComment($commentId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.username 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLikes($postId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM likes 
            WHERE post_id = ?
        ");
        $stmt->execute([$postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getComments($postId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $postActions = new PostActions($pdo);
        
        switch($data['action']) {
            case 'like':
                $response = $postActions->likePost($data['postId'], $_SESSION['user_id']);
                $response['likeCount'] = $postActions->getLikes($data['postId']);
                echo json_encode($response);
                break;
                
            case 'comment':
                $comment = $postActions->addComment(
                    $data['postId'], 
                    $_SESSION['user_id'], 
                    $data['content']
                );
                echo json_encode($comment);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>