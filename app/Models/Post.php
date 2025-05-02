<?php
/**
 * Post Model
 * Handles post-related database operations
 */
namespace App\Models;

class Post extends Model {
    protected $table = 'posts';
    protected $fillable = [
        'user_id',
        'content',
        'image_url',
        'location_name',
        'latitude',
        'longitude'
    ];

    /**
     * Get post with user details
     * @param int $postId Post ID
     * @return array|false
     */
    public function getWithUser($postId) {
        return $this->db->fetch("
            SELECT p.*, u.username, u.email
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ", [$postId]);
    }

    /**
     * Get posts for user's feed
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Posts per page
     * @return array
     */
    public function getFeed($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $posts = $this->db->fetchAll("
            SELECT 
                p.*,
                u.username,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
                OR p.user_id IN (SELECT follows_id FROM follows WHERE user_id = ?)
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userId, $userId, $userId, $perPage, $offset]);

        $total = $this->db->fetch("
            SELECT COUNT(*) as count
            FROM {$this->table} p
            WHERE p.user_id = ?
                OR p.user_id IN (SELECT follows_id FROM follows WHERE user_id = ?)
        ", [$userId, $userId])['count'];

        return [
            'data' => $posts,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Create new post
     * @param array $data Post data
     * @param array $file Optional image file
     * @return int Post ID
     */
    public function createPost($data, $file = null) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $config = require __DIR__ . '/../config/config.php';
            $uploadDir = $config['upload']['directory'];
            $allowedTypes = $config['upload']['allowed_types'];
            $maxSize = $config['upload']['max_size'];

            // Validate file
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > $maxSize) {
                throw new Exception('File size exceeds limit');
            }

            if (!in_array($extension, $allowedTypes)) {
                throw new Exception('Invalid file type');
            }

            // Generate unique filename
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }

            $data['image_url'] = $filename;
        }

        return $this->create($data);
    }

    /**
     * Like a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return bool
     */
    public function like($postId, $userId) {
        try {
            $this->db->insert('likes', [
                'post_id' => $postId,
                'user_id' => $userId
            ]);
            return true;
        } catch (PDOException $e) {
            // Ignore duplicate entry errors
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Unlike a post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return bool
     */
    public function unlike($postId, $userId) {
        return (bool) $this->db->delete(
            'likes',
            'post_id = ? AND user_id = ?',
            [$postId, $userId]
        );
    }

    /**
     * Add comment to post
     * @param int $postId Post ID
     * @param int $userId User ID
     * @param string $content Comment content
     * @return int Comment ID
     */
    public function addComment($postId, $userId, $content) {
        return $this->db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content
        ]);
    }

    /**
     * Get post comments
     * @param int $postId Post ID
     * @param int $page Page number
     * @param int $perPage Comments per page
     * @return array
     */
    public function getComments($postId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $comments = $this->db->fetchAll("
            SELECT c.*, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ", [$postId, $perPage, $offset]);

        $total = $this->db->fetch("
            SELECT COUNT(*) as count
            FROM comments
            WHERE post_id = ?
        ", [$postId])['count'];

        return [
            'data' => $comments,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get posts by location
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param float $radius Radius in kilometers
     * @param int $page Page number
     * @param int $perPage Posts per page
     * @return array
     */
    public function getByLocation($lat, $lng, $radius = 10, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        // Haversine formula to calculate distance
        $posts = $this->db->fetchAll("
            SELECT 
                p.*,
                u.username,
                (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            HAVING distance < ?
            ORDER BY distance
            LIMIT ? OFFSET ?
        ", [$lat, $lng, $lat, $radius, $perPage, $offset]);

        return [
            'data' => $posts,
            'per_page' => $perPage,
            'current_page' => $page
        ];
    }

    /**
     * Get post statistics for admin
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
                COUNT(DISTINCT user_id) as user_count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL {$interval})
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
    }
} 