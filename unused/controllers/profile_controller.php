<?php
/**
 * Profile Controller
 * Handles user profile operations
 */

// Include necessary files
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/utilities.php';

/**
 * Get user profile information
 * 
 * @param int $user_id User ID
 * @return array User profile data
 */
function get_user_profile($user_id) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    try {
        // Get user details
        $stmt = $conn->prepare("
            SELECT 
                id, username, email, full_name, profile_picture, cover_image, 
                bio, website, is_verified, created_at
            FROM users 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Get follower and following counts
        $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $followers_count = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $following_count = $stmt->fetchColumn();
        
        // Get posts count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $posts_count = $stmt->fetchColumn();
        
        // Check if viewing user is following this profile
        $is_following = false;
        $current_user_id = $_SESSION['user_id'] ?? null;
        
        if ($current_user_id && $user_id != $current_user_id) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM follows 
                WHERE follower_id = :follower_id AND followed_id = :followed_id
            ");
            $stmt->bindParam(':follower_id', $current_user_id);
            $stmt->bindParam(':followed_id', $user_id);
            $stmt->execute();
            $is_following = ($stmt->fetchColumn() > 0);
        }
        
        // Format profile data
        $profile = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'] ?? '',
                'profile_picture' => $user['profile_picture'] ?? $config['DEFAULT_PROFILE_IMAGE'],
                'cover_image' => $user['cover_image'] ?? $config['DEFAULT_COVER_IMAGE'],
                'bio' => $user['bio'] ?? '',
                'website' => $user['website'] ?? '',
                'is_verified' => (bool)($user['is_verified'] ?? false),
                'followers_count' => $followers_count,
                'following_count' => $following_count,
                'posts_count' => $posts_count,
                'is_following' => $is_following,
                'joined_at' => date('F Y', strtotime($user['created_at'])),
                'is_current_user' => ($current_user_id == $user_id)
            ]
        ];
        
        // If this is the current user or an admin, include email
        if ($current_user_id == $user_id || ($_SESSION['is_admin'] ?? false)) {
            $profile['user']['email'] = $user['email'];
        }
        
        return $profile;
        
    } catch (PDOException $e) {
        error_log("Profile fetch error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Get user artworks
 * 
 * @param int $user_id User ID
 * @param string $sort Sort method (recent, popular, oldest)
 * @param int $limit Number of artworks to return
 * @param int $offset Offset for pagination
 * @return array Artworks data
 */
function get_user_artworks($user_id, $sort = 'recent', $limit = 6, $offset = 0) {
    global $conn;
    
    try {
        // Build query based on sort method
        $order_by = "p.created_at DESC"; // Default: recent
        
        if ($sort === 'popular') {
            $order_by = "(COALESCE(like_count, 0) + COALESCE(comment_count, 0)) DESC, p.created_at DESC";
        } elseif ($sort === 'oldest') {
            $order_by = "p.created_at ASC";
        }
        
        $stmt = $conn->prepare("
            SELECT 
                p.id, p.title, p.description, p.image_url, p.category, p.tags,
                p.created_at, p.used_ai, p.ai_tools,
                COALESCE(
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0
                ) as like_count,
                COALESCE(
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                ) as comment_count
            FROM posts p
            WHERE p.user_id = :user_id
            ORDER BY {$order_by}
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $artworks = $stmt->fetchAll();
        
        // Get total count for pagination
        $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $total_count = $stmt->fetchColumn();
        
        // Format the artworks
        $formatted_artworks = [];
        foreach ($artworks as $artwork) {
            $formatted_artworks[] = [
                'id' => $artwork['id'],
                'title' => $artwork['title'],
                'description' => $artwork['description'],
                'image_path' => $artwork['image_url'],
                'category' => $artwork['category'],
                'tags' => $artwork['tags'],
                'created_at' => $artwork['created_at'],
                'used_ai' => (bool)$artwork['used_ai'],
                'ai_tools' => $artwork['ai_tools'],
                'likes_count' => $artwork['like_count'],
                'comments_count' => $artwork['comment_count']
            ];
        }
        
        return [
            'success' => true,
            'artworks' => $formatted_artworks,
            'has_more' => ($offset + $limit) < $total_count,
            'total_count' => $total_count
        ];
        
    } catch (PDOException $e) {
        error_log("Artworks fetch error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Update user profile information
 * 
 * @param int $user_id User ID
 * @param array $data Profile data to update
 * @return array Response with success/error message
 */
function update_profile($user_id, $data) {
    global $conn;
    
    // Validate user ID
    if ($user_id != $_SESSION['user_id'] && !($_SESSION['is_admin'] ?? false)) {
        return [
            'success' => false,
            'message' => 'You do not have permission to update this profile.'
        ];
    }
    
    // Sanitize inputs
    $full_name = sanitize_input($data['full_name'] ?? '');
    $bio = sanitize_input($data['bio'] ?? '');
    $website = sanitize_input($data['website'] ?? '');
    
    try {
        $stmt = $conn->prepare("
            UPDATE users 
            SET 
                full_name = :full_name, 
                bio = :bio, 
                website = :website 
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':website', $website);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'full_name' => $full_name,
                    'bio' => $bio,
                    'website' => $website
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update profile'
            ];
        }
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Update profile picture
 * 
 * @param int $user_id User ID
 * @param array $file Uploaded file data
 * @return array Response with success/error message
 */
function update_profile_picture($user_id, $file) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    // Validate user ID
    if ($user_id != $_SESSION['user_id'] && !($_SESSION['is_admin'] ?? false)) {
        return [
            'success' => false,
            'message' => 'You do not have permission to update this profile.'
        ];
    }
    
    // Validate file upload
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'No image uploaded or upload error'
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
        $upload_dir = $config['UPLOAD_DIR'] . '/profiles/' . $user_id;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'profile_' . time() . '.' . get_file_extension($file_type);
        $file_path = $upload_dir . '/' . $filename;
        $image_url = '/uploads/profiles/' . $user_id . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return [
                'success' => false,
                'message' => 'Failed to upload file. Please try again.'
            ];
        }
        
        // Get current profile picture
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $old_image = $stmt->fetchColumn();
        
        // Update profile picture in database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
        $stmt->bindParam(':profile_picture', $image_url);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Delete old image if exists and not default
            if ($old_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_image) && strpos($old_image, 'default') === false) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . $old_image);
            }
            
            return [
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'image_url' => $image_url
            ];
        } else {
            // Delete uploaded file if database update fails
            @unlink($file_path);
            return [
                'success' => false,
                'message' => 'Failed to update profile picture. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        // Delete uploaded file if database error occurs
        if (isset($file_path)) {
            @unlink($file_path);
        }
        
        error_log("Profile picture update error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Toggle follow status (follow/unfollow user)
 * 
 * @param int $follower_id User who is following
 * @param int $followed_id User being followed
 * @return array Response with success/error message
 */
function toggle_follow($follower_id, $followed_id) {
    global $conn;
    
    // Validate user IDs
    if ($follower_id <= 0 || $followed_id <= 0 || $follower_id == $followed_id) {
        return [
            'success' => false,
            'message' => 'Invalid user ID'
        ];
    }
    
    try {
        // Check if already following
        $stmt = $conn->prepare("
            SELECT id 
            FROM follows 
            WHERE follower_id = :follower_id AND followed_id = :followed_id
        ");
        $stmt->bindParam(':follower_id', $follower_id);
        $stmt->bindParam(':followed_id', $followed_id);
        $stmt->execute();
        
        $follow_id = $stmt->fetchColumn();
        
        if ($follow_id) {
            // Unfollow
            $stmt = $conn->prepare("DELETE FROM follows WHERE id = :id");
            $stmt->bindParam(':id', $follow_id);
            $stmt->execute();
            
            $is_following = false;
            $action = 'unfollowed';
        } else {
            // Follow
            $stmt = $conn->prepare("
                INSERT INTO follows (
                    follower_id, 
                    followed_id, 
                    created_at
                ) VALUES (
                    :follower_id, 
                    :followed_id, 
                    NOW()
                )
            ");
            $stmt->bindParam(':follower_id', $follower_id);
            $stmt->bindParam(':followed_id', $followed_id);
            $stmt->execute();
            
            $is_following = true;
            $action = 'followed';
            
            // Create notification for the followed user
            createFollowNotification($follower_id, $followed_id);
        }
        
        // Get updated follower count
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM follows 
            WHERE followed_id = :followed_id
        ");
        $stmt->bindParam(':followed_id', $followed_id);
        $stmt->execute();
        
        $follower_count = $stmt->fetchColumn();
        
        return [
            'success' => true,
            'is_following' => $is_following,
            'follower_count' => $follower_count,
            'message' => 'Successfully ' . $action . ' user'
        ];
        
    } catch (PDOException $e) {
        error_log("Follow toggle error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Create a follow notification
 * 
 * @param int $follower_id User who is following
 * @param int $followed_id User being followed
 */
function createFollowNotification($follower_id, $followed_id) {
    global $conn;
    
    try {
        // Get follower username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $follower_id);
        $stmt->execute();
        $follower_username = $stmt->fetchColumn();
        
        $message = $follower_username . ' started following you';
        
        // Create notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, 
                type, 
                actor_id, 
                message, 
                created_at
            ) VALUES (
                :user_id, 
                'follow', 
                :actor_id, 
                :message, 
                NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $followed_id);
        $stmt->bindParam(':actor_id', $follower_id);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Follow notification error: " . $e->getMessage());
    }
}