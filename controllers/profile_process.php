<?php
// Start session
session_start();

// Include database connection
require_once('../../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return error if not authenticated
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get request method
$request_method = $_SERVER['REQUEST_METHOD'];

// Process based on request method
switch ($request_method) {
    case 'GET':
        // Get profile information
        handleGetRequest();
        break;
    case 'POST':
        // Update profile information
        handlePostRequest();
        break;
    default:
        // Invalid request method
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
}

/**
 * Handle GET requests - retrieve profile information
 */
function handleGetRequest() {
    global $conn;

    // Get the requested user ID (if not specified, use the logged-in user's ID)
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

    try {
        // Query to get user information
        $stmt = $conn->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.age, 
                u.bio, 
                u.interests,
                u.created_at,
                (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count,
                (SELECT COUNT(*) FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id = u.id)) AS total_likes
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if the profile is of the logged-in user
            $is_own_profile = ($user_id == $_SESSION['user_id']);

            // Get follower and following counts
            $follower_stmt = $conn->prepare("
                SELECT COUNT(*) AS follower_count
                FROM followers
                WHERE followed_id = ?
            ");
            $follower_stmt->execute([$user_id]);
            $follower_data = $follower_stmt->fetch(PDO::FETCH_ASSOC);

            $following_stmt = $conn->prepare("
                SELECT COUNT(*) AS following_count
                FROM followers
                WHERE follower_id = ?
            ");
            $following_stmt->execute([$user_id]);
            $following_data = $following_stmt->fetch(PDO::FETCH_ASSOC);

            // Check if logged-in user is following this profile
            $is_following = false;
            if (!$is_own_profile) {
                $following_check = $conn->prepare("
                    SELECT COUNT(*) AS is_following
                    FROM followers
                    WHERE follower_id = ? AND followed_id = ?
                ");
                $following_check->execute([$_SESSION['user_id'], $user_id]);
                $following_result = $following_check->fetch(PDO::FETCH_ASSOC);
                $is_following = ($following_result['is_following'] > 0);
            }

            // Get recent posts (limit to 6 for initial load)
            $posts_stmt = $conn->prepare("
                SELECT 
                    p.id,
                    p.content AS title,
                    p.image_url AS image_path,
                    p.created_at,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
                FROM posts p
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
                LIMIT 6
            ");
            $posts_stmt->execute([$user_id]);
            $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check if there are more posts
            $more_posts_check = $conn->prepare("
                SELECT COUNT(*) AS total_posts
                FROM posts
                WHERE user_id = ?
            ");
            $more_posts_check->execute([$user_id]);
            $total_posts_result = $more_posts_check->fetch(PDO::FETCH_ASSOC);
            $has_more_posts = ($total_posts_result['total_posts'] > count($posts));

            // Combine all data
            $profile_data = [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $is_own_profile ? $user['email'] : null, // Only include email for own profile
                    'age' => $user['age'],
                    'bio' => $user['bio'],
                    'interests' => $user['interests'],
                    'member_since' => date('F Y', strtotime($user['created_at'])),
                    'post_count' => $user['post_count'],
                    'total_likes' => $user['total_likes']
                ],
                'social' => [
                    'follower_count' => $follower_data['follower_count'],
                    'following_count' => $following_data['following_count'],
                    'is_following' => $is_following,
                    'is_own_profile' => $is_own_profile
                ],
                'posts' => [
                    'items' => $posts,
                    'has_more' => $has_more_posts
                ],
                'success' => true
            ];

            // Return profile data
            header('Content-Type: application/json');
            echo json_encode($profile_data);
        } else {
            // User not found
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch (PDOException $e) {
        // Database error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle POST requests - update profile information or perform actions
 */
function handlePostRequest() {
    global $conn;

    // Get the action type from the request
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

    switch ($action) {
        case 'update_profile':
            updateProfileInfo();
            break;
        case 'toggle_follow':
            toggleFollowUser();
            break;
        default:
            // Invalid action
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

/**
 * Update user profile information
 */
function updateProfileInfo() {
    global $conn;

    try {
        // Get and sanitize input
        $bio = isset($_POST['bio']) ? sanitize_input($_POST['bio']) : null;
        $age = isset($_POST['age']) ? intval($_POST['age']) : null;
        $interests = isset($_POST['interests']) ? sanitize_input($_POST['interests']) : null;

        // Update user information
        $stmt = $conn->prepare("
            UPDATE users 
            SET 
                bio = ?,
                age = ?,
                interests = ?
            WHERE id = ?
        ");
        $stmt->execute([$bio, $age, $interests, $_SESSION['user_id']]);

        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (PDOException $e) {
        // Database error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Toggle follow/unfollow user
 */
function toggleFollowUser() {
    global $conn;

    // Get the target user ID
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    // Check if target user exists and is not the same as logged-in user
    if ($target_user_id <= 0 || $target_user_id == $_SESSION['user_id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    try {
        // Check if already following
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) AS is_following
            FROM followers
            WHERE follower_id = ? AND followed_id = ?
        ");
        $check_stmt->execute([$_SESSION['user_id'], $target_user_id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $is_following = ($result['is_following'] > 0);

        if ($is_following) {
            // Unfollow
            $stmt = $conn->prepare("
                DELETE FROM followers
                WHERE follower_id = ? AND followed_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $target_user_id]);
            $message = 'Successfully unfollowed user';
        } else {
            // Follow
            $stmt = $conn->prepare("
                INSERT INTO followers (follower_id, followed_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $target_user_id]);
            $message = 'Successfully followed user';
        }

        // Get updated follower count
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) AS follower_count
            FROM followers
            WHERE followed_id = ?
        ");
        $count_stmt->execute([$target_user_id]);
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'is_following' => !$is_following, // Toggled state
            'follower_count' => $count_result['follower_count']
        ]);
    } catch (PDOException $e) {
        // Database error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>