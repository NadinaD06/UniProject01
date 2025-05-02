<?php
/**
 * Post Model
 * Handles post-related operations including creation, retrieval, and interaction
 */
namespace App\Models;

use App\Core\Model;

class Post extends Model {
    protected $table = 'posts';
    
    /**
     * Create a new post
     */
    public function create($data) {
        $sql = "INSERT INTO posts (user_id, content, image_url, location_lat, location_lng, created_at) 
                VALUES (:user_id, :content, :image_url, :location_lat, :location_lng, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'image_url' => $data['image'] ?? null,
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null
        ]);
    }
    
    /**
     * Get posts for feed
     */
    public function getFeed($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id IN (
                    SELECT friend_id FROM friendships WHERE user_id = :user_id
                    UNION SELECT :user_id
                )
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get post by ID
     */
    public function getById($postId, $userId) {
        $sql = "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :post_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => $userId
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get user's posts
     */
    public function getByUser($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Delete post
     */
    public function delete($id) {
        $sql = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get post statistics for admin
     */
    public function getStats($period = 'week') {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as post_count,
                COUNT(DISTINCT user_id) as user_count
                FROM posts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 " . $period . ")
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
} 