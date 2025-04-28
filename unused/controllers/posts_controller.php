<?php
/**
 * Posts Controller
 * Handles post operations (create, read, update, delete)
 */

// Include necessary files
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/utilities.php';

/**
 * Get feed posts
 * 
 * @param int $user_id User ID
 * @param string $filter Filter type (all, following, trending)
 * @param string $category Category filter
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @return array Posts data
 */
function get_feed_posts($user_id, $filter = 'all', $category = '', $limit = 10, $offset = 0) {
    global $conn;
    
    try {
        // Start building the query
        $query = "
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :user_id) as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
        ";
        
        // Add filters
        $params = [':user_id' => $user_id];
        $where_clauses = [];
        
        // Following filter
        if ($filter === 'following') {
            $where_clauses[] = "p.user_id IN (
                SELECT followed_id FROM follows WHERE follower_id = :user_id
                UNION 
                SELECT :user_id
            )";
        }
        
        // Category filter
        if (!empty($category)) {
            $where_clauses[] = "p.category = :category";
            $params[':category'] = $category;
        }
        
        // NSFW filter (exclude NSFW by default)
        $where_clauses[] = "p.nsfw = 0"; // Add option to show NSFW in the future
        
        // Add where clauses to query
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        // Add ordering
        if ($filter === 'trending') {
            $query .= " ORDER BY (likes_count + comments_count * 2) DESC, p.created_at DESC";
        } else {
            $query .= " ORDER BY p.created_at DESC";
        }
        
        // Add limit and offset
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        $posts = $stmt->fetchAll();
        
        // Get total count for pagination
        $count_query = str_replace("SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :user_id) as user_saved", "SELECT COUNT(*) as count", $query);
        
        // Remove LIMIT and OFFSET from count query
        $count_query = preg_replace('/\s+LIMIT\s+:limit\s+OFFSET\s+:offset/i', '', $count_query);
        
        $count_stmt = $conn->prepare($count_query);
        
        // Bind parameters for count query (excluding limit and offset)
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $count_stmt->bindValue($key, $value);
            }
        }
        
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        // Fetch comments for each post (limited to 2 most recent)
        foreach ($posts as &$post) {
            $comments_stmt = $conn->prepare("
                SELECT 
                    c.id, c.user_id, c.content, c.created_at,
                    u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at DESC
                LIMIT 2
            ");
            
            $comments_stmt->bindParam(':post_id', $post['id']);
            $comments_stmt->execute();
            $comments = $comments_stmt->fetchAll();
            
            // Format comments
            $post['comments'] = [];
            foreach ($comments as $comment) {
                $post['comments'][] = [
                    'id' => $comment['id'],
                    'user_id' => $comment['user_id'],
                    'username' => $comment['username'],
                    'content' => $comment['content'],
                    'created_at' => $comment['created_at']
                ];
            }
            
            // Format other post data
            $post['created_at_formatted'] = format_time_ago($post['created_at']);
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            $post['used_ai'] = (bool)$post['used_ai'];
            
            // Format author info
            $post['author'] = [
                'id' => $post['user_id'],
                'username' => $post['author_username'],
                'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40'
            ];
            
            // Clean up redundant fields
            unset($post['author_username']);
            unset($post['author_profile_pic']);
        }
        
        return [
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit),
                'total_count' => $total_count,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Feed posts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get a single post by ID
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID for interaction status
 * @return array Post data
 */
function get_post($post_id, $user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic, u.is_verified,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :user_id) as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :post_id
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        // Format post data
        $post['created_at_formatted'] = format_time_ago($post['created_at']);
        $post['user_liked'] = (bool)$post['user_liked'];
        $post['user_saved'] = (bool)$post['user_saved'];
        $post['used_ai'] = (bool)$post['used_ai'];
        $post['comments_enabled'] = (bool)$post['comments_enabled'];
        $post['nsfw'] = (bool)$post['nsfw'];
        
        // Format author info
        $post['author'] = [
            'id' => $post['user_id'],
            'username' => $post['author_username'],
            'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40',
            'is_verified' => (bool)$post['is_verified']
        ];
        
        // Get comments
        $comments_stmt = $conn->prepare("
            SELECT 
                c.id, c.user_id, c.content, c.created_at,
                u.username, u.profile_picture, u.is_verified
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        
        $comments_stmt->bindParam(':post_id', $post_id);
        $comments_stmt->execute();
        $comments = $comments_stmt->fetchAll();
        
        // Format comments
        $post['comments'] = [];
        foreach ($comments as $comment) {
            $post['comments'][] = [
                'id' => $comment['id'],
                'user' => [
                    'id' => $comment['user_id'],
                    'username' => $comment['username'],
                    'profile_picture' => $comment['profile_picture'] ?: '/api/placeholder/32/32',
                    'is_verified' => (bool)$comment['is_verified']
                ],
                'content' => $comment['content'],
                'created_at' => $comment['created_at'],
                'created_at_formatted' => format_time_ago($comment['created_at'])
            ];
        }
        
        // Clean up redundant fields
        unset($post['author_username']);
        unset($post['author_profile_pic']);
        unset($post['is_verified']);
        
        // Increment view count
        $view_stmt = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = :post_id");
        $view_stmt->bindParam(':post_id', $post_id);
        $view_stmt->execute();
        
        return [
            'success' => true,
            'post' => $post
        ];
        
    } catch (PDOException $e) {
        error_log("Get post error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Create a new post
 * 
 * @param int $user_id User ID
 * @param array $data Post data
 * @param array $file Uploaded file
 * @return array Response with success/error message
 */
function create_post($user_id, $data, $file) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    // Validate inputs
    $title = sanitize_input($data['title'] ?? '');
    $description = sanitize_input($data['description'] ?? '');
    $category = sanitize_input($data['category'] ?? '');
    $tags = sanitize_input($data['tags'] ?? '');
    $comments_enabled = isset($data['comments_enabled']) ? 1 : 0;
    $used_ai = isset($data['used_ai']) ? 1 : 0;
    $ai_tools = $used_ai ? sanitize_input($data['ai_tools'] ?? '') : '';
    $nsfw = isset($data['nsfw']) ? 1 : 0;
    
    // Validate required fields
    if (empty($title)) {
        return [
            'success' => false,
            'message' => 'Please enter a title for your artwork.'
        ];
    }
    
    if (empty($category)) {
        return [
            'success' => false,
            'message' => 'Please select a category for your artwork.'
        ];
    }
    
    // Validate file upload
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'Please upload an image of your artwork.'
        ];
    }
    
    // Validate file size
    if ($file['size'] > $config['MAX_FILE_SIZE']) {
        return [
            'success' => false,
            'message' => 'File size exceeds the maximum limit (' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB).'
        ];
    }
    
    // Validate file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $config['ALLOWED_IMAGE_TYPES'])) {
        return [
            'success' => false,
            'message' => 'Only ' . implode(', ', array_map(function($type) {
                return strtoupper(str_replace('image/', '', $type));
            }, $config['ALLOWED_IMAGE_TYPES'])) . ' files are allowed.'
        ];
    }
    
    try {
        // Create upload directory if it doesn't exist
        $upload_dir = $config['UPLOAD_DIR'] . '/artworks/' . date('Y/m');
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'art_' . uniqid() . '_' . time() . '.' . get_file_extension($file_type);
        $file_path = $upload_dir . '/' . $filename;
        $image_url = '/uploads/artworks/' . date('Y/m') . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return [
                'success' => false,
                'message' => 'Failed to upload file. Please try again.'
            ];
        }
        
        // Insert post into database
        $stmt = $conn->prepare("
            INSERT INTO posts (
                user_id,
                title,
                description,
                image_url,
                category,
                tags,
                comments_enabled,
                used_ai,
                ai_tools,
                nsfw,
                views,
                created_at
            ) VALUES (
                :user_id,
                :title,
                :description,
                :image_url,
                :category,
                :tags,
                :comments_enabled,
                :used_ai,
                :ai_tools,
                :nsfw,
                0,
                NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':comments_enabled', $comments_enabled);
        $stmt->bindParam(':used_ai', $used_ai);
        $stmt->bindParam(':ai_tools', $ai_tools);
        $stmt->bindParam(':nsfw', $nsfw);
        
        if ($stmt->execute()) {
            $post_id = $conn->lastInsertId();
            
            // Notify followers
            notify_followers($user_id, $post_id, $title);
            
            return [
                'success' => true,
                'message' => 'Your artwork has been posted successfully!',
                'post_id' => $post_id
            ];
        } else {
            // Delete uploaded file if database insertion fails
            @unlink($file_path);
            
            return [
                'success' => false,
                'message' => 'Failed to save your post. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        // Delete uploaded file if an error occurs
        if (isset($file_path) && file_exists($file_path)) {
            @unlink($file_path);
        }
        
        error_log("Create post error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Delete a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID requesting deletion
 * @return array Response with success/error message
 */
function delete_post($post_id, $user_id) {
    global $conn;
    
    try {
        // Check if user owns the post or is an admin
        $stmt = $conn->prepare("
            SELECT user_id, image_url FROM posts WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        if ($post['user_id'] != $user_id && !($_SESSION['is_admin'] ?? false)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this post'
            ];
        }
        
        // Delete the post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        
        if ($stmt->execute()) {
            // Delete associated image file
            $image_path = $_SERVER['DOCUMENT_ROOT'] . $post['image_url'];
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
            
            return [
                'success' => true,
                'message' => 'Post deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete post'
            ];
        }
    } catch (PDOException $e) {
        error_log("Delete post error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Toggle like status for a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID
 * @return array Response with success/error message
 */
function toggle_like($post_id, $user_id) {
    global $conn;
    
    try {
        // Check if post exists
        $stmt = $conn->prepare("SELECT id, user_id, title FROM posts WHERE id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        // Check if user already liked the post
        $stmt = $conn->prepare("
            SELECT id FROM likes 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $like = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($like) {
            // Unlike
            $stmt = $conn->prepare("
                DELETE FROM likes 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'unliked';
        } else {
            // Like
            $stmt = $conn->prepare("
                INSERT INTO likes (post_id, user_id, created_at) 
                VALUES (:post_id, :user_id, NOW())
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'liked';
            
            // Create notification for post owner (if not self-like)
            if ($post['user_id'] != $user_id) {
                create_like_notification($user_id, $post['user_id'], $post_id, $post['title']);
            }
        }
        
        // Get updated like count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        $likes_count = $stmt->fetchColumn();
        
        return [
            'success' => true,
            'action' => $action,
            'likes_count' => $likes_count
        ];
    } catch (PDOException $e) {
        error_log("Toggle like error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Create a like notification
 * 
 * @param int $liker_id User who liked the post
 * @param int $owner_id Owner of the post
 * @param int $post_id Post ID
 * @param string $post_title Post title
 */
function create_like_notification($liker_id, $owner_id, $post_id, $post_title) {
    global $conn;
    
    try {
        // Get liker username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $liker_id);
        $stmt->execute();
        $liker_username = $stmt->fetchColumn();
        
        $message = $liker_username . ' liked your post "' . truncate_text($post_title, 30) . '"';
        
        // Create notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, 
                type, 
                actor_id, 
                entity_id,
                message, 
                created_at
            ) VALUES (
                :user_id, 
                'like', 
                :actor_id, 
                :entity_id,
                :message, 
                NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $owner_id);
        $stmt->bindParam(':actor_id', $liker_id);
        $stmt->bindParam(':entity_id', $post_id);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Create like notification error: " . $e->getMessage());
    }
}

/**
 * Notify followers of a new post
 * 
 * @param int $user_id User who created the post
 * @param int $post_id Post ID
 * @param string $post_title Post title
 */
function notify_followers($user_id, $post_id, $post_title) {
    global $conn;
    
    try {
        // Get user's followers
        $stmt = $conn->prepare("
            SELECT follower_id FROM follows WHERE followed_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($followers)) {
            return;
        }
        
        // Get user's username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $username = $stmt->fetchColumn();
        
        $message = $username . ' shared a new artwork "' . truncate_text($post_title, 30) . '"';
        
        // Prepare notification insert statement
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, 
                type, 
                actor_id, 
                entity_id,
                message, 
                created_at
            ) VALUES (
                :user_id, 
                'new_post', 
                :actor_id, 
                :entity_id,
                :message, 
                NOW()
            )
        ");
        
        // Create a notification for each follower
        foreach ($followers as $follower_id) {
            $stmt->bindParam(':user_id', $follower_id);
            $stmt->bindParam(':actor_id', $user_id);
            $stmt->bindParam(':entity_id', $post_id);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Notify followers error: " . $e->getMessage());
    }
}

/**
 * Add a comment to a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID
 * @param string $content Comment content
 * @return array Response with success/error message
 */
function add_comment($post_id, $user_id, $content) {
    global $conn;
    
    // Validate inputs
    $content = sanitize_input($content);
    
    if (empty($content)) {
        return [
            'success' => false,
            'message' => 'Comment cannot be empty'
        ];
    }
    
    try {
        // Check if post exists and comments are enabled
        $stmt = $conn->prepare("
            SELECT id, user_id, title, comments_enabled 
            FROM posts 
            WHERE id = :post_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        if (!$post['comments_enabled']) {
            return [
                'success' => false,
                'message' => 'Comments are disabled for this post'
            ];
        }
        
        // Insert comment
        $stmt = $conn->prepare("
            INSERT INTO comments (
                post_id, 
                user_id, 
                content, 
                created_at
            ) VALUES (
                :post_id, 
                :user_id, 
                :content, 
                NOW()
            )
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':content', $content);
        
        if ($stmt->execute()) {
            $comment_id = $conn->lastInsertId();
            
            // Get user info
            $stmt = $conn->prepare("
                SELECT username, profile_picture 
                FROM users 
                WHERE id = :user_id
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get updated comment count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->execute();
            $comments_count = $stmt->fetchColumn();
            
            // Create notification for post owner (if not self-comment)
            if ($post['user_id'] != $user_id) {
                create_comment_notification($user_id, $post['user_id'], $post_id, $post['title'], $content);
            }
            
            return [
                'success' => true,
                'comment' => [
                    'id' => $comment_id,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_at_formatted' => 'Just now',
                    'user' => [
                        'id' => $user_id,
                        'username' => $user['username'],
                        'profile_picture' => $user['profile_picture'] ?: '/api/placeholder/32/32'
                    ]
                ],
                'comments_count' => $comments_count
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add comment'
            ];
        }
    } catch (PDOException $e) {
        error_log("Add comment error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Create a comment notification
 * 
 * @param int $commenter_id User who commented
 * @param int $owner_id Owner of the post
 * @param int $post_id Post ID
 * @param string $post_title Post title
 * @param string $comment_content Comment content
 */
function create_comment_notification($commenter_id, $owner_id, $post_id, $post_title, $comment_content) {
    global $conn;
    
    try {
        // Get commenter username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $commenter_id);
        $stmt->execute();
        $commenter_username = $stmt->fetchColumn();
        
        $message = $commenter_username . ' commented on your post "' . truncate_text($post_title, 30) . '"';
        
        // Create notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, 
                type, 
                actor_id, 
                entity_id,
                message, 
                created_at
            ) VALUES (
                :user_id, 
                'comment', 
                :actor_id, 
                :entity_id,
                :message, 
                NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $owner_id);
        $stmt->bindParam(':actor_id', $commenter_id);
        $stmt->bindParam(':entity_id', $post_id);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Create comment notification error: " . $e->getMessage());
    }
}

/**
 * Delete a comment
 * 
 * @param int $comment_id Comment ID
 * @param int $user_id User ID requesting deletion
 * @return array Response with success/error message
 */
function delete_comment($comment_id, $user_id) {
    global $conn;
    
    try {
        // Check if user owns the comment or is an admin or post owner
        $stmt = $conn->prepare("
            SELECT c.id, c.user_id, c.post_id, p.user_id as post_owner_id 
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            WHERE c.id = :comment_id
        ");
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->execute();
        
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment) {
            return [
                'success' => false,
                'message' => 'Comment not found'
            ];
        }
        
        // Check if user has permission to delete
        $has_permission = 
            $comment['user_id'] == $user_id || // Comment owner
            $comment['post_owner_id'] == $user_id || // Post owner
            ($_SESSION['is_admin'] ?? false); // Admin
            
        if (!$has_permission) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this comment'
            ];
        }
        
        // Delete the comment
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = :comment_id");
        $stmt->bindParam(':comment_id', $comment_id);
        
        if ($stmt->execute()) {
            // Get updated comment count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $comment['post_id']);
            $stmt->execute();
            $comments_count = $stmt->fetchColumn();
            
            return [
                'success' => true,
                'message' => 'Comment deleted successfully',
                'comments_count' => $comments_count
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete comment'
            ];
        }
    } catch (PDOException $e) {
        error_log("Delete comment error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Toggle save status for a post
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID
 * @return array Response with success/error message
 */
function toggle_save($post_id, $user_id) {
    global $conn;
    
    try {
        // Check if post exists
        $stmt = $conn->prepare("SELECT id FROM posts WHERE id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        // Check if user already saved the post
        $stmt = $conn->prepare("
            SELECT id FROM saved_posts 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($saved) {
            // Unsave
            $stmt = $conn->prepare("
                DELETE FROM saved_posts 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'unsaved';
        } else {
            // Save
            $stmt = $conn->prepare("
                INSERT INTO saved_posts (post_id, user_id, created_at) 
                VALUES (:post_id, :user_id, NOW())
            ");
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $action = 'saved';
        }
        
        return [
            'success' => true,
            'action' => $action
        ];
    } catch (PDOException $e) {
        error_log("Toggle save error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get saved posts for a user
 * 
 * @param int $user_id User ID
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @return array Saved posts data
 */
function get_saved_posts($user_id, $limit = 10, $offset = 0) {
    global $conn;
    
    try {
        // Build query
        $query = "
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                1 as user_saved
            FROM saved_posts sp
            JOIN posts p ON sp.post_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE sp.user_id = :user_id
            ORDER BY sp.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) FROM saved_posts WHERE user_id = :user_id
        ");
        $count_stmt->bindParam(':user_id', $user_id);
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        // Format posts
        foreach ($posts as &$post) {
            // Format post data
            $post['created_at_formatted'] = format_time_ago($post['created_at']);
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            $post['used_ai'] = (bool)$post['used_ai'];
            
            // Format author info
            $post['author'] = [
                'id' => $post['user_id'],
                'username' => $post['author_username'],
                'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40'
            ];
            
            // Clean up redundant fields
            unset($post['author_username']);
            unset($post['author_profile_pic']);
        }
        
        return [
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit),
                'total_count' => $total_count,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Get saved posts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Report a post for inappropriate content
 * 
 * @param int $post_id Post ID
 * @param int $user_id User ID reporting the post
 * @param string $reason Reason for the report
 * @return array Response with success/error message
 */
function report_post($post_id, $user_id, $reason) {
    global $conn;
    
    // Validate inputs
    $reason = sanitize_input($reason);
    
    if (empty($reason)) {
        return [
            'success' => false,
            'message' => 'Please provide a reason for your report'
        ];
    }
    
    try {
        // Check if post exists
        $stmt = $conn->prepare("SELECT id, title FROM posts WHERE id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Post not found'
            ];
        }
        
        // Check if user already reported this post
        $stmt = $conn->prepare("
            SELECT id FROM reports 
            WHERE post_id = :post_id AND user_id = :user_id
        ");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'You have already reported this post'
            ];
        }
        
        // Insert report
        $stmt = $conn->prepare("
            INSERT INTO reports (
                post_id, 
                user_id, 
                reason, 
                status,
                created_at
            ) VALUES (
                :post_id, 
                :user_id, 
                :reason, 
                'pending',
                NOW()
            )
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':reason', $reason);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Thank you for your report. Our team will review it shortly.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to submit report. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Report post error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get posts by a specific user
 * 
 * @param int $profile_user_id User ID of the profile being viewed
 * @param int $viewing_user_id User ID of the user viewing the profile
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @return array User's posts data
 */
function get_user_posts($profile_user_id, $viewing_user_id, $limit = 10, $offset = 0) {
    global $conn;
    
    try {
        // Build query
        $query = "
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :viewing_user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :viewing_user_id) as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = :profile_user_id
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':profile_user_id', $profile_user_id);
        $stmt->bindParam(':viewing_user_id', $viewing_user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) FROM posts WHERE user_id = :profile_user_id
        ");
        $count_stmt->bindParam(':profile_user_id', $profile_user_id);
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        // Format posts
        foreach ($posts as &$post) {
            // Format post data
            $post['created_at_formatted'] = format_time_ago($post['created_at']);
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            $post['used_ai'] = (bool)$post['used_ai'];
            
            // Format author info
            $post['author'] = [
                'id' => $post['user_id'],
                'username' => $post['author_username'],
                'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40'
            ];
            
            // Clean up redundant fields
            unset($post['author_username']);
            unset($post['author_profile_pic']);
        }
        
        return [
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit),
                'total_count' => $total_count,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Get user posts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get posts by category
 * 
 * @param int $user_id User ID viewing the posts
 * @param string $category Category name
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @return array Category posts data
 */
function get_category_posts($user_id, $category, $limit = 10, $offset = 0) {
    global $conn;
    
    try {
        // Build query
        $query = "
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :user_id) as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.category = :category AND p.nsfw = 0
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) FROM posts WHERE category = :category AND nsfw = 0
        ");
        $count_stmt->bindParam(':category', $category);
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        // Format posts (similar to other post formatting)
        foreach ($posts as &$post) {
            $post['created_at_formatted'] = format_time_ago($post['created_at']);
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            $post['used_ai'] = (bool)$post['used_ai'];
            
            $post['author'] = [
                'id' => $post['user_id'],
                'username' => $post['author_username'],
                'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40'
            ];
            
            unset($post['author_username']);
            unset($post['author_profile_pic']);
        }
        
        return [
            'success' => true,
            'category' => $category,
            'posts' => $posts,
            'pagination' => [
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit),
                'total_count' => $total_count,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Get category posts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Search for posts by keyword
 * 
 * @param int $user_id User ID viewing the search results
 * @param string $query Search query
 * @param int $limit Number of posts to return
 * @param int $offset Offset for pagination
 * @return array Search results
 */
function search_posts($user_id, $query, $limit = 10, $offset = 0) {
    global $conn;
    
    // Sanitize and prepare search query
    $search_term = '%' . sanitize_input($query) . '%';
    
    try {
        // Build query
        $query = "
            SELECT 
                p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                p.used_ai, p.ai_tools, p.created_at, p.comments_enabled, p.nsfw,
                u.username as author_username, u.profile_picture as author_profile_pic,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as likes_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comments_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked,
                (SELECT COUNT(*) > 0 FROM saved_posts WHERE post_id = p.id AND user_id = :user_id) as user_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE (
                p.title LIKE :search_term OR
                p.description LIKE :search_term OR
                p.tags LIKE :search_term OR
                u.username LIKE :search_term
            ) AND p.nsfw = 0
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':search_term', $search_term);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $count_query = "
            SELECT COUNT(*) FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE (
                p.title LIKE :search_term OR
                p.description LIKE :search_term OR
                p.tags LIKE :search_term OR
                u.username LIKE :search_term
            ) AND p.nsfw = 0
        ";
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bindParam(':search_term', $search_term);
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        // Format posts
        foreach ($posts as &$post) {
            $post['created_at_formatted'] = format_time_ago($post['created_at']);
            $post['user_liked'] = (bool)$post['user_liked'];
            $post['user_saved'] = (bool)$post['user_saved'];
            $post['used_ai'] = (bool)$post['used_ai'];
            
            $post['author'] = [
                'id' => $post['user_id'],
                'username' => $post['author_username'],
                'profile_picture' => $post['author_profile_pic'] ?: '/api/placeholder/40/40'
            ];
            
            unset($post['author_username']);
            unset($post['author_profile_pic']);
        }
        
        return [
            'success' => true,
            'search_query' => sanitize_input($query),
            'posts' => $posts,
            'pagination' => [
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($total_count / $limit),
                'total_count' => $total_count,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Search posts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get post categories
 * 
 * @return array List of categories with post counts
 */
function get_categories() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT category, COUNT(*) as post_count
            FROM posts
            WHERE nsfw = 0
            GROUP BY category
            ORDER BY post_count DESC
        ");
        
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (PDOException $e) {
        error_log("Get categories error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Truncate text to specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncate_text($text, $length = 50) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}