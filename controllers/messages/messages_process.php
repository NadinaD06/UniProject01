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
        case 'get_contacts':
            // Get all conversations
            $stmt = $conn->prepare("
                SELECT 
                    u.id, u.username, u.profile_picture,
                    (
                        SELECT content FROM messages 
                        WHERE (sender_id = :user_id AND receiver_id = u.id) 
                           OR (sender_id = u.id AND receiver_id = :user_id)
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ) as last_message,
                    (
                        SELECT created_at FROM messages 
                        WHERE (sender_id = :user_id AND receiver_id = u.id) 
                           OR (sender_id = u.id AND receiver_id = :user_id)
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ) as last_message_time,
                    (
                        SELECT COUNT(*) FROM messages 
                        WHERE sender_id = u.id AND receiver_id = :user_id AND is_read = 0
                    ) as unread_count
                FROM users u
                WHERE u.id IN (
                    SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = :user_id THEN receiver_id
                            ELSE sender_id
                        END as contact_id
                    FROM messages
                    WHERE sender_id = :user_id OR receiver_id = :user_id
                )
                ORDER BY last_message_time DESC
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the contacts data
            $formatted_contacts = [];
            
            foreach ($contacts as $contact) {
                // Format time difference
                $last_message_time = new DateTime($contact['last_message_time']);
                $now = new DateTime();
                $interval = $now->diff($last_message_time);
                
                if ($interval->days > 0) {
                    if ($interval->days > 7) {
                        $time_diff = $last_message_time->format('M d');
                    } else {
                        $time_diff = $interval->days . 'd';
                    }
                } elseif ($interval->h > 0) {
                    $time_diff = $interval->h . 'h';
                } else {
                    $time_diff = $interval->i > 0 ? $interval->i . 'm' : 'now';
                }
                
                $formatted_contacts[] = [
                    'id' => $contact['id'],
                    'username' => $contact['username'],
                    'profile_picture' => $contact['profile_picture'] ? $contact['profile_picture'] : '/api/placeholder/48/48',
                    'last_message' => $contact['last_message'],
                    'last_message_time' => $time_diff,
                    'unread_count' => $contact['unread_count']
                ];
            }
            
            $response['success'] = true;
            $response['data'] = $formatted_contacts;
            break;
            
        case 'get_messages':
            // Get messages for a specific conversation
            $contact_id = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : 0;
            
            if ($contact_id <= 0) {
                $response['message'] = 'Invalid contact ID';
                break;
            }
            
            // Get user info
            $stmt = $conn->prepare("SELECT id, username, profile_picture, CASE WHEN is_online = 1 THEN 1 ELSE 0 END as online_status, last_active FROM users WHERE id = :id");
            $stmt->bindParam(':id', $contact_id);
            $stmt->execute();
            $contact_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact_info) {
                $response['message'] = 'Contact not found';
                break;
            }
            
            // Format online status
            $online_status = 'Offline';
            
            if ($contact_info['online_status']) {
                $online_status = 'Online';
            } elseif ($contact_info['last_active']) {
                $last_active = new DateTime($contact_info['last_active']);
                $now = new DateTime();
                $interval = $now->diff($last_active);
                
                if ($interval->days > 0) {
                    if ($interval->days > 7) {
                        $online_status = 'Last seen ' . $last_active->format('M d');
                    } else {
                        $online_status = 'Last seen ' . $interval->days . 'd ago';
                    }
                } elseif ($interval->h > 0) {
                    $online_status = 'Last seen ' . $interval->h . 'h ago';
                } else {
                    $online_status = 'Last seen ' . ($interval->i > 0 ? $interval->i . 'm ago' : 'just now');
                }
            }
            
            // Get messages
            $stmt = $conn->prepare("
                SELECT 
                    id, sender_id, content, created_at
                FROM messages
                WHERE (sender_id = :user_id AND receiver_id = :contact_id)
                    OR (sender_id = :contact_id AND receiver_id = :user_id)
                ORDER BY created_at ASC
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':contact_id', $contact_id);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format messages
            $formatted_messages = [];
            
            foreach ($messages as $message) {
                $message_time = new DateTime($message['created_at']);
                
                $formatted_messages[] = [
                    'id' => $message['id'],
                    'sender_id' => $message['sender_id'],
                    'content' => $message['content'],
                    'is_own' => $message['sender_id'] == $user_id,
                    'time' => $message_time->format('h:i A'),
                    'date' => $message_time->format('M d, Y')
                ];
            }
            
            // Mark messages as read
            $stmt = $conn->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE sender_id = :contact_id AND receiver_id = :user_id AND is_read = 0
            ");
            $stmt->bindParam(':contact_id', $contact_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $response['success'] = true;
            $response['data'] = [
                'contact' => [
                    'id' => $contact_info['id'],
                    'username' => $contact_info['username'],
                    'profile_picture' => $contact_info['profile_picture'] ? $contact_info['profile_picture'] : '/api/placeholder/48/48',
                    'status' => $online_status
                ],
                'messages' => $formatted_messages
            ];
            break;
            
        case 'send_message':
            // Send a new message
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            
            if ($receiver_id <= 0 || $receiver_id === $user_id) {
                $response['message'] = 'Invalid receiver ID';
                break;
            }
            
            if (empty($content)) {
                $response['message'] = 'Message cannot be empty';
                break;
            }
            
            // Send message
            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, content, is_read, created_at)
                VALUES (:sender_id, :receiver_id, :content, 0, NOW())
            ");
            $stmt->bindParam(':sender_id', $user_id);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':content', $content);
            
            if ($stmt->execute()) {
                $message_id = $conn->lastInsertId();
                
                // Get current time
                $now = new DateTime();
                
                $response['success'] = true;
                $response['message'] = 'Message sent successfully';
                $response['data'] = [
                    'id' => $message_id,
                    'sender_id' => $user_id,
                    'content' => $content,
                    'is_own' => true,
                    'time' => $now->format('h:i A'),
                    'date' => $now->format('M d, Y')
                ];
            } else {
                $response['message'] = 'Failed to send message';
            }
            break;
            
        case 'search_users':
            // Search for users to start a conversation with
            $query = isset($_GET['q']) ? trim($_GET['q']) : '';
            
            if (strlen($query) < 2) {
                $response['message'] = 'Search query too short';
                break;
            }
            
            $stmt = $conn->prepare("
                SELECT id, username, profile_picture, bio
                FROM users
                WHERE username LIKE :query AND id != :user_id
                LIMIT 10
            ");
            $search_param = '%' . $query . '%';
            $stmt->bindParam(':query', $search_param);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format users
            $formatted_users = [];
            
            foreach ($users as $user) {
                $formatted_users[] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'profile_picture' => $user['profile_picture'] ? $user['profile_picture'] : '/api/placeholder/40/40',
                    'bio' => substr($user['bio'] ?? '', 0, 50) . (strlen($user['bio'] ?? '') > 50 ? '...' : '')
                ];
            }
            
            $response['success'] = true;
            $response['data'] = $formatted_users;
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Messages error: " . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>