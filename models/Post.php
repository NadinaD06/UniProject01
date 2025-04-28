<?php
/**
 * Post Model
 * Handles all post data operations
 */
class Post {
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
     * Get post by ID
     * 
     * @param int $id Post ID
     * @return array|bool Post data or false if not found
     */
    public function getPostById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, 
                p.category, p.tags, p.used_ai, p.ai_tools, p.comments_enabled,
                p.nsfw, p.created_at, u.username, u.profile_picture
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new post
     * 
     * @param array $postData Post data
     * @return int|bool New post ID or false on failure
     */
    public function createPost($postData) {
        $stmt = $this->conn->prepare("
            INSERT INTO posts (
                user_id, title, description, image_url, category, 
                tags, comments_enabled, used_ai, ai_tools, nsfw, 
                created_at
            ) VALUES (
                :user_id, :title, :description, :image_url, :category, 
                :tags, :comments_enabled, :used_ai, :ai_tools, :nsfw, 
                NOW()
            )
        ");
        
        $commentsEnabled = isset($postData['comments_enabled']) ? 1 : 0;
        $usedAI = isset($postData['used_ai']) ? 1 : 0;
        $nsfw = isset($postData['nsfw']) ? 1 : 0;
        
        $stmt->bindParam(':user_id', $postData['user_id']);
        $stmt->bindParam(':title', $postData['title']);
        $stmt->bindParam(':description', $postData['description'] ?? null);
        $stmt->bindParam(':image_url', $postData['image_url']);
        $stmt->bindParam(':category', $postData['category']);
        $stmt->bindParam(':tags', $postData['tags'] ?? null);
        $stmt->bindParam(':comments_enabled', $commentsEnabled);
        $stmt->bindParam(':used_ai', $usedAI);
        $stmt->bindParam(':ai_tools', $postData['ai_tools'] ?? null);
        $stmt->bindParam(':nsfw', $nsfw);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update post
     * 
     * @param int $postId Post ID
     * @param array $postData Post data to update
     * @return bool Success status
     */
    public function updatePost($postId, $postData) {
        $fields = [];
        $params = [':post_id' => $postId];
        
        // Build dynamic query based on provided fields
        foreach ($postData as $key => $value) {
            if ($key !== 'id' && $key !== 'user_id') {
                if ($key === 'comments_enabled' || $key === 'used_ai' || $key === 'nsfw') {
                    $value = isset($value) ? 1 : 0;
                }
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = :post_id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
    }
    
    /**
     * Delete post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID (for authorization)
     * @return bool Success status
     */
    public function deletePost($postId, $userId) {
        // First check if the user owns the post
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        
        $postUserId = $stmt->fetchColumn();
        
        // If user doesn't own post and is not an admin, fail
        if ($postUserId != $userId) {
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
        
        // Delete the post
        $stmt = $this->conn->prepare("
            DELETE FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        
        return $stmt->execute();
    }
    
    /**
     * Get user posts
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @param string $sort Sort direction (recent, popular, oldest)
     * @return array List of posts
     */
    public function getUserPosts($userId, $limit = 10, $offset = 0, $sort = 'recent') {
        $orderBy = 'p.created_at DESC'; // Default sorting (recent)
        
        if ($sort === 'popular') {
            $orderBy = 'likes_count DESC, comments_count DESC, p.created_at DESC';
        } elseif ($sort === 'oldest') {
            $orderBy = 'p.created_at ASC';
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.title, p.description, p.image_url, p.category,
                p.tags, p.used_ai, p.ai_tools, p.created_at,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
            FROM posts p
            WHERE p.user_id = :user_id
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get feed posts (posts from followed users + trending)
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @param bool $followingOnly Only show posts from followed users
     * @param string $category Filter by category
     * @return array List of posts with user interactions
     */
    public function getFeedPosts($userId, $limit = 10, $offset = 0, $followingOnly = false, $category = '') {
        $whereClause = [];
        $params = [':user_id' => $userId];
        
        if ($followingOnly) {
            $whereClause[] = "p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = :user_id
                UNION
                SELECT :user_id
            )";
        }
        
        if (!empty($category)) {
            $whereClause[] = "p.category = :category";
            $params[':category'] = $category;
        }
        
        // Exclude posts from blocked users
        $whereClause[] = "p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
            UNION
            SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
        )";
        
        $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
        
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, 
                p.category, p.tags, p.used_ai, p.ai_tools, p.created_at,
                u.username as author_username, u.profile_picture as author_profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :user_id
            LEFT JOIN saved_posts s ON s.post_id = p.id AND s.user_id = :user_id
            $whereSQL
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent comments for each post
        foreach ($posts as &$post) {
            $post['comments'] = $this->getPostComments($post['id'], 2);
        }
        
        return $posts;
    }
    
    /**
     * Get trending posts
     * 
     * @param int $userId User ID (for personalization)
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @param string $category Filter by category
     * @return array List of posts
     */
    public function getTrendingPosts($userId, $limit = 10, $offset = 0, $category = '') {
        $whereClause = [];
        $params = [':user_id' => $userId];
        
        if (!empty($category)) {
            $whereClause[] = "p.category = :category";
            $params[':category'] = $category;
        }
        
        // Exclude posts from blocked users
        $whereClause[] = "p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
            UNION
            SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
        )";
        
        // Only include posts from last 7 days for trending
        $whereClause[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
        
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, 
                p.category, p.tags, p.used_ai, p.ai_tools, p.created_at,
                u.username as author_username, u.profile_picture as author_profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :user_id
            LEFT JOIN saved_posts s ON s.post_id = p.id AND s.user_id = :user_id
            $whereSQL
            ORDER BY (likes_count + comments_count * 2) DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search posts
     * 
     * @param string $query Search query
     * @param int $userId User ID (for personalization)
     * @param int $limit Result limit
     * @return array List of posts
     */
    public function searchPosts($query, $userId, $limit = 10) {
        $searchTerm = "%$query%";
        
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.title, p.description, p.image_url, p.category,
                p.tags, p.created_at, u.id as user_id, u.username
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE (p.title LIKE :query OR p.description LIKE :query OR p.tags LIKE :query)
            AND p.user_id NOT IN (
                SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
                UNION
                SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
            )
            ORDER BY p.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get post comments
     * 
     * @param int $postId Post ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of comments
     */
    public function getPostComments($postId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.user_id, c.content, c.created_at,
                u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at DESC
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
     * Add comment to post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID
     * @param string $content Comment content
     * @return int|bool New comment ID or false on failure
     */
    public function addComment($postId, $userId, $content) {
        // First check if comments are enabled for this post
        $stmt = $this->conn->prepare("
            SELECT comments_enabled 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        
        $commentsEnabled = $stmt->fetchColumn();
        
        if (!$commentsEnabled) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO comments (post_id, user_id, content, created_at)
            VALUES (:post_id, :user_id, :content, NOW())
        ");
        
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':content', $content);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Like or unlike a post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return array Result with action (liked/unliked) and count
     */
    public function toggleLike($postId, $userId) {
        // Check if already liked
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM likes 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $likeExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($likeExists) {
            // Unlike
            $stmt = $this->conn->prepare("
                DELETE FROM likes 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'unliked';
        } else {
            // Like
            $stmt = $this->conn->prepare("
                INSERT INTO likes (post_id, user_id, created_at)
                VALUES (:post_id, :user_id, NOW())
            ");
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'liked';
            
            // Create notification for post owner
            $this->createLikeNotification($postId, $userId);
        }
        
        // Get updated like count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM likes 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        $likesCount = $stmt->fetchColumn();
        
        return [
            'action' => $action,
            'likes_count' => $likesCount
        ];
    }
    
    /**
     * Create notification for post like
     * 
     * @param int $postId Post ID
     * @param int $actorId User ID who liked
     * @return bool Success status
     */
    private function createLikeNotification($postId, $actorId) {
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
        
        // Don't notify if liking own post
        if ($result['user_id'] == $actorId) {
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
        $stmt->bindParam(':actor_id', $actorId);
        $stmt->bindParam(':entity_id', $postId);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    /**
     * Save or unsave a post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return array Result with action (saved/unsaved)
     */
    public function toggleSave($postId, $userId) {
        // Check if already saved
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM saved_posts 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $saveExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($saveExists) {
            // Unsave
            $stmt = $this->conn->prepare("
                DELETE FROM saved_posts 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'unsaved';
        } else {
            // Save
            $stmt = $this->conn->prepare("
                INSERT INTO saved_posts (post_id, user_id, created_at)
                VALUES (:post_id, :user_id, NOW())
            ");
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $action = 'saved';
        }
        
        return [
            'action' => $action
        ];
    }
    
    /**
     * Get saved posts
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of saved posts
     */
    public function getSavedPosts($userId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, 
                p.category, p.tags, p.used_ai, p.created_at,
                u.username as author_username, u.profile_picture as author_profile_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                sp.created_at as saved_at
            FROM saved_posts sp
            JOIN posts p ON sp.post_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE sp.user_id = :user_id
            ORDER BY sp.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Report a post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID reporting
     * @param string $reason Report reason
     * @param string $details Additional details
     * @return bool Success status
     */
    public function reportPost($postId, $userId, $reason, $details = null) {
        // Get post owner
        $stmt = $this->conn->prepare("
            SELECT user_id 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        
        $postOwnerId = $stmt->fetchColumn();
        
        // Don't allow reporting own post
        if ($postOwnerId == $userId) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO post_reports (
                post_id, reporter_id, reason, details, created_at
            ) VALUES (
                :post_id, :reporter_id, :reason, :details, NOW()
            )
        ");
        
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':reporter_id', $userId);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':details', $details);
        
        return $stmt->execute();
    }
    
    /**
     * Get post metrics
     * 
     * @param int $postId Post ID
     * @return array Post metrics (views, likes, comments)
     */
    public function getPostMetrics($postId) {
        // Get views
        $stmt = $this->conn->prepare("
            SELECT views 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        $views = $stmt->fetchColumn() ?: 0;
        
        // Get likes count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM likes 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        $likesCount = $stmt->fetchColumn();
        
        // Get comments count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM comments 
            WHERE post_id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();
        $commentsCount = $stmt->fetchColumn();
        
        return [
            'views' => $views,
            'likes' => $likesCount,
            'comments' => $commentsCount
        ];
    }
    
    /**
     * Increment post view count
     * 
     * @param int $postId Post ID
     * @return bool Success status
     */
    public function incrementViews($postId) {
        $stmt = $this->conn->prepare("
            UPDATE posts 
            SET views = views + 1 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $postId);
        
        return $stmt->execute();
    }
    
    /**
     * Get trending tags from posts
     * 
     * @param int $limit Result limit
     * @return array List of trending tags with counts
     */
    public function getTrendingTags($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 
                TRIM(tag) as name, 
                COUNT(*) as count
            FROM (
                SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(tags, ','), ',', n.n), ',', -1) as tag
                FROM 
                    posts,
                    (SELECT a.N + b.N * 10 + 1 as n
                     FROM 
                         (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                         (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                    ) n
                WHERE 
                    n.n <= 1 + LENGTH(tags) - LENGTH(REPLACE(tags, ',', ''))
                    AND tags IS NOT NULL
                    AND tags != ''
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as subquery
            WHERE 
                CHAR_LENGTH(tag) > 1
            GROUP BY tag
            ORDER BY count DESC, name
            LIMIT :limit
        ");
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}