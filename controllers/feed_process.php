<?php
// Start session
session_start();

// Include database connection
require_once('../../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not authenticated, return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get the current user ID
$user_id = $_SESSION['user_id'];

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle different request types
$request_method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'get_feed';

switch ($request_method) {
    case 'GET':
        switch ($action) {
            case 'get_feed':
                getFeedPosts();
                break;
            case 'get_stories':
                getStories();
                break;
            case 'get_suggestions':
                getSuggestions();
                break;
            case 'search':
                searchPosts();
                break;
            default:
                sendErrorResponse('Invalid action');
                break;
        }
        break;
    
    case 'POST':
        switch ($action) {
            case 'like_post':
                likePost();
                break;
            case 'save_post':
                savePost();
                break;
            case 'add_comment':
                addComment();
                break;
            default:
                sendErrorResponse('Invalid action');
                break;
        }
        break;
    
    default:
        sendErrorResponse('Invalid request method');
        break;
}

/**
 * Get feed posts with pagination
 */
function getFeedPosts() {
    global $conn, $user_id;
    
    try {
        // Get pagination parameters
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        // Get category filter if any
        $category = isset($_GET['category']) ? sanitize_input($_GET['category']) : null;
        
        // Build the base query
        $query = "
            SELECT 
                p.id,
                p.content AS title,
                p.image_url,
                p.created_at,
                u.id AS user_id,
                u.username,
                ad.description,
                ad.category,
                ad.tags,
                ad.used_ai,
                ad.ai_tools,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) AS is_liked,
                (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) AS is_saved
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN artwork_details ad ON p.id = ad.post_id
            WHERE 1=1
        ";
        
        // Add parameters array
        $params = [$user_id, $user_id];
        
        // Add category filter if provided
        if ($category) {
            $query .= " AND ad.category = ?";
            $params[] = $category;
        }
        
        // Add following filter (posts from users the current user follows)
        $following_filter = isset($_GET['following_only']) && $_GET['following_only'] === 'true';
        if ($following_filter) {
            $query .= " AND p.user_id IN (SELECT followed_id FROM followers WHERE follower_id = ?)";
            $params[] = $user_id;
        }
        
        // Complete the query with ordering and pagination
        $query .= "
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total post count for pagination
        $count_query = "
            SELECT COUNT(*) AS total
            FROM posts p
            LEFT JOIN artwork_details ad ON p.id = ad.post_id
            WHERE 1=1
        ";
        
        // Add parameters array for count query
        $count_params = [];
        
        // Add category filter if provided
        if ($category) {
            $count_query .= " AND ad.category = ?";
            $count_params[] = $category;
        }
        
        // Add following filter
        if ($following_filter) {
            $count_query .= " AND p.user_id IN (SELECT followed_id FROM followers WHERE follower_id = ?)";
            $count_params[] = $user_id;
        }
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($count_params);
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_posts = $count_result['total'];
        
        // Process results for easier front-end consumption
        $processed_posts = [];
        foreach ($posts as $post) {
            // Format the data
            $processed_post = [
                'id' => $post['id'],
                'title' => $post['title'],
                'description' => $post['description'],
                'image_url' => $post['image_url'],
                'category' => $post['category'],
                'tags' => $post['tags'],
                'created_at' => $post['created_at'],
                'created_at_formatted' => formatTimeAgo($post['created_at']),
                'author' => [
                    'id' => $post['user_id'],
                    'username' => $post['username']
                ],
                'stats' => [
                    'likes' => $post['likes_count'],
                    'comments' => $post['comments_count']
                ],
                'user_interaction' => [
                    'liked' => (bool)$post['is_liked'],
                    'saved' => (bool)$post['is_saved']
                ],
                'ai_info' => [
                    'used_ai' => (bool)$post['used_ai'],
                    'ai_tools' => $post['ai_tools']
                ]
            ];
            
            // Get top 2 comments for this post
            $comments_stmt = $conn->prepare("
                SELECT 
                    c.id,
                    c.content,
                    c.created_at,
                    u.id AS user_id,
                    u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC
                LIMIT 2
            ");
            $comments_stmt->execute([$post['id']]);
            $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed_comments = [];
            foreach ($comments as $comment) {
                $processed_comments[] = [
                    'id' => $comment['id'],
                    'content' => $comment['content'],
                    'created_at' => $comment['created_at'],
                    'created_at_formatted' => formatTimeAgo($comment['created_at']),
                    'user' => [
                        'id' => $comment['user_id'],
                        'username' => $comment['username']
                    ]
                ];
            }
            
            $processed_post['comments'] = $processed_comments;
            $processed_posts[] = $processed_post;
        }
        
        // Pagination info
        $total_pages = ceil($total_posts / $limit);
        $has_more = $page < $total_pages;
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'posts' => $processed_posts,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'has_more' => $has_more
                ]
            ]
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Get stories for the feed
 */
function getStories() {
    global $conn, $user_id;
    
    try {
        // Get stories from users the current user follows
        $stmt = $conn->prepare("
            SELECT 
                s.id,
                s.user_id,
                s.image_url,
                s.created_at,
                u.username
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE 
                s.expires_at > NOW()
                AND (
                    s.user_id IN (SELECT followed_id FROM followers WHERE follower_id = ?)
                    OR s.user_id = ?
                )
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group stories by user
        $grouped_stories = [];
        foreach ($stories as $story) {
            $user_id = $story['user_id'];
            
            if (!isset($grouped_stories[$user_id])) {
                $grouped_stories[$user_id] = [
                    'user_id' => $user_id,
                    'username' => $story['username'],
                    'stories' => []
                ];
            }
            
            $grouped_stories[$user_id]['stories'][] = [
                'id' => $story['id'],
                'image_url' => $story['image_url'],
                'created_at' => $story['created_at'],
                'created_at_formatted' => formatTimeAgo($story['created_at'])
            ];
        }
        
        // Convert to indexed array
        $result = array_values($grouped_stories);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Get user suggestions for the feed
 */
function getSuggestions() {
    global $conn, $user_id;
    
    try {
        // Get users the current user might want to follow
        // (users not already followed, ordered by popularity)
        $stmt = $conn->prepare("
            SELECT 
                u.id,
                u.username,
                (SELECT COUNT(*) FROM followers WHERE followed_id = u.id) AS follower_count
            FROM users u
            WHERE 
                u.id != ?
                AND u.id NOT IN (SELECT followed_id FROM followers WHERE follower_id = ?)
            ORDER BY follower_count DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id, $user_id]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the response
        $formatted_suggestions = [];
        foreach ($suggestions as $suggestion) {
            $formatted_suggestions[] = [
                'id' => $suggestion['id'],
                'username' => $suggestion['username'],
                'follower_count' => $suggestion['follower_count'],
                'formatted_follower_count' => formatNumber($suggestion['follower_count'])
            ];
        }
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $formatted_suggestions
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Search for posts and users
 */
function searchPosts() {
    global $conn, $user_id;
    
    try {
        // Get search term
        $search_term = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
        
        if (empty($search_term)) {
            sendErrorResponse('Search term is required');
            return;
        }
        
        // Search posts
        $post_stmt = $conn->prepare("
            SELECT 
                p.id,
                p.content AS title,
                p.image_url,
                u.id AS user_id,
                u.username,
                ad.category,
                ad.tags
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN artwork_details ad ON p.id = ad.post_id
            WHERE 
                p.content LIKE ? 
                OR ad.description LIKE ?
                OR ad.tags LIKE ?
            LIMIT 10
        ");
        $search_pattern = "%{$search_term}%";
        $post_stmt->execute([$search_pattern, $search_pattern, $search_pattern]);
        $posts = $post_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search users
        $user_stmt = $conn->prepare("
            SELECT 
                u.id,
                u.username,
                u.bio,
                (SELECT COUNT(*) FROM followers WHERE followed_id = u.id) AS follower_count
            FROM users u
            WHERE u.username LIKE ? OR u.bio LIKE ?
            LIMIT 5
        ");
        $user_stmt->execute([$search_pattern, $search_pattern]);
        $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format posts
        $formatted_posts = [];
        foreach ($posts as $post) {
            $formatted_posts[] = [
                'id' => $post['id'],
                'title' => $post['title'],
                'image_url' => $post['image_url'],
                'category' => $post['category'],
                'tags' => $post['tags'],
                'author' => [
                    'id' => $post['user_id'],
                    'username' => $post['username']
                ]
            ];
        }
        
        // Format users
        $formatted_users = [];
        foreach ($users as $user) {
            $formatted_users[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'bio' => $user['bio'],
                'follower_count' => $user['follower_count'],
                'formatted_follower_count' => formatNumber($user['follower_count'])
            ];
        }
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'posts' => $formatted_posts,
                'users' => $formatted_users
            ]
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Like/unlike a post
 */
function likePost() {
    global $conn, $user_id;
    
    try {
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            sendErrorResponse('Invalid post ID');
            return;
        }
        
        // Check if post exists
        $post_check = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $post_check->execute([$post_id]);
        
        if ($post_check->rowCount() === 0) {
            sendErrorResponse('Post not found');
            return;
        }
        
        // Check if already liked
        $like_check = $conn->prepare("
            SELECT id FROM likes 
            WHERE post_id = ? AND user_id = ?
        ");
        $like_check->execute([$post_id, $user_id]);
        $already_liked = $like_check->rowCount() > 0;
        
        if ($already_liked) {
            // Unlike the post
            $unlike_stmt = $conn->prepare("
                DELETE FROM likes 
                WHERE post_id = ? AND user_id = ?
            ");
            $unlike_stmt->execute([$post_id, $user_id]);
            $action = 'unliked';
        } else {
            // Like the post
            $like_stmt = $conn->prepare("
                INSERT INTO likes (post_id, user_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $like_stmt->execute([$post_id, $user_id]);
            $action = 'liked';
        }
        
        // Get updated like count
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) AS likes_count 
            FROM likes 
            WHERE post_id = ?
        ");
        $count_stmt->execute([$post_id]);
        $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $likes_count = $result['likes_count'];
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'action' => $action,
                'post_id' => $post_id,
                'likes_count' => $likes_count,
                'formatted_likes_count' => formatNumber($likes_count)
            ]
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Save/unsave a post
 */
function savePost() {
    global $conn, $user_id;
    
    try {
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            sendErrorResponse('Invalid post ID');
            return;
        }
        
        // Check if post exists
        $post_check = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $post_check->execute([$post_id]);
        
        if ($post_check->rowCount() === 0) {
            sendErrorResponse('Post not found');
            return;
        }
        
        // Check if already saved
        $save_check = $conn->prepare("
            SELECT id FROM saved_posts 
            WHERE post_id = ? AND user_id = ?
        ");
        $save_check->execute([$post_id, $user_id]);
        $already_saved = $save_check->rowCount() > 0;
        
        if ($already_saved) {
            // Unsave the post
            $unsave_stmt = $conn->prepare("
                DELETE FROM saved_posts 
                WHERE post_id = ? AND user_id = ?
            ");
            $unsave_stmt->execute([$post_id, $user_id]);
            $action = 'unsaved';
        } else {
            // Save the post
            $save_stmt = $conn->prepare("
                INSERT INTO saved_posts (post_id, user_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $save_stmt->execute([$post_id, $user_id]);
            $action = 'saved';
        }
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'action' => $action,
                'post_id' => $post_id
            ]
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Add a comment to a post
 */
function addComment() {
    global $conn, $user_id;
    
    try {
        // Get post ID and comment content
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_input($_POST['content']) : '';
        
        if ($post_id <= 0) {
            sendErrorResponse('Invalid post ID');
            return;
        }
        
        if (empty($content)) {
            sendErrorResponse('Comment content is required');
            return;
        }
        
        // Check if post exists
        $post_check = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $post_check->execute([$post_id]);
        
        if ($post_check->rowCount() === 0) {
            sendErrorResponse('Post not found');
            return;
        }
        
        // Add the comment
        $comment_stmt = $conn->prepare("
            INSERT INTO comments (post_id, user_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $comment_stmt->execute([$post_id, $user_id, $content]);
        $comment_id = $conn->lastInsertId();
        
        // Get the user info for the response
        $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $username = $user_result['username'];
        
        // Get updated comment count
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) AS comments_count 
            FROM comments 
            WHERE post_id = ?
        ");
        $count_stmt->execute([$post_id]);
        $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $comments_count = $result['comments_count'];
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'comment' => [
                    'id' => $comment_id,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_at_formatted' => 'Just now',
                    'user' => [
                        'id' => $user_id,
                        'username' => $username
                    ]
                ],
                'post_id' => $post_id,
                'comments_count' => $comments_count,
                'formatted_comments_count' => formatNumber($comments_count)
            ]
        ]);
        
    } catch (PDOException $e) {
        sendErrorResponse('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendErrorResponse($e->getMessage());
    }
}

/**
 * Format a timestamp into a human-readable time ago string
 */
function formatTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $time_diff = time() - $time;
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . 'm';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . 'h';
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . 'd';
    } elseif ($time_diff < 2592000) {
        $weeks = floor($time_diff / 604800);
        return $weeks . 'w';
    } elseif ($time_diff < 31536000) {
        $months = floor($time_diff / 2592000);
        return $months . 'mo';
    } else {
        $years = floor($time_diff / 31536000);
        return $years . 'y';
    }
}

/**
 * Format a number to a human-readable string (e.g., 1.2k for 1,200)
 */
function formatNumber($number) {
    if ($number < 1000) {
        return $number;
    } elseif ($number < 1000000) {
        return round($number / 1000, 1) . 'k';
    } else {
        return round($number / 1000000, 1) . 'm';
    }
}

/**
 * Send an error response
 */
function sendErrorResponse($message, $status_code = 400) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}
?>