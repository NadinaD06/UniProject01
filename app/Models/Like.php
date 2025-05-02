<?php
/**
 * Like Model
 * Handles post like operations
 */
namespace App\Models;

class Like extends Model {
    protected $table = 'likes';
    protected $fillable = [
        'user_id',
        'post_id'
    ];

    /**
     * Like a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return bool Success status
     */
    public function likePost($postId, $userId) {
        try {
            return (bool) $this->create([
                'post_id' => $postId,
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Unlike a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return bool Success status
     */
    public function unlikePost($postId, $userId) {
        return (bool) $this->db->delete(
            $this->table,
            'post_id = ? AND user_id = ?',
            [$postId, $userId]
        );
    }

    /**
     * Check if a user has liked a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return bool
     */
    public function hasLiked($postId, $userId) {
        return (bool) $this->db->fetch(
            "SELECT 1 FROM {$this->table} WHERE post_id = ? AND user_id = ?",
            [$postId, $userId]
        );
    }

    /**
     * Get like count for a post
     * @param int $postId Post ID
     * @return int
     */
    public function getLikeCount($postId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE post_id = ?",
            [$postId]
        );
        return (int) $result['count'];
    }

    /**
     * Get users who liked a post
     * @param int $postId Post ID
     * @param int $page Page number
     * @param int $perPage Users per page
     * @return array
     */
    public function getLikedUsers($postId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $users = $this->db->fetchAll(
            "SELECT u.id, u.username, u.profile_image, l.created_at as liked_at
            FROM {$this->table} l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = ?
            ORDER BY l.created_at DESC
            LIMIT ? OFFSET ?",
            [$postId, $perPage, $offset]
        );

        $total = $this->getLikeCount($postId);

        return [
            'data' => $users,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
} 