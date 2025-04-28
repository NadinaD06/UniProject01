<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include database connection
require_once '../../database.php';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get action from request
$action = $_GET['action'] ?? '';

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid action',
    'data' => []
];

try {
    switch ($action) {
        case 'get_tags':
            // Get trending tags
            $stmt = $conn->prepare("
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
                ) as subquery
                WHERE 
                    CHAR_LENGTH(tag) > 1
                GROUP BY tag
                ORDER BY count DESC, name
                LIMIT 20
            ");
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $tags;
            break;
            
        case 'get_artworks':
            // Get all artworks with filters
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            $offset = ($page - 1) * $limit;
            $filter = $_GET['filter'] ?? 'trending';
            $category = $_GET['category'] ?? '';
            $tag = $_GET['tag'] ?? '';
            $search = $_GET['q'] ?? '';
            
            // Build the query based on filters
            $query = "
                SELECT 
                    p.id, p.user_id, p.title, p.description, p.image_url, 
                    p.category, p.tags, p.used_ai, p.ai_tools,
                    u.id as artist_id, u.username as artist_username, u.profile_picture as artist_profile_pic,
                    COALESCE(l.like_count, 0) as likes_count,
                    COALESCE(c.comment_count, 0) as comments_count,
                    CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                    CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as user_saved
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) as like_count FROM likes GROUP BY post_id
                ) l ON l.post_id = p.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) as comment_count FROM comments GROUP BY post_id
                ) c ON c.post_id = p.id
                LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :user_id
                LEFT JOIN saved_posts s ON s.post_id = p.id AND s.user_id = :user_id
            ";
            
            // Add WHERE clauses based on filters
            $whereClauses = [];
            $params = [':user_id' => $user_id];
            
            if (!empty($category)) {
                $whereClauses[] = "p.category = :category";
                $params[':category'] = $category;
            }
            
            if (!empty($tag)) {
                $whereClauses[] = "p.tags LIKE :tag";
                $params[':tag'] = '%' . $tag . '%';
            }
            
            if (!empty($search)) {
                $whereClauses[] = "(p.title LIKE :search OR p.description LIKE :search OR p.tags LIKE :search OR u.username LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            if ($filter === 'following') {
                $whereClauses[] = "p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = :follower_id)";
                $params[':follower_id'] = $user_id;
            }
            
            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }
            
            // Add order by clause based on filter
            switch ($filter) {
                case 'trending':
                    $query .= " ORDER BY (COALESCE(l.like_count, 0) + COALESCE(c.comment_count, 0) * 2) DESC, p.created_at DESC";
                    break;
                case 'recent':
                    $query .= " ORDER BY p.created_at DESC";
                    break;
                case 'popular':
                    $query .= " ORDER BY COALESCE(l.like_count, 0) DESC, p.created_at DESC";
                    break;
                default:
                    $query .= " ORDER BY p.created_at DESC";
            }
            
            // Add limit and offset
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            // Prepare and execute query
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
            $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the result for the response
            $formatted_artworks = [];
            
            foreach ($artworks as $artwork) {
                $formatted_artwork = [
                    'id' => $artwork['id'],
                    'title' => $artwork['title'],
                    'image_url' => $artwork['image_url'],
                    'likes_count' => $artwork['likes_count'],
                    'comments_count' => $artwork['comments_count'],
                    'user_liked' => (bool)$artwork['user_liked'],
                    'user_saved' => (bool)$artwork['user_saved'],
                    'category' => $artwork['category'],
                    'tags' => $artwork['tags'],
                    'used_ai' => (bool)$artwork['used_ai'],
                    'ai_tools' => $artwork['ai_tools'],
                    'artist' => [
                        'id' => $artwork['artist_id'],
                        'username' => $artwork['artist_username'],
                        'profile_picture' => $artwork['artist_profile_pic'] ? $artwork['artist_profile_pic'] : '/api/placeholder/40/40'
                    ]
                ];
                
                $formatted_artworks[] = $formatted_artwork;
            }
            
            // Check if there are more artworks
            $countQuery = "SELECT COUNT(*) FROM posts p";
            
            if (!empty($whereClauses)) {
                $countQuery .= " WHERE " . implode(" AND ", $whereClauses);
            }
            
            $countStmt = $conn->prepare($countQuery);
            
            // Bind parameters (except limit and offset)
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $countStmt->bindValue($key, $value);
                }
            }
            
            $countStmt->execute();
            $total_count = $countStmt->fetchColumn();
            
            $has_more = ($offset + $limit) < $total_count;
            
            $response['success'] = true;
            $response['data'] = [
                'artworks' => $formatted_artworks,
                'has_more' => $has_more,
                'total_count' => $total_count
            ];
            break;
            
        case 'get_artwork_detail':
            // Get detailed info for a single artwork
            $artwork_id = isset($_GET['artwork_id']) ? intval($_GET['artwork_id']) : 0;
            
            if ($artwork_id <= 0) {
                $response['message'] = 'Invalid artwork ID';
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    p.id, p.user_id, p.title, p.description, p.image_url, 
                    p.category, p.tags, p.used_ai, p.ai_tools, p.created_at,
                    u.id as artist_id, u.username as artist_username, u.profile_picture as artist_profile_pic,
                    COALESCE(l.like_count, 0) as likes_count,
                    COALESCE(c.comment_count, 0) as comments_count,
                    CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked,
                    CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as user_saved
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) as like_count FROM likes GROUP BY post_id
                ) l ON l.post_id = p.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) as comment_count FROM comments GROUP BY post_id
                ) c ON c.post_id = p.id
                LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :user_id
                LEFT JOIN saved_posts s ON s.post_id = p.id AND s.user_id = :user_id
                WHERE p.id = :artwork_id
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':artwork_id', $artwork_id);
            $stmt->execute();
            
            $artwork = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$artwork) {
                $response['message'] = 'Artwork not found';
                break;
            }
            
            // Format created_at
            $created_at = new DateTime($artwork['created_at']);
            $artwork['created_at_formatted'] = $created_at->format('M d, Y');
            
            // Get comments
            $stmt = $conn->prepare("
                SELECT 
                    c.id, c.user_id, c.content, c.created_at,
                    u.username, u.profile_picture
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");
            $stmt->bindParam(':post_id', $artwork_id);
            $stmt->execute();
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format comments
            $formatted_comments = [];
            foreach ($comments as $comment) {
                $comment_date = new DateTime($comment['created_at']);
                
                $formatted_comments[] = [
                    'id' => $comment['id'],
                    'content' => $comment['content'],
                    'created_at' => $comment['created_at'],
                    'created_at_formatted' => $comment_date->format('M d, Y \a\t h:i A'),
                    'user' => [
                        'id' => $comment['user_id'],
                        'username' => $comment['username'],
                        'profile_picture' => $comment['profile_picture'] ? $comment['profile_picture'] : '/api/placeholder/32/32'
                    ]
                ];
            }
            
            // Structure the response
            $artwork_detail = [
                'id' => $artwork['id'],
                'title' => $artwork['title'],
                'description' => $artwork['description'],
                'image_url' => $artwork['image_url'],
                'category' => $artwork['category'],
                'tags' => $artwork['tags'],
                'created_at' => $artwork['created_at'],
                'created_at_formatted' => $artwork['created_at_formatted'],
                'likes_count' => $artwork['likes_count'],
                'comments_count' => $artwork['comments_count'],
                'user_liked' => (bool)$artwork['user_liked'],
                'user_saved' => (bool)$artwork['user_saved'],
                'used_ai' => (bool)$artwork['used_ai'],
                'ai_tools' => $artwork['ai_tools'],
                'artist' => [
                    'id' => $artwork['artist_id'],
                    'username' => $artwork['artist_username'],
                    'profile_picture' => $artwork['artist_profile_pic'] ? $artwork['artist_profile_pic'] : '/api/placeholder/40/40'
                ],
                'comments' => $formatted_comments
            ];
            
            // Increment view count
            $stmt = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = :id");
            $stmt->bindParam(':id', $artwork_id);
            $stmt->execute();
            
            $response['success'] = true;
            $response['data'] = $artwork_detail;
            break;
            
        case 'get_artist_preview':
            // Get preview info for an artist
            $artist_id = isset($_GET['artist_id']) ? intval($_GET['artist_id']) : 0;
            
            if ($artist_id <= 0) {
                $response['message'] = 'Invalid artist ID';
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    u.id, u.username, u.profile_picture, u.bio, u.is_verified,
                    (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as follower_count,
                    (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
                    CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_following
                FROM users u
                LEFT JOIN follows f ON f.followed_id = u.id AND f.follower_id = :user_id
                WHERE u.id = :artist_id
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':artist_id', $artist_id);
            $stmt->execute();
            
            $artist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$artist) {
                $response['message'] = 'Artist not found';
                break;
            }
            
            // Get recent works
            $stmt = $conn->prepare("
                SELECT 
                    id, title, image_url
                FROM posts
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 6
            ");
            $stmt->bindParam(':user_id', $artist_id);
            $stmt->execute();
            $recent_works = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Structure the response
            $artist_preview = [
                'id' => $artist['id'],
                'username' => $artist['username'],
                'profile_picture' => $artist['profile_picture'] ? $artist['profile_picture'] : '/api/placeholder/80/80',
                'bio' => $artist['bio'],
                'is_verified' => (bool)$artist['is_verified'],
                'follower_count' => $artist['follower_count'],
                'post_count' => $artist['post_count'],
                'is_following' => (bool)$artist['is_following'],
                'recent_works' => $recent_works
            ];
            
            $response['success'] = true;
            $response['data'] = $artist_preview;
            break;
            
        case 'like_artwork':
            // Like or unlike an artwork
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
            
            if ($artwork_id <= 0) {
                $response['message'] = 'Invalid artwork ID';
                break;
            }
            
            // Check if user already liked the artwork
            $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id");
            $stmt->bindParam(':post_id', $artwork_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $like_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($like_exists) {
                // Unlike the artwork
                $stmt = $conn->prepare("DELETE FROM likes WHERE id = :id");
                $stmt->bindParam(':id', $like_exists['id']);
                $stmt->execute();
                
                $action = 'unliked';
            } else {
                // Like the artwork
                $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())");
                $stmt->bindParam(':post_id', $artwork_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $action = 'liked';
                
                // Get artwork owner
                $stmt = $conn->prepare("SELECT user_id, title FROM posts WHERE id = :id");
                $stmt->bindParam(':id', $artwork_id);
                $stmt->execute();
                $artwork_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Create notification (if liking someone else's artwork)
                if ($artwork_info && $artwork_info['user_id'] != $user_id) {
                    // Get the user's username
                    $stmt = $conn->prepare("SELECT username FROM users WHERE id = :id");
                    $stmt->bindParam(':id', $user_id);
                    $stmt->execute();
                    $username = $stmt->fetchColumn();
                    
                    $message = $username . ' liked your artwork "' . $artwork_info['title'] . '"';
                    
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, actor_id, entity_id, message, created_at) 
                        VALUES (:user_id, 'like', :actor_id, :entity_id, :message, NOW())
                    ");
                    $stmt->bindParam(':user_id', $artwork_info['user_id']);
                    $stmt->bindParam(':actor_id', $user_id);
                    $stmt->bindParam(':entity_id', $artwork_id);
                    $stmt->bindParam(':message', $message);
                    $stmt->execute();
                }
            }
            
            // Get updated like count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $artwork_id);
            $stmt->execute();
            $likes_count = $stmt->fetchColumn();
            
            $response['success'] = true;
            $response['data'] = [
                'action' => $action,
                'likes_count' => $likes_count
            ];
            break;
            
        case 'save_artwork':
            // Save or unsave an artwork
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
            
            if ($artwork_id <= 0) {
                $response['message'] = 'Invalid artwork ID';
                break;
            }
            
            // Check if user already saved the artwork
            $stmt = $conn->prepare("SELECT id FROM saved_posts WHERE post_id = :post_id AND user_id = :user_id");
            $stmt->bindParam(':post_id', $artwork_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $save_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($save_exists) {
                // Unsave the artwork
                $stmt = $conn->prepare("DELETE FROM saved_posts WHERE id = :id");
                $stmt->bindParam(':id', $save_exists['id']);
                $stmt->execute();
                
                $action = 'unsaved';
            } else {
                // Save the artwork
                $stmt = $conn->prepare("INSERT INTO saved_posts (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())");
                $stmt->bindParam(':post_id', $artwork_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $action = 'saved';
            }
            
            $response['success'] = true;
            $response['data'] = [
                'action' => $action
            ];
            break;
            
        case 'follow_artist':
            // Follow or unfollow an artist
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
            
            if ($artist_id <= 0 || $artist_id === $user_id) {
                $response['message'] = 'Invalid artist ID';
                break;
            }
            
            // Check if user already follows the artist
            $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
            $stmt->bindParam(':follower_id', $user_id);
            $stmt->bindParam(':followed_id', $artist_id);
            $stmt->execute();
            
            $follow_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($follow_exists) {
                // Unfollow the artist
                $stmt = $conn->prepare("DELETE FROM follows WHERE id = :id");
                $stmt->bindParam(':id', $follow_exists['id']);
                $stmt->execute();
                
                $action = 'unfollowed';
            } else {
                // Follow the artist
                $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id, created_at) VALUES (:follower_id, :followed_id, NOW())");
                $stmt->bindParam(':follower_id', $user_id);
                $stmt->bindParam(':followed_id', $artist_id);
                $stmt->execute();
                
                $action = 'followed';
                
                // Create notification
                // Get follower username
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $username = $stmt->fetchColumn();
                
                $message = $username . ' started following you';
                
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, actor_id, message, created_at) 
                    VALUES (:user_id, 'follow', :actor_id, :message, NOW())
                ");
                $stmt->bindParam(':user_id', $artist_id);
                $stmt->bindParam(':actor_id', $user_id);
                $stmt->bindParam(':message', $message);
                $stmt->execute();
            }
            
            // Get updated follower count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = :followed_id");
            $stmt->bindParam(':followed_id', $artist_id);
            $stmt->execute();
            $follower_count = $stmt->fetchColumn();
            
            $response['success'] = true;
            $response['data'] = [
                'action' => $action,
                'follower_count' => $follower_count
            ];
            break;
            
        case 'add_comment':
            // Add a comment to an artwork
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            
            if ($artwork_id <= 0) {
                $response['message'] = 'Invalid artwork ID';
                break;
            }
            
            if (empty($comment)) {
                $response['message'] = 'Comment cannot be empty';
                break;
            }
            
            // Check if comments are enabled for this artwork
            $stmt = $conn->prepare("SELECT comments_enabled, user_id, title FROM posts WHERE id = :id");
            $stmt->bindParam(':id', $artwork_id);
            $stmt->execute();
            $post_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post_info) {
                $response['message'] = 'Artwork not found';
                break;
            }
            
            if (!$post_info['comments_enabled']) {
                $response['message'] = 'Comments are disabled for this artwork';
                break;
            }
            
            try {
                // Add the comment
                $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (:post_id, :user_id, :content, NOW())");
                $stmt->bindParam(':post_id', $artwork_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':content', $comment);
                $stmt->execute();
                
                $comment_id = $conn->lastInsertId();
                
                // Get user info
                $stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Create notification (if commenting on someone else's artwork)
                if ($post_info['user_id'] != $user_id) {
                    $message = $user_info['username'] . ' commented on your artwork "' . $post_info['title'] . '"';
                    
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, actor_id, entity_id, message, created_at) 
                        VALUES (:user_id, 'comment', :actor_id, :entity_id, :message, NOW())
                    ");
                    $stmt->bindParam(':user_id', $post_info['user_id']);
                    $stmt->bindParam(':actor_id', $user_id);
                    $stmt->bindParam(':entity_id', $artwork_id);
                    $stmt->bindParam(':message', $message);
                    $stmt->execute();
                }
                
                // Get updated comment count
                $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
                $stmt->bindParam(':post_id', $artwork_id);
                $stmt->execute();
                $comments_count = $stmt->fetchColumn();
                
                // Format the comment for response
                $formatted_comment = [
                    'id' => $comment_id,
                    'content' => $comment,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_at_formatted' => date('M d, Y \a\t h:i A'),
                    'user' => [
                        'id' => $user_id,
                        'username' => $user_info['username'],
                        'profile_picture' => $user_info['profile_picture'] ? $user_info['profile_picture'] : '/api/placeholder/32/32'
                    ]
                ];
                
                $response['success'] = true;
                $response['data'] = [
                    'comment' => $formatted_comment,
                    'comments_count' => $comments_count
                ];
            } catch (PDOException $e) {
                // Log error
                error_log("Comment error: " . $e->getMessage());
                $response['message'] = 'An error occurred while adding your comment. Please try again.';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Explore error: " . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);