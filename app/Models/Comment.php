<?php
/**
 * Comment Model
 * Handles comment-related operations
 */
namespace App\Models;

class Comment extends Model {
    protected $table = 'comments';
    protected $fillable = ['user_id', 'post_id', 'content'];

    /**
     * Add a comment to a post
     * @param int $userId User ID
     * @param int $postId Post ID
     * @param string $content Comment content
     * @return int|bool Comment ID or false on failure
     */
    public function addComment($userId, $postId, $content) {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, post_id, content) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $postId, $content]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a comment
     * @param int $commentId Comment ID
     * @param int $userId User ID (for verification)
     * @return bool Success status
     */
    public function deleteComment($commentId, $userId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$commentId, $userId]);
        } catch (\PDOException $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get comments for a post
     * @param int $postId Post ID
     * @param int $page Page number
     * @param int $perPage Comments per page
     * @return array Comments with pagination info
     */
    public function getPostComments($postId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            $comments = $this->db->fetchAll(
                "SELECT c.*, u.username, u.profile_image 
                FROM {$this->table} c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?",
                [$postId, $perPage, $offset]
            );

            $total = $this->db->fetch(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE post_id = ?",
                [$postId]
            )['count'];

            return [
                'data' => $comments,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ];
        } catch (\PDOException $e) {
            error_log("Error getting comments: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    /**
     * Get comment count for a post
     * @param int $postId Post ID
     * @return int Number of comments
     */
    public function getCommentCount($postId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId]);
            $result = $stmt->fetch();
            
            return (int) $result['count'];
        } catch (\PDOException $e) {
            error_log("Error getting comment count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if a user can delete a comment
     * @param int $commentId Comment ID
     * @param int $userId User ID
     * @return bool True if user can delete, false otherwise
     */
    public function canDelete($commentId, $userId) {
        try {
            $sql = "SELECT user_id FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();
            
            return $comment && ($comment['user_id'] == $userId);
        } catch (\PDOException $e) {
            error_log("Error checking comment ownership: " . $e->getMessage());
            return false;
        }
    }
} 