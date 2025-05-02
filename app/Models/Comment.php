<?php
/**
 * Comment Model
 * Handles comment-related operations
 */
namespace App\Models;

class Comment extends Model {
    protected $table = 'comments';
    protected $fillable = [
        'post_id',
        'user_id',
        'content'
    ];

    /**
     * Add a comment to a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @param string $content Comment content
     * @return int|bool Comment ID or false on failure
     */
    public function addComment($postId, $userId, $content) {
        return $this->create([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content
        ]);
    }

    /**
     * Get comments for a post
     * @param int $postId Post ID
     * @param int $page Page number
     * @param int $perPage Comments per page
     * @return array
     */
    public function getPostComments($postId, $page = 1, $perPage = 20) {
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
    }

    /**
     * Delete a comment
     * @param int $commentId Comment ID
     * @param int $userId User ID (for verification)
     * @return bool Success status
     */
    public function deleteComment($commentId, $userId) {
        // Verify comment ownership
        $comment = $this->find($commentId);
        if (!$comment || $comment['user_id'] !== $userId) {
            return false;
        }

        return (bool) $this->delete($commentId);
    }

    /**
     * Update a comment
     * @param int $commentId Comment ID
     * @param int $userId User ID (for verification)
     * @param string $content New comment content
     * @return bool Success status
     */
    public function updateComment($commentId, $userId, $content) {
        // Verify comment ownership
        $comment = $this->find($commentId);
        if (!$comment || $comment['user_id'] !== $userId) {
            return false;
        }

        return (bool) $this->update($commentId, [
            'content' => $content
        ]);
    }
} 