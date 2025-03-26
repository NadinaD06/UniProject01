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
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';

switch ($action) {
    case 'get_contacts':
        getContacts();
        break;
    case 'get_messages':
        getMessages();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'search_users':
        searchUsers();
        break;
    case 'get_user_info':
        getUserInfo();
        break;
    case 'get_shared_media':
        getSharedMedia();
        break;
    case 'delete_message':
        deleteMessage();
        break;
    case 'block_user':
        blockUser();
        break;
    default:
        // Default to getting contacts
        getContacts();
        break;
}

/**
 * Get contacts for the current user
 */
function getContacts() {
    global $conn, $user_id;
    
    try {
        // Get all users the current user has conversations with
        $stmt = $conn->prepare("
            SELECT 
                u.id,
                u.username,
                (
                    SELECT 
                        content 
                    FROM 
                        messages 
                    WHERE 
                        (sender_id = ? AND receiver_id = u.id) OR
                        (sender_id = u.id AND receiver_id = ?)
                    ORDER BY 
                        sent_at DESC 
                    LIMIT 1
                ) AS last_message,
                (
                    SELECT 
                        sent_at 
                    FROM 
                        messages 
                    WHERE 
                        (sender_id = ? AND receiver_id = u.id) OR
                        (sender_id = u.id AND receiver_id = ?)
                    ORDER BY 
                        sent_at DESC 
                    LIMIT 1
                ) AS last_message_time,
                (
                    SELECT 
                        COUNT(*) 
                    FROM 
                        messages 
                    WHERE 
                        sender_id = u.id AND 
                        receiver_id = ? AND 
                        is_read = 0
                ) AS unread_count,
                CASE 
                    WHEN (
                        SELECT MAX(sent_at) 
                        FROM messages 
                        WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)
                    ) > (
                        SELECT MAX(sent_at) 
                        FROM messages 
                        WHERE sender_id != ? AND receiver_id != u.id
                    )
                    THEN 1
                    ELSE 0
                END AS is_recent
            FROM 
                users u
            WHERE 
                u.id IN (
                    SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = ? THEN receiver_id
                            ELSE sender_id
                        END
                    FROM 
                        messages
                    WHERE 
                        sender_id = ? OR receiver_id = ?
                )
            AND 
                u.id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
                )
            ORDER BY 
                last_message_time DESC
        ");
        
        $stmt->execute([
            $user_id, $user_id, 
            $user_id, $user_id, 
            $user_id, 
            $user_id, $user_id,
            $user_id,
            $user_id, 
            $user_id, $user_id,
            $user_id
        ]);
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the contacts for the response
        $formatted_contacts = array_map(function($contact) {
            return [
                'id' => $contact['id'],
                'username' => $contact['username'],
                'last_message' => $contact['last_message'],
                'last_message_time' => $contact['last_message_time'],
                'last_message_time_formatted' => formatTimeAgo($contact['last_message_time']),
                'unread_count' => (int)$contact['unread_count'],
                'is_recent' => (bool)$contact['is_recent']
            ];
        }, $contacts);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $formatted_contacts
        ]);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get messages between the current user and another user
 */
function getMessages() {
    global $conn, $user_id;
    
    try {
        // Get the other user's ID
        $other_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($other_user_id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        // Check if the other user is blocked
        $block_check = $conn->prepare("
            SELECT COUNT(*) AS is_blocked 
            FROM blocks 
            WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
        ");
        $block_check->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $block_result = $block_check->fetch(PDO::FETCH_ASSOC);
        
        if ($block_result['is_blocked'] > 0) {
            throw new Exception('This conversation is unavailable');
        }
        
        // Get the messages
        $stmt = $conn->prepare("
            SELECT 
                id,
                sender_id,
                receiver_id,
                content,
                sent_at,
                is_read
            FROM 
                messages
            WHERE 
                (sender_id = ? AND receiver_id = ?) OR 
                (sender_id = ? AND receiver_id = ?)
            ORDER BY 
                sent_at ASC
        ");
        
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark all unread messages as read
        $mark_read = $conn->prepare("
            UPDATE messages
            SET is_read = 1
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $mark_read->execute([$other_user_id, $user_id]);
        
        // Get user information
        $user_info = $conn->prepare("
            SELECT 
                username,
                bio
            FROM 
                users
            WHERE 
                id = ?
        ");
        $user_info->execute([$other_user_id]);
        $user_data = $user_info->fetch(PDO::FETCH_ASSOC);
        
        // Format the messages for the response
        $formatted_messages = array_map(function($message) use ($user_id) {
            return [
                'id' => $message['id'],
                'sender_id' => $message['sender_id'],
                'receiver_id' => $message['receiver_id'],
                'content' => $message['content'],
                'sent_at' => $message['sent_at'],
                'sent_at_formatted' => formatTimeAgo($message['sent_at']),
                'is_read' => (bool)$message['is_read'],
                'is_sent' => $message['sender_id'] == $user_id
            ];
        }, $messages);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'messages' => $formatted_messages,
                'user' => [
                    'id' => $other_user_id,
                    'username' => $user_data['username'],
                    'bio' => $user_data['bio']
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Send a message to another user
 */
function sendMessage() {
    global $conn, $user_id;
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }
    
    try {
        // Get the request body
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        if (!$data) {
            throw new Exception('Invalid request data');
        }
        
        // Get the receiver ID and message content
        $receiver_id = isset($data['receiver_id']) ? intval($data['receiver_id']) : 0;
        $content = isset($data['content']) ? sanitize_input($data['content']) : '';
        
        if ($receiver_id <= 0) {
            throw new Exception('Invalid receiver ID');
        }
        
        if (empty($content)) {
            throw new Exception('Message content cannot be empty');
        }
        
        // Check if the receiver exists
        $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $user_check->execute([$receiver_id]);
        
        if ($user_check->rowCount() === 0) {
            throw new Exception('User not found');
        }
        
        // Check if the receiver has blocked the sender
        $block_check = $conn->prepare("
            SELECT COUNT(*) AS is_blocked 
            FROM blocks 
            WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)
        ");
        $block_check->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
        $block_result = $block_check->fetch(PDO::FETCH_ASSOC);
        
        if ($block_result['is_blocked'] > 0) {
            throw new Exception('This conversation is unavailable');
        }
        
        // Insert the message
        $stmt = $conn->prepare("
            INSERT INTO messages (
                sender_id,
                receiver_id,
                content,
                sent_at,
                is_read
            ) VALUES (?, ?, ?, NOW(), 0)
        ");
        
        $stmt->execute([$user_id, $receiver_id, $content]);
        $message_id = $conn->lastInsertId();
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $message_id,
                'sender_id' => $user_id,
                'receiver_id' => $receiver_id,
                'content' => $content,
                'sent_at' => date('Y-m-d H:i:s'),
                'sent_at_formatted' => 'Just now',
                'is_read' => false,
                'is_sent' => true
            ]
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Search for users to message
 */
function searchUsers() {
    global $conn, $user_id;
    
    try {
        // Get the search term
        $search_term = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
        
        if (empty($search_term)) {
            // Return the most recent contacts if no search term
            getContacts();
            return;
        }
        
        // Search for users
        $stmt = $conn->prepare("
            SELECT 
                id,
                username,
                bio
            FROM 
                users
            WHERE 
                username LIKE ? AND
                id != ? AND
                id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
            ORDER BY 
                username ASC
            LIMIT 10
        ");
        
        $stmt->execute(['%' . $search_term . '%', $user_id, $user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get detailed user information for the chat sidebar
 */
function getUserInfo() {
    global $conn, $user_id;
    
    try {
        // Get the other user's ID
        $other_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($other_user_id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        // Get user information
        $stmt = $conn->prepare("
            SELECT 
                username,
                bio,
                (SELECT COUNT(*) FROM followers WHERE followed_id = ?) AS follower_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = ?) AS following_count,
                (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = ? AND followed_id = ?) AS is_following,
                (SELECT COUNT(*) FROM blocks WHERE blocker_id = ? AND blocked_id = ?) AS is_blocked
            FROM 
                users
            WHERE 
                id = ?
        ");
        
        $stmt->execute([
            $other_user_id, 
            $other_user_id, 
            $other_user_id, 
            $user_id, $other_user_id,
            $user_id, $other_user_id,
            $other_user_id
        ]);
        
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data) {
            throw new Exception('User not found');
        }
        
        // Format the user data
        $user_info = [
            'id' => $other_user_id,
            'username' => $user_data['username'],
            'bio' => $user_data['bio'],
            'follower_count' => $user_data['follower_count'],
            'following_count' => $user_data['following_count'],
            'post_count' => $user_data['post_count'],
            'is_following' => (bool)$user_data['is_following'],
            'is_blocked' => (bool)$user_data['is_blocked']
        ];
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $user_info
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get shared media between users
 */
function getSharedMedia() {
    global $conn, $user_id;
    
    try {
        // Get the other user's ID
        $other_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $media_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'images';
        
        if ($other_user_id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        // Get shared media based on type
        switch ($media_type) {
            case 'images':
                // Get shared images
                $stmt = $conn->prepare("
                    SELECT 
                        m.id,
                        m.content,
                        m.sent_at
                    FROM 
                        messages m
                    WHERE 
                        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND
                        m.content LIKE '%<img%' OR m.content LIKE '%uploaded an image%'
                    ORDER BY 
                        m.sent_at DESC
                    LIMIT 12
                ");
                break;
                
            case 'files':
                // Get shared files
                $stmt = $conn->prepare("
                    SELECT 
                        m.id,
                        m.content,
                        m.sent_at
                    FROM 
                        messages m
                    WHERE 
                        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND
                        m.content LIKE '%<file%' OR m.content LIKE '%uploaded a file%'
                    ORDER BY 
                        m.sent_at DESC
                    LIMIT 12
                ");
                break;
                
            case 'links':
                // Get shared links
                $stmt = $conn->prepare("
                    SELECT 
                        m.id,
                        m.content,
                        m.sent_at
                    FROM 
                        messages m
                    WHERE 
                        ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND
                        m.content LIKE '%http%'
                    ORDER BY 
                        m.sent_at DESC
                    LIMIT 12
                ");
                break;
                
            default:
                throw new Exception('Invalid media type');
        }
        
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'type' => $media_type,
                'items' => $media
            ]
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Delete a message
 */
function deleteMessage() {
    global $conn, $user_id;
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }
    
    try {
        // Get the message ID
        $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
        
        if ($message_id <= 0) {
            throw new Exception('Invalid message ID');
        }
        
        // Check if the message belongs to the user
        $stmt = $conn->prepare("
            SELECT id 
            FROM messages 
            WHERE id = ? AND sender_id = ?
        ");
        $stmt->execute([$message_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('You can only delete your own messages');
        }
        
        // Delete the message
        $delete_stmt = $conn->prepare("
            DELETE FROM messages 
            WHERE id = ?
        ");
        $delete_stmt->execute([$message_id]);
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Block/unblock a user
 */
function blockUser() {
    global $conn, $user_id;
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }
    
    try {
        // Get the user ID and action
        $blocked_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
        
        if ($blocked_id <= 0) {
            throw new Exception('Invalid user ID');
        }
        
        if ($action !== 'block' && $action !== 'unblock') {
            throw new Exception('Invalid action');
        }
        
        if ($action === 'block') {
            // Check if already blocked
            $check_stmt = $conn->prepare("
                SELECT id 
                FROM blocks 
                WHERE blocker_id = ? AND blocked_id = ?
            ");
            $check_stmt->execute([$user_id, $blocked_id]);
            
            if ($check_stmt->rowCount() === 0) {
                // Block the user
                $block_stmt = $conn->prepare("
                    INSERT INTO blocks (
                        blocker_id,
                        blocked_id,
                        blocked_at
                    ) VALUES (?, ?, NOW())
                ");
                $block_stmt->execute([$user_id, $blocked_id]);
            }
            
            $message = 'User blocked successfully';
        } else {
            // Unblock the user
            $unblock_stmt = $conn->prepare("
                DELETE FROM blocks 
                WHERE blocker_id = ? AND blocked_id = ?
            ");
            $unblock_stmt->execute([$user_id, $blocked_id]);
            
            $message = 'User unblocked successfully';
        }
        
        // Return the response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Format a timestamp into a human-readable time ago string
 */
function formatTimeAgo($timestamp) {
    if (!$timestamp) return '';
    
    $time = strtotime($timestamp);
    $time_diff = time() - $time;
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . 'm ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . 'h ago';
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . 'd ago';
    } else {
        return date('M j', $time);
    }
}
?>