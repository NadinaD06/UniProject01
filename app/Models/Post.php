<?php
/**
 * Post Model
 * Handles post-related operations including creation, retrieval, and interaction
 */
namespace App\Models;

class Post extends Model {
    protected $table = 'posts';
    protected $fillable = [
        'user_id',
        'content',
        'image_path',
        'location_lat',
        'location_lng',
        'location_name'
    ];

    /**
     * Create a new post
     * @param int $userId User ID
     * @param string $content Post content
     * @param string|null $imagePath Image path
     * @param float|null $locationLat Location latitude
     * @param float|null $locationLng Location longitude
     * @param string|null $locationName Location name
     * @return int|bool Post ID or false on failure
     */
    public function createPost($userId, $content, $imagePath = null, $locationLat = null, $locationLng = null, $locationName = null) {
        try {
            $sql = "INSERT INTO posts (user_id, content, image_path, location_lat, location_lng, location_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $content, $imagePath, $locationLat, $locationLng, $locationName]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a post with its author and interaction data
     * @param int $postId Post ID
     * @param int $currentUserId Current user ID for interaction data
     * @return array|bool Post data or false if not found
     */
    public function getPost($postId, $currentUserId = null) {
        $post = $this->db->fetch(
            "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?",
            [$postId]
        );

        if (!$post) {
            return false;
        }

        // Add current user's interaction data if user is logged in
        if ($currentUserId) {
            $post['is_liked'] = (bool) $this->db->fetch(
                "SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?",
                [$postId, $currentUserId]
            );
        }

        return $post;
    }

    /**
     * Get posts for a user's feed
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Posts per page
     * @return array
     */
    public function getFeed($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                (SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = ?
            )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?",
            [$userId, $userId, $userId, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} p
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = ?
            )",
            [$userId, $userId]
        )['count'];

        return [
            'data' => $posts,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get posts by a specific user
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Posts per page
     * @return array
     */
    public function getUserPosts($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?",
            [$userId]
        )['count'];

        return [
            'data' => $posts,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get post statistics
     * @param string $period 'week'|'month'|'year'
     * @return array
     */
    public function getStats($period = 'week') {
        $intervals = [
            'week' => '7 DAY',
            'month' => '1 MONTH',
            'year' => '1 YEAR'
        ];

        $interval = $intervals[$period] ?? '7 DAY';

        return $this->db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as post_count,
                COUNT(DISTINCT user_id) as user_count,
                COUNT(CASE WHEN image_path IS NOT NULL THEN 1 END) as image_count,
                COUNT(CASE WHEN location_lat IS NOT NULL THEN 1 END) as location_count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL {$interval})
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
    }

    /**
     * Delete a post
     * @param int $postId Post ID
     * @param int $userId User ID (for verification)
     * @return bool Success status
     */
    public function deletePost($postId, $userId) {
        // Verify post ownership
        $post = $this->find($postId);
        if (!$post || $post['user_id'] !== $userId) {
            return false;
        }

        return (bool) $this->delete($postId);
    }
} 