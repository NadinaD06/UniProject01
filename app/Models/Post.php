<?php
/**
 * Post Model
 * Handles post-related operations including creation, retrieval, and interaction
 */
namespace App\Models;

class Post {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new post
     * 
     * @param int $userId
     * @param string $content
     * @param string|null $imageUrl
     * @param float|null $lat
     * @param float|null $lng
     * @return bool
     */
    public function create($userId, $content, $imageUrl = null, $lat = null, $lng = null) {
        $sql = "INSERT INTO posts (user_id, content, image_url, location_lat, location_lng) 
                VALUES (:user_id, :content, :image_url, :location_lat, :location_lng)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':content' => $content,
            ':image_url' => $imageUrl,
            ':location_lat' => $lat,
            ':location_lng' => $lng
        ]);
    }
    
    /**
     * Get all posts with user information
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllPosts($limit = 10, $offset = 0) {
        $sql = "SELECT p.*, u.username, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get post by ID
     * 
     * @param int $postId
     * @return array|false
     */
    public function getById($postId) {
        $sql = "SELECT p.*, u.username,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $postId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Like a post
     * 
     * @param int $postId
     * @param int $userId
     * @return bool
     */
    public function likePost($postId, $userId) {
        $sql = "INSERT INTO likes (post_id, user_id) VALUES (:post_id, :user_id)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Unlike a post
     * 
     * @param int $postId
     * @param int $userId
     * @return bool
     */
    public function unlikePost($postId, $userId) {
        $sql = "DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Add a comment to a post
     * 
     * @param int $postId
     * @param int $userId
     * @param string $content
     * @return bool
     */
    public function addComment($postId, $userId, $content) {
        $sql = "INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':content' => $content
        ]);
    }
    
    /**
     * Get comments for a post
     * 
     * @param int $postId
     * @return array
     */
    public function getComments($postId) {
        $sql = "SELECT c.*, u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':post_id' => $postId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get post statistics for admin
     * 
     * @param string $period week|month|year
     * @return array
     */
    public function getPostStats($period) {
        $dateFormat = match($period) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };
        
        $sql = "SELECT 
                DATE_FORMAT(created_at, :date_format) as period,
                COUNT(*) as post_count,
                COUNT(DISTINCT user_id) as user_count
                FROM posts
                GROUP BY period
                ORDER BY period DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':date_format' => $dateFormat]);
        
        return $stmt->fetchAll();
    }
} 