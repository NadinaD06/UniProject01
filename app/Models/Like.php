<?php
/**
 * Like Model
 * Handles like-related operations
 */
namespace App\Models;

class Like extends Model {
    protected $table = 'likes';
    protected $fillable = ['user_id', 'post_id'];

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
     * Check if a user has liked a post
     * @param int $userId User ID
     * @param int $postId Post ID
     * @return bool True if liked, false otherwise
     */
    public function hasLiked($userId, $postId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $postId]);
            $result = $stmt->fetch();
            
            return (bool) $result['count'];
        } catch (\PDOException $e) {
            error_log("Error checking like status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get like count for a post
     * @param int $postId Post ID
     * @return int Number of likes
     */
    public function getLikeCount($postId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId]);
            $result = $stmt->fetch();
            
            return (int) $result['count'];
        } catch (\PDOException $e) {
            error_log("Error getting like count: " . $e->getMessage());
            return 0;
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