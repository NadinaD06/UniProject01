<?php
/**
 * Like Model
 * Handles all like data operations
 */
class Like {
    private $conn;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get like by ID
     * 
     * @param int $id Like ID
     * @return array|bool Like data or false if not found
     */
    public function getLikeById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                l.id, l.post_id, l.user_id, l.created_at,
                u.username
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if user has liked a post
     * 
     * @param int $post_id Post ID
     * @param int $user_id User ID
     * @return bool True if user has liked the post
     */
    public function hasUserLiked($post_id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM likes 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Toggle like status (like/unlike)
     * 
     * @param int $post_id Post ID
     * @param int $user_id User ID
     * @return array Result with action (liked/unliked) and count
     */
    public function toggleLike($post_id, $user_id) {
        // Check if already liked
        $stmt = $this->conn->prepare("
            SELECT id FROM likes 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $like_exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($like_exists) {
            // Unlike
            $stmt = $this->conn->prepare("
                DELETE FROM likes 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'unliked';
        } else {
            // Like
            $stmt = $this->conn->prepare("
                INSERT INTO likes (post_id, user_id, created_at)
                VALUES (:post_id, :user_id, NOW())
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'liked';
            
            // Create notification for post owner (if not self-like)
            $this->createLikeNotification($post_id, $user_id);
        }
        
        // Get updated like count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM likes 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        $likes_count = $stmt->fetchColumn();
        
        return [
            'action' => $action,
            'likes_count' => $likes_count
        ];
    }
    
    /**
     * Create a notification for post like
     * 
     * @param int $post_id Post ID
     * @param int $liker_id User ID who liked the post
     * @return bool Success status
     */
    private function createLikeNotification($post_id, $liker_id) {
        // Get post owner and post title
        $stmt = $this->conn->prepare("
            SELECT p.user_id, p.title, u.username
            FROM posts p
            JOIN users u ON u.id = :liker_id
            WHERE p.id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':liker_id', $liker_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Don't notify if liking own post
        if (!$result || $result['user_id'] == $liker_id) {
            return false;
        }
        
        $message = $result['username'] . ' liked your post "' . $result['title'] . '"';
        
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, type, actor_id, entity_id, message, created_at
            ) VALUES (
                :user_id, 'like', :actor_id, :entity_id, :message, NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $result['user_id']);
        $stmt->bindParam(':actor_id', $liker_id);
        $stmt->bindParam(':entity_id', $post_id);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    /**
     * Get likes for a post
     * 
     * @param int $post_id Post ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of likes
     */
    public function getPostLikes($post_id, $limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                l.id, l.user_id, l.created_at,
                u.username, u.profile_picture
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = :post_id
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the results
        $formatted_likes = [];
        foreach ($likes as $like) {
            $formatted_likes[] = [
                'id' => $like['id'],
                'created_at' => $like['created_at'],
                'user' => [
                    'id' => $like['user_id'],
                    'username' => $like['username'],
                    'profile_picture' => $like['profile_picture'] ?: '/api/placeholder/32/32'
                ]
            ];
        }
        
        return $formatted_likes;
    }
    
    /**
     * Get like count for a post
     * 
     * @param int $post_id Post ID
     * @return int Like count
     */
    public function getLikeCount($post_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM likes 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get users who liked a post
     * 
     * @param int $post_id Post ID
     * @param int $limit Result limit
     * @return array Users who liked the post
     */
    public function getLikeUsers($post_id, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, u.username, u.profile_picture
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = :post_id
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get posts liked by a user
     * 
     * @param int $user_id User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array Posts liked by the user
     */
    public function getUserLikedPosts($user_id, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.title, p.description, p.image_url, p.category,
                p.tags, p.created_at, p.user_id,
                u.username as author_username, u.profile_picture as author_profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                l.created_at as liked_at
            FROM likes l
            JOIN posts p ON l.post_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE l.user_id = :user_id
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete likes for a post
     * 
     * @param int $post_id Post ID
     * @return bool Success status
     */
    public function deleteLikesForPost($post_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM likes 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete likes for a user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function deleteLikesForUser($user_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM likes 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get recent likers for a post
     * 
     * @param int $post_id Post ID
     * @param int $limit Limit of results
     * @return array Recent likers
     */
    public function getRecentLikers($post_id, $limit = 3) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, u.username, u.profile_picture
            FROM likes l
            JOIN users u ON l.user_id = u.id
            WHERE l.post_id = :post_id
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}