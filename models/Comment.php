<?php
/**
 * Comment Model
 * Handles all comment data operations
 */
class Comment {
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
     * Get comment by ID
     * 
     * @param int $id Comment ID
     * @return array|bool Comment data or false if not found
     */
    public function getCommentById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.post_id, c.user_id, c.content, c.created_at,
                u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new comment
     * 
     * @param array $commentData Comment data
     * @return int|bool New comment ID or false on failure
     */
    public function createComment($commentData) {
        // First check if comments are enabled for this post
        $stmt = $this->conn->prepare("
            SELECT comments_enabled 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $commentData['post_id']);
        $stmt->execute();
        
        $commentsEnabled = $stmt->fetchColumn();
        
        if (!$commentsEnabled) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO comments (
                post_id, user_id, content, created_at
            ) VALUES (
                :post_id, :user_id, :content, NOW()
            )
        ");
        
        $stmt->bindParam(':post_id', $commentData['post_id']);
        $stmt->bindParam(':user_id', $commentData['user_id']);
        $stmt->bindParam(':content', $commentData['content']);
        
        if ($stmt->execute()) {
            $commentId = $this->conn->lastInsertId();
            
            // Create notification for post owner
            $this->createCommentNotification($commentData['post_id'], $commentData['user_id'], $commentId);
            
            return $commentId;
        }
        
        return false;
    }
    
    /**
     * Update comment
     * 
     * @param int $commentId Comment ID
     * @param string $content Updated content
     * @param int $userId User ID (for authorization)
     * @return bool Success status
     */
    public function updateComment($commentId, $content, $userId) {
        // Check if user owns the comment
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM comments 
            WHERE id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->execute();
        
        $commentUserId = $stmt->fetchColumn();
        
        // If user doesn't own comment, fail
        if ($commentUserId != $userId) {
            // Check if user is admin
            $stmt = $this->conn->prepare("
                SELECT is_admin 
                FROM users 
                WHERE id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $isAdmin = $stmt->fetchColumn();
            
            if (!$isAdmin) {
                return false;
            }
        }
        
        $stmt = $this->conn->prepare("
            UPDATE comments 
            SET content = :content 
            WHERE id = :comment_id
        ");
        
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':comment_id', $commentId);
        
        return $stmt->execute();
    }
    
    /**
     * Delete comment
     * 
     * @param int $commentId Comment ID
     * @param int $userId User ID (for authorization)
     * @return bool Success status
     */
    public function deleteComment($commentId, $userId) {
        // Check if user owns the comment
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM comments 
            WHERE id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->execute();
        
        $commentUserId = $stmt->fetchColumn();
        
        // If user doesn't own comment, check if user owns the post
        if ($commentUserId != $userId) {
            $stmt = $this->conn->prepare("
                SELECT p.user_id 
                FROM comments c
                JOIN posts p ON c.post_id = p.id
                WHERE c.id = :comment_id
            ");
            $stmt->bindParam(':comment_id', $commentId);
            $stmt->execute();
            
            $postOwnerId = $stmt->fetchColumn();
            
            // If user doesn't own comment or post, check if user is admin
            if ($postOwnerId != $userId) {
                $stmt = $this->conn->prepare("
                    SELECT is_admin 
                    FROM users 
                    WHERE id = :user_id
                ");
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                
                $isAdmin = $stmt->fetchColumn();
                
                if (!$isAdmin) {
                    return false;
                }
            }
        }
        
        $stmt = $this->conn->prepare("
            DELETE FROM comments 
            WHERE id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        
        return $stmt->execute();
    }
    
    /**
     * Get comments for a post
     * 
     * @param int $postId Post ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @param string $sort Sort direction (recent, oldest)
     * @return array List of comments
     */
    public function getPostComments($postId, $limit = 10, $offset = 0, $sort = 'recent') {
        $orderBy = $sort === 'recent' ? 'c.created_at DESC' : 'c.created_at ASC';
        
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.user_id, c.content, c.created_at,
                u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the comments for output
        foreach ($comments as &$comment) {
            $comment['user'] = [
                'id' => $comment['user_id'],
                'username' => $comment['username'],
                'profile_picture' => $comment['profile_picture'] ?: '/api/placeholder/32/32'
            ];
            unset($comment['user_id'], $comment['username'], $comment['profile_picture']);
        }
        
        return $comments;
    }
    
    /**
     * Get user comments
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of comments with post info
     */
    public function getUserComments($userId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.post_id, c.content, c.created_at,
                p.title as post_title, p.image_url as post_image,
                u.username as post_owner_username
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Report a comment
     * 
     * @param int $commentId Comment ID
     * @param int $userId User ID reporting
     * @param string $reason Report reason
     * @param string $details Additional details
     * @return bool Success status
     */
    public function reportComment($commentId, $userId, $reason, $details = null) {
        // Get comment owner
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM comments 
            WHERE id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->execute();
        
        $commentOwnerId = $stmt->fetchColumn();
        
        // Don't allow reporting own comment
        if ($commentOwnerId == $userId) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO comment_reports (
                comment_id, reporter_id, reason, details, created_at
            ) VALUES (
                :comment_id, :reporter_id, :reason, :details, NOW()
            )
        ");
        
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->bindParam(':reporter_id', $userId);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':details', $details);
        
        return $stmt->execute();
    }
    
    /**
     * Like or unlike a comment
     * 
     * @param int $commentId Comment ID
     * @param int $userId User ID
     * @return array Result with action (liked/unliked) and count
     */
    public function toggleCommentLike($commentId, $userId) {
        // Check if already liked
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM comment_likes 
            WHERE comment_id = :comment_id AND user_id = :user_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $likeExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($likeExists) {
            // Unlike
            $stmt = $this->conn->prepare("
                DELETE FROM comment_likes 
                WHERE comment_id = :comment_id AND user_id = :user_id
            ");
            $stmt->bindParam(':comment_id', $commentId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'unliked';
        } else {
            // Like
            $stmt = $this->conn->prepare("
                INSERT INTO comment_likes (comment_id, user_id, created_at)
                VALUES (:comment_id, :user_id, NOW())
            ");
            $stmt->bindParam(':comment_id', $commentId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'liked';
            
            // Get comment owner and create notification
            $stmt = $this->conn->prepare("
                SELECT 
                    c.user_id, 
                    p.id as post_id 
                FROM comments c
                JOIN posts p ON c.post_id = p.id
                WHERE c.id = :comment_id
            ");
            $stmt->bindParam(':comment_id', $commentId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Don't notify if liking own comment
            if ($result['user_id'] != $userId) {
                $this->createCommentLikeNotification($commentId, $userId, $result['user_id'], $result['post_id']);
            }
        }
        
        // Get updated like count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM comment_likes 
            WHERE comment_id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->execute();
        $likesCount = $stmt->fetchColumn();
        
        return [
            'action' => $action,
            'likes_count' => $likesCount
        ];
    }
    
    /**
     * Create notification for new comment
     * 
     * @param int $postId Post ID
     * @param int $actorId User ID who commented
     * @param int $commentId Comment ID
     * @return bool Success status
     */
    private function createCommentNotification($postId, $actorId, $commentId) {
        // Get post owner
        $stmt = $this->conn->prepare("
            SELECT p.user_id, p.title, u.username
            FROM posts p
            JOIN users u ON u.id = :actor_id
            WHERE p.id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':actor_id', $actorId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Don't notify if commenting on own post
        if ($result['user_id'] == $actorId) {
            return false;
        }
        
        $message = $result['username'] . ' commented on your post "' . $result['title'] . '"';
        
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, type, actor_id, entity_id, reference_id, message, created_at
            ) VALUES (
                :user_id, 'comment', :actor_id, :post_id, :comment_id, :message, NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $result['user_id']);
        $stmt->bindParam(':actor_id', $actorId);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    /**
     * Create notification for comment like
     * 
     * @param int $commentId Comment ID
     * @param int $actorId User ID who liked
     * @param int $receiverId User ID receiving notification
     * @param int $postId Post ID
     * @return bool Success status
     */
    private function createCommentLikeNotification($commentId, $actorId, $receiverId, $postId) {
        // Get actor username
        $stmt = $this->conn->prepare("
            SELECT username
            FROM users
            WHERE id = :actor_id
        ");
        $stmt->bindParam(':actor_id', $actorId);
        $stmt->execute();
        
        $username = $stmt->fetchColumn();
        
        $message = $username . ' liked your comment';
        
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, type, actor_id, entity_id, reference_id, message, created_at
            ) VALUES (
                :user_id, 'comment_like', :actor_id, :comment_id, :post_id, :message, NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $receiverId);
        $stmt->bindParam(':actor_id', $actorId);
        $stmt->bindParam(':comment_id', $commentId);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    /**
     * Search comments
     * 
     * @param string $query Search query
     * @param int $userId User ID (for filtering)
     * @param int $limit Result limit
     * @return array List of matching comments
     */
    public function searchComments($query, $userId, $limit = 10) {
        $searchTerm = "%$query%";
        
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.post_id, c.content, c.created_at,
                p.title as post_title,
                u.username as commenter_username,
                pu.username as post_owner_username
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            JOIN users u ON c.user_id = u.id
            JOIN users pu ON p.user_id = pu.id
            WHERE c.content LIKE :query
            AND c.user_id NOT IN (
                SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
                UNION
                SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
            )
            ORDER BY c.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get comment stats for a user
     * 
     * @param int $userId User ID
     * @return array Comment statistics
     */
    public function getUserCommentStats($userId) {
        // Total comments
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM comments 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $totalComments = $stmt->fetchColumn();
        
        // Total comment likes received
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM comment_likes cl
            JOIN comments c ON cl.comment_id = c.id
            WHERE c.user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $totalLikesReceived = $stmt->fetchColumn();
        
        // Most liked comment
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.content, c.post_id, p.title as post_title,
                COUNT(cl.id) as likes_count
            FROM comments c
            LEFT JOIN comment_likes cl ON c.id = cl.comment_id
            JOIN posts p ON c.post_id = p.id
            WHERE c.user_id = :user_id
            GROUP BY c.id
            ORDER BY likes_count DESC
            LIMIT 1
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $mostLikedComment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_comments' => $totalComments,
            'total_likes_received' => $totalLikesReceived,
            'most_liked_comment' => $mostLikedComment
        ];
    }
}