<?php
/**
 * Like Model
 * Handles like-related operations
 */
namespace App\Models;

use App\Core\Model;

class Like extends Model {
    protected $table = 'likes';
    protected $fillable = ['user_id', 'post_id'];

    /**
     * Toggle like status for a post
     */
    public function toggleLike($postId, $userId) {
        // Check if already liked
        $sql = "SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => $userId
        ]);
        
        $like = $stmt->fetch();
        
        if ($like) {
            // Unlike
            $sql = "DELETE FROM likes WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $like['id']]);
        } else {
            // Like
            $sql = "INSERT INTO likes (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'post_id' => $postId,
                'user_id' => $userId
            ]);
        }
    }
    
    /**
     * Get likes for a post
     */
    public function getForPost($postId) {
        $sql = "SELECT l.*, u.username, u.profile_image
                FROM likes l
                JOIN users u ON l.user_id = u.id
                WHERE l.post_id = :post_id
                ORDER BY l.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get like count for a post
     */
    public function getCount($postId) {
        $sql = "SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Check if user liked a post
     */
    public function isLiked($postId, $userId) {
        $sql = "SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => $userId
        ]);
        return $stmt->fetch() !== false;
    }

    /**
     * Add a like to a post
     * @param int $userId User ID
     * @param int $postId Post ID
     * @return int|bool Like ID or false on failure
     */
    public function addLike($userId, $postId) {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, post_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $postId]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error adding like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a like from a post
     * @param int $userId User ID
     * @param int $postId Post ID
     * @return bool Success status
     */
    public function removeLike($userId, $postId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND post_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $postId]);
        } catch (\PDOException $e) {
            error_log("Error removing like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users who liked a post
     * @param int $postId Post ID
     * @param int $limit Maximum number of users to return
     * @return array List of users who liked the post
     */
    public function getLikedUsers($postId, $limit = 10) {
        try {
            $sql = "SELECT u.id, u.username, u.profile_image 
                    FROM {$this->table} l 
                    JOIN users u ON l.user_id = u.id 
                    WHERE l.post_id = ? 
                    ORDER BY l.created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId, $limit]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting liked users: " . $e->getMessage());
            return [];
        }
    }
} 