<?php
/**
* app/Models/Post.php
* Model for handling posts, likes, comments, and related operations
**/

namespace App\Models;

use App\Core\Model;

class Post extends Model {
    protected $table = 'posts';
    
    /**
     * Get post by ID
     * 
     * @param int $postId Post ID
     * @param int $userId Current user ID for personalized data
     * @return array|bool Post data or false if not found
     */
    public function getPost($postId, $userId = null) {
        $sql = "
            SELECT 
                p.*, 
                u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
        ";
        
        // Add personalized fields if user ID provided
        if ($userId) {
            $sql .= "
                , (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                , (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
            ";
        }
        
        $sql .= "
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ";
        
        $params = $userId ? [$userId, $userId, $postId] : [$postId];
        
        $post = $this->db->fetch($sql, $params);
        
        if ($post) {
            // Add formatted properties
            $post['created_at_formatted'] = date('F j, Y', strtotime($post['created_at']));
            $post['author_username'] = $post['username'];
            $post['author_profile_pic'] = $post['profile_picture'] ?: '/assets/images/default-avatar.png';
            
            // Convert numeric fields
            $post['likes_count'] = (int)$post['likes_count'];
            $post['comments_count'] = (int)$post['comments_count'];
            
            // Convert boolean fields
            $post['comments_enabled'] = (bool)$post['comments_enabled'];
            $post['used_ai'] = (bool)$post['used_ai'];
            $post['nsfw'] = (bool)$post['nsfw'];
            
            if ($userId) {
                $post['user_liked'] = (bool)$post['user_liked'];
                $post['user_saved'] = (bool)$post['user_saved'];
            }
        }
        
        return $post;
    }
    
    /**
     * Create a new post
     * 
     * @param array $postData Post data
     * @return int|bool New post ID or false on failure
     */
    public function createPost($postData) {
        // Ensure boolean fields are set
        $postData['comments_enabled'] = isset($postData['comments_enabled']) ? 1 : 0;
        $postData['used_ai'] = isset($postData['used_ai']) ? 1 : 0;
        $postData['nsfw'] = isset($postData['nsfw']) ? 1 : 0;
        
        // Set created date
        $postData['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($postData);
    }
    
    /**
     * Update a post
     * 
     * @param int $postId Post ID
     * @param array $postData Post data
     * @return bool Success status
     */
    public function updatePost($postId, $postData) {
        // Ensure boolean fields are properly formatted
        if (isset($postData['comments_enabled'])) {
            $postData['comments_enabled'] = $postData['comments_enabled'] ? 1 : 0;
        }
        
        if (isset($postData['used_ai'])) {
            $postData['used_ai'] = $postData['used_ai'] ? 1 : 0;
        }
        
        if (isset($postData['nsfw'])) {
            $postData['nsfw'] = $postData['nsfw'] ? 1 : 0;
        }
        
        // Set updated date
        $postData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->update($postId, $postData);
    }
    
    /**
     * Get feed posts (posts from followed users or everyone)
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of posts
     * @param int $offset Offset for pagination
     * @param bool $followingOnly Only show posts from followed users
     * @param string $category Filter by category
     * @return array Posts data
     */
    public function getFeedPosts($userId, $limit = 10, $offset = 0, $followingOnly = false, $category = '') {
        $params = [$userId, $userId];
        
        $sql = "
            SELECT 
                p.*, 
                u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
        ";
        
        $where = [];
        
        // Filter by following if requested
        if ($followingOnly) {
            $where[] = "p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = ?
                UNION SELECT ?
            )";
            $params[] = $userId;
            $params[] = $userId; // Include user's own posts
        }
        
        // Filter by category if provided
        if (!empty($category)) {
            $where[] = "p.category = ?";
            $params[] = $category;
        }
        
        // Exclude posts from blocked users
        $where[] = "p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = ?
            UNION SELECT blocker_id FROM blocks WHERE blocked_id = ?
        )";
        $params[] = $userId;
        $params[] = $userId;
        
        // Include NSFW posts only if user has enabled the option
        $where[] = "(p.nsfw = 0 OR (SELECT show_nsfw FROM user_settings WHERE user_id = ?) = 1)";
        $params[] = $userId;
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add order and limit
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $posts = $this->db->fetchAll($sql, $params);
        
        // Process posts for display
        return $this->processPostsForDisplay($posts, $userId);
    }
    
    /**
     * Get trending posts
     * 
     * @param int $userId User ID for personalized data
     * @param int $limit Maximum number of posts
     * @param int $offset Offset for pagination
     * @param string $category Filter by category
     * @return array Posts data
     */
    public function getTrendingPosts($userId, $limit = 10, $offset = 0, $category = '') {
        $params = [$userId, $userId];
        
        $sql = "
            SELECT 
                p.*, 
                u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
        ";
        
        $where = [];
        
        // Filter by category if provided
        if (!empty($category)) {
            $where[] = "p.category = ?";
            $params[] = $category;
        }
        
        // Only include posts from last 7 days for trending
        $where[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        // Exclude posts from blocked users
        $where[] = "p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = ?
            UNION SELECT blocker_id FROM blocks WHERE blocked_id = ?
        )";
        $params[] = $userId;
        $params[] = $userId;
        
        // Include NSFW posts only if user has enabled the option
        $where[] = "(p.nsfw = 0 OR (SELECT show_nsfw FROM user_settings WHERE user_id = ?) = 1)";
        $params[] = $userId;
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add trending ordering (likes + comments * 2) and limit
        $sql .= " ORDER BY (likes_count + comments_count * 2) DESC, p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $posts = $this->db->fetchAll($sql, $params);
        
        // Process posts for display
        return $this->processPostsForDisplay($posts, $userId);
    }
    
    /**
     * Get posts by tag
     * 
     * @param string $tag Tag to search for
     * @param int $userId User ID for personalization
     * @param int $limit Maximum number of posts
     * @param int $offset Offset for pagination
     * @return array Posts data
     */
    public function getPostsByTag($tag, $userId, $limit = 10, $offset = 0) {
        $params = [$userId, $userId, "%$tag%"];
        
        $sql = "
            SELECT 
                p.*, 
                u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE p.tags LIKE ?
        ";
        
        // Exclude posts from blocked users
        $sql .= " AND p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = ?
            UNION SELECT blocker_id FROM blocks WHERE blocked_id = ?
        )";
        $params[] = $userId;
        $params[] = $userId;
        
        // Include NSFW posts only if user has enabled the option
        $sql .= " AND (p.nsfw = 0 OR (SELECT show_nsfw FROM user_settings WHERE user_id = ?) = 1)";
        $params[] = $userId;
        
        // Add order and limit
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $posts = $this->db->fetchAll($sql, $params);
        
        // Process posts for display
        return $this->processPostsForDisplay($posts, $userId);
    }
    
    /**
     * Search posts
     * 
     * @param string $query Search query
     * @param int $userId User ID for personalization
     * @param int $limit Maximum number of results
     * @return array Search results
     */
    public function searchPosts($query, $userId, $limit = 10) {
        $searchTerm = "%$query%";
        $params = [$userId, $userId, $searchTerm, $searchTerm, $searchTerm];
        
        $sql = "
            SELECT 
                p.*, 
                u.username, u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as user_saved
            FROM {$this->table} p
            JOIN users u ON p.user_id = u.id
            WHERE (p.title LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
        ";
        
        // Exclude posts from blocked users
        $sql .= " AND p.user_id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = ?
            UNION SELECT blocker_id FROM blocks WHERE blocked_id = ?
        )";
        $params[] = $userId;
        $params[] = $userId;
        
        // Include NSFW posts only if user has enabled the option
        $sql .= " AND (p.nsfw = 0 OR (SELECT show_nsfw FROM user_settings WHERE user_id = ?) = 1)";
        $params[] = $userId;
        
        // Add relevance scoring and limit
        $sql .= " ORDER BY 
            CASE 
                WHEN p.title LIKE ? THEN 3
                WHEN p.tags LIKE ? THEN 2
                ELSE 1
            END DESC,
            p.created_at DESC
            LIMIT ?";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $limit;
        
        $posts = $this->db->fetchAll($sql, $params);
        
        // Process posts for display
        return $this->processPostsForDisplay($posts, $userId);
    }
    
    /**
     * Toggle like status for a post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return array Result with action (liked/unliked) and count
     */
    public function toggleLike($postId, $userId) {
        // Check if already liked
        $sql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
        $likeExists = $this->db->fetch($sql, [$postId, $userId]);
        
        if ($likeExists) {
            // Unlike
            $this->db->delete('likes', 'post_id = ? AND user_id = ?', [$postId, $userId]);
            $action = 'unliked';
        } else {
            // Like
            $this->db->insert('likes', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $action = 'liked';
            
            // Create notification for post owner
            $this->createLikeNotification($postId, $userId);
        }
        
        // Get updated like count
        $sql = "SELECT COUNT(*) as count FROM likes WHERE post_id = ?";
        $result = $this->db->fetch($sql, [$postId]);
        $likesCount = $result ? (int)$result['count'] : 0;
        
        return [
            'action' => $action,
            'likes_count' => $likesCount
        ];
    }
    
    /**
     * Toggle save status for a post
     * 
     * @param int $postId Post ID
     * @param int $userId User ID
     * @return array Result with action (saved/unsaved)
     */
    public function toggleSave($postId, $userId) {
        // Check if already saved
        $sql = "SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?";
        $saveExists = $this->db->fetch($sql, [$postId, $userId]);
        
        if ($saveExists) {
            // Unsave
            $this->db->delete('saved_posts', 'post_id = ? AND user_id = ?', [$postId, $userId]);
            $action = 'unsaved';
        } else {
            // Save
            $this->db->insert('saved_posts', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $action = 'saved';
        }
        
        return [
            'action' => $action
        ];
    }
    
    /**
     * Get trending tags
     * 
     * @param int $limit Maximum number of tags
     * @return array Tags with counts
     */
    public function getTrendingTags($limit = 10) {
        // This is a more complex query that extracts tags from the comma-separated list
        // and counts their occurrences across all posts
        $sql = "
            SELECT 
                TRIM(tag) as name, 
                COUNT(*) as count
            FROM (
                SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(tags, ','), ',', n.n), ',', -1) as tag
                FROM 
                    {$this->table},
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
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Search tags
     * 
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array Matching tags
     */
    public function searchTags($query, $limit = 10) {
        $searchTerm = "%$query%";
        
        // This uses the same tag extraction logic as getTrendingTags
        $sql = "
            SELECT 
                TRIM(tag) as name, 
                COUNT(*) as count
            FROM (
                SELECT 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(tags, ','), ',', n.n), ',', -1) as tag
                FROM 
                    {$this->table},
                    (SELECT a.N + b.N * 10 + 1 as n
                     FROM 
                         (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                         (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                    ) n
                WHERE 
                    n.n <= 1 + LENGTH(tags) - LENGTH(REPLACE(tags, ',', ''))
                    AND tags IS NOT NULL
                    AND tags != ''
            ) as subquery
            WHERE 
                tag LIKE ?
            GROUP BY tag
            ORDER BY 
                CASE WHEN tag LIKE ? THEN 0 ELSE 1 END,
                count DESC, name
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$searchTerm, $query, $limit]);
    }
    
    /**
     * Get categories for posts
     * 
     * @return array List of categories
     */
    public function getCategories() {
        return [
            ['id' => 'digital-art', 'name' => 'Digital Art'],
            ['id' => 'traditional', 'name' => 'Traditional'],
            ['id' => 'photography', 'name' => 'Photography'],
            ['id' => '3d-art', 'name' => '3D Art'],
            ['id' => 'illustration', 'name' => 'Illustration'],
            ['id' => 'animation', 'name' => 'Animation'],
            ['id' => 'concept-art', 'name' => 'Concept Art'],
            ['id' => 'character-design', 'name' => 'Character Design'],
            ['id' => 'fan-art', 'name' => 'Fan Art'],
            ['id' => 'other', 'name' => 'Other']
        ];
    }
    
    /**
     * Increment view count for a post
     * 
     * @param int $postId Post ID
     * @return bool Success status
     */
    public function incrementViews($postId) {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE id = ?";
        return $this->db->query($sql, [$postId])->rowCount() > 0;
    }
    
    /**
     * Create a notification for post like
     * 
     * @param int $postId Post ID
     * @param int $actorId User who liked the post
     * @return bool Success status
     */
    private function createLikeNotification($postId, $actorId) {
        // Get post owner
        $post = $this->getPost($postId);
        
        if (!$post || $post['user_id'] == $actorId) {
            return false; // Don't notify for self-likes
        }
        
        // Get actor info
        $actor = (new User())->find($actorId);
        
        if (!$actor) {
            return false;
        }
        
        // Create notification message
        $message = "{$actor['username']} liked your post \"{$post['title']}\"";
        
        // Insert notification
        $notification = new Notification();
        return $notification->createNotification(
            $post['user_id'],
            Notification::TYPE_LIKE,
            $actorId,
            $postId,
            $message
        );
    }
    
    /**
     * Process posts data for display
     * 
     * @param array $posts Raw posts data
     * @param int $userId Current user ID
     * @return array Processed posts
     */
    private function processPostsForDisplay($posts, $userId) {
        $processedPosts = [];
        
        foreach ($posts as $post) {
            // Format user data
            $post['author_username'] = $post['username'];
            $post['author_profile_pic'] = $post['profile_picture'] ?: '/assets/images/default-avatar.png';
            
            // Convert numeric fields
            $post['likes_count'] = (int)$post['likes_count'];
            $post['comments_count'] = (int)$post['comments_count'];
            $post['views'] = (int)($post['views'] ?? 0);
            
            // Convert boolean fields
            $post['comments_enabled'] = (bool)$post['comments_enabled'];
            $post['used_ai'] = (bool)$post['used_ai'];
            $post['nsfw'] = (bool)$post['nsfw'];
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            
            // Add recent comments if needed
            if ($post['comments_count'] > 0) {
                $post['comments'] = $this->getPostComments($post['id'], 2);
            } else {
                $post['comments'] = [];
            }
            
            // Clean up unnecessary fields
            unset($post['username'], $post['profile_picture']);
            
            $processedPosts[] = $post;
        }
        
        return $processedPosts;
    }
    
    /**
     * Get recent comments for a post
     * 
     * @param int $postId Post ID
     * @param int $limit Maximum number of comments
     * @return array Comments
     */
    private function getPostComments($postId, $limit = 2) {
        $sql = "
            SELECT 
                c.id, c.user_id, c.content, c.created_at,
                u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC
            LIMIT ?
        ";
        
        $comments = $this->db->fetchAll($sql, [$postId, $limit]);
        
        // Process comments
        $processedComments = [];
        foreach ($comments as $comment) {
            $processedComments[] = [
                'id' => $comment['id'],
                'content' => $comment['content'],
                'created_at' => $comment['created_at'],
                'user' => [
                    'id' => $comment['user_id'],
                    'username' => $comment['username'],
                    'profile_picture' => $comment['profile_picture'] ?: '/assets/images/default-avatar.png'
                ]
            ];
        }
        
        return $processedComments;
    }
}