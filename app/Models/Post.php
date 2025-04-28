<?php
namespace App\Models;

class Post {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO posts (user_id, content, image_url, location)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'],
            $data['content'],
            $data['image_url'] ?? null,
            $data['location'] ?? null
        ]);
    }
    
    public function getFeed($userId, $limit = 10, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username, u.profile_image,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = ?
                UNION SELECT ?
            )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $userId, $userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username, u.profile_image,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        $values[] = $id;
        
        $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($values);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function like($postId, $userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO likes (post_id, user_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([$postId, $userId]);
    }
    
    public function unlike($postId, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        return $stmt->execute([$postId, $userId]);
    }
} 