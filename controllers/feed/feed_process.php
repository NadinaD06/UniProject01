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
        case 'get_feed':
            // Get feed parameters
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $following_only = isset($_GET['following_only']) && $_GET['following_only'] === 'true';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            
            // Build the query based on filters
            $query = "
                SELECT 
                    p.id, p.user_id, p.title, p.description, p.image_url, p.tags, p.category,
                    p.used_ai, p.ai_tools, p.created_at,
                    u.username as author_username, u.profile_picture as author_profile_pic,
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
            
            if ($following_only) {
                $whereClauses[] = "p.user_id IN (
                    SELECT followed_id FROM follows WHERE follower_id = :user_id
                    UNION
                    SELECT :user_id
                )";
            }
            
            if (!empty($category)) {
                $whereClauses[] = "p.category = :category";
                $params[':category'] = $category;
            }
            
            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }
            
            // Add order by and limit
            $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
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
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process posts to add formatted timestamp and author info
            foreach ($posts as &$post) {
                // Format created_at timestamp
                $created_at = new DateTime($post['created_at']);
                $post['created_at_formatted'] = $created_at->format('M d, Y \a\t h:i A');
                
                // Structure author info
                $post['author'] = [
                    'id' => $post['user_id'],
                    'username' => $post['author_username'],
                    'profile_picture' => $post['author_profile_pic'] ? $post['author_profile_pic'] : '/api/placeholder/40/40'
                ];
                
                // Structure AI info
                $post['ai_info'] = [
                    'used_ai' => (bool)$post['used_ai'],
                    'ai_tools' => $post['ai_tools']
                ];
                
                // Structure user interaction
                $post['user_interaction'] = [
                    'liked' => (bool)$post['user_liked'],
                    'saved' => (bool)$post['user_saved']
                ];
                
                // Structure stats
                $post['stats'] = [
                    'likes' => $post['likes_count'],
                    'comments' => $post['comments_count']
                ];
                
                // Get the most recent comments (limit to 2)
                $commentsStmt = $conn->prepare("
                    SELECT c.id, c.user_id, c.content, c.created_at, u.username
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.post_id = :post_id
                    ORDER BY c.created_at DESC
                    LIMIT 2
                ");
                $commentsStmt->bindParam(':post_id', $post['id']);
                $commentsStmt->execute();
                $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format comments
                foreach ($comments as &$comment) {
                    $comment['user'] = [
                        'id' => $comment['user_id'],
                        'username' => $comment['username']
                    ];
                    unset($comment['user_id']);
                }
                
                $post['comments'] = $comments;
                
                // Clean up redundant fields
                unset($post['author_username']);
                unset($post['author_profile_pic']);
                unset($post['user_liked']);
                unset($post['user_saved']);
            }
            
            // Check if there are more posts
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM posts");
            $countStmt->execute();
            $total_posts = $countStmt->fetchColumn();
            
            $has_more = ($offset + $limit) < $total_posts;
            
            // Build response
            $response['success'] = true;
            $response['data'] = [
                'posts' => $posts,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total_posts / $limit),
                    'has_more' => $has_more
                ]
            ];
            break;
            
        case 'get_stories':
            // Get user stories (users who have posted stories in the last 24 hours)
            $stmt = $conn->prepare("
                SELECT 
                    s.user_id, 
                    u.username, 
                    COUNT(s.id) as story_count
                FROM stories s
                JOIN users u ON s.user_id = u.id
                WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY s.user_id
                ORDER BY s.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $stories;
            break;
            
        case 'get_suggestions':
            // Get suggested artists (users not followed by current user)
            $stmt = $conn->prepare("
                SELECT 
                    u.id, 
                    u.username, 
                    u.profile_picture,
                    COUNT(f.follower_id) as follower_count
                FROM users u
                LEFT JOIN follows f ON u.id = f.followed_id
                WHERE u.id != :user_id
                AND u.id NOT IN (
                    SELECT followed_id FROM follows WHERE follower_id = :user_id
                )
                GROUP BY u.id
                ORDER BY follower_count DESC
                LIMIT 5
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format follower counts
            foreach ($suggestions as &$suggestion) {
                $count = $suggestion['follower_count'];
                if ($count >= 1000000) {
                    $suggestion['formatted_follower_count'] = round($count / 1000000, 1) . 'M';
                } elseif ($count >= 1000) {
                    $suggestion['formatted_follower_count'] = round($count / 1000, 1) . 'K';
                } else {
                    $suggestion['formatted_follower_count'] = $count;
                }
                
                // Set default profile picture if none
                if (empty($suggestion['profile_picture'])) {
                    $suggestion['profile_picture'] = '/api/placeholder/40/40';
                }
            }
            
            $response['success'] = true;
            $response['data'] = $suggestions;
            break;
            
        case 'like_post':
            // Handle liking/unliking a post
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
                
                if ($post_id <= 0) {
                    $response['message'] = 'Invalid post ID';
                    break;
                }
                
                // Check if user already liked the post
                $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $like_exists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($like_exists) {
                    // Unlike the post
                    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id");
                    $stmt->bindParam(':post_id', $post_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $action = 'unliked';
                } else {
                    // Like the post
                    $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())");
                    $stmt->bindParam(':post_id', $post_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $action = 'liked';
                }
                
                // Get updated like count
                $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                $likes_count = $stmt->fetchColumn();
                
                $response['success'] = true;
                $response['data'] = [
                    'action' => $action,
                    'likes_count' => $likes_count
                ];
            }
            break;
            
        case 'save_post':
            // Handle saving/unsaving a post
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
                
                if ($post_id <= 0) {
                    $response['message'] = 'Invalid post ID';
                    break;
                }
                
                // Check if user already saved the post
                $stmt = $conn->prepare("SELECT id FROM saved_posts WHERE post_id = :post_id AND user_id = :user_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $save_exists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($save_exists) {
                    // Unsave the post
                    $stmt = $conn->prepare("DELETE FROM saved_posts WHERE post_id = :post_id AND user_id = :user_id");
                    $stmt->bindParam(':post_id', $post_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $action = 'unsaved';
                } else {
                    // Save the post
                    $stmt = $conn->prepare("INSERT INTO saved_posts (post_id, user_id, created_at) VALUES (:post_id, :user_id, NOW())");
                    $stmt->bindParam(':post_id', $post_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $action = 'saved';
                }
                
                $response['success'] = true;
                $response['data'] = [
                    'action' => $action
                ];
            }
            break;
            
        case 'add_comment':
            // Handle adding a comment
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
                $content = isset($_POST['content']) ? trim($_POST['content']) : '';
                
                if ($post_id <= 0) {
                    $response['message'] = 'Invalid post ID';
                    break;
                }
                
                if (empty($content)) {
                    $response['message'] = 'Comment cannot be empty';
                    break;
                }
                
                // Add the comment
                $stmt = $conn->prepare("
                    INSERT INTO comments (post_id, user_id, content, created_at) 
                    VALUES (:post_id, :user_id, :content, NOW())
                ");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':content', $content);
                $stmt->execute();
                
                $comment_id = $conn->lastInsertId();
                
                // Get the user info
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $username = $stmt->fetchColumn();
                
                // Get updated comment count
                $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                $comments_count = $stmt->fetchColumn();
                
                // Format the new comment for response
                $comment = [
                    'id' => $comment_id,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $user_id,
                        'username' => $username
                    ]
                ];
                
                $response['success'] = true;
                $response['data'] = [
                    'comment' => $comment,
                    'comments_count' => $comments_count
                ];
            }
            break;
            
        case 'search':
            // Handle search
            $query = isset($_GET['query']) ? trim($_GET['query']) : '';
            
            if (strlen($query) < 3) {
                $response['message'] = 'Search query must be at least 3 characters';
                break;
            }
            
            // Search for users
            $stmt = $conn->prepare("
                SELECT 
                    id, username, profile_picture, bio
                FROM users
                WHERE 
                    username LIKE :query 
                    OR bio LIKE :query
                LIMIT 10
            ");
            $searchTerm = "%{$query}%";
            $stmt->bindParam(':query', $searchTerm);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Search for posts (tags, title, description)
            $stmt = $conn->prepare("
                SELECT 
                    p.id, p.title, p.tags, p.category,
                    u.id as user_id, u.username
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE 
                    p.title LIKE :query 
                    OR p.description LIKE :query
                    OR p.tags LIKE :query
                LIMIT 10
            ");
            $stmt->bindParam(':query', $searchTerm);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = [
                'results' => [
                    'users' => $users,
                    'posts' => $posts
                ]
            ];
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Feed error: " . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>