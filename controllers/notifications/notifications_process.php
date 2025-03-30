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
        case 'get_notifications':
            // Get user notifications
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $stmt = $conn->prepare("
                SELECT 
                    n.id, n.type, n.message, n.is_read, n.created_at,
                    u.id as actor_id, u.username as actor_username, u.profile_picture as actor_profile_pic,
                    n.entity_id
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format notifications
            $formatted_notifications = [];
            
            foreach ($notifications as $notification) {
                // Format time
                $created_at = new DateTime($notification['created_at']);
                $now = new DateTime();
                $interval = $now->diff($created_at);
                
                if ($interval->days > 0) {
                    if ($interval->days > 7) {
                        $time_diff = $created_at->format('M d');
                    } else {
                        $time_diff = $interval->days . 'd ago';
                    }
                } elseif ($interval->h > 0) {
                    $time_diff = $interval->h . 'h ago';
                } else {
                    $time_diff = $interval->i > 0 ? $interval->i . 'm ago' : 'just now';
                }
                
                $formatted_notifications[] = [
                    'id' => $notification['id'],
                    'type' => $notification['type'],
                    'message' => $notification['message'],
                    'is_read' => (bool)$notification['is_read'],
                    'time' => $time_diff,
                    'created_at' => $notification['created_at'],
                    'actor' => $notification['actor_id'] ? [
                        'id' => $notification['actor_id'],
                        'username' => $notification['actor_username'],
                        'profile_picture' => $notification['actor_profile_pic'] ? $notification['actor_profile_pic'] : '/api/placeholder/40/40'
                    ] : null,
                    'entity_id' => $notification['entity_id']
                ];
            }
            
            // Get unread count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $unread_count = $stmt->fetchColumn();
            
            $response['success'] = true;
            $response['data'] = [
                'notifications' => $formatted_notifications,
                'unread_count' => $unread_count
            ];
            break;
            
        case 'mark_read':
            // Mark notification as read
            $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            
            if ($notification_id <= 0) {
                $response['message'] = 'Invalid notification ID';
                break;
            }
            
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $notification_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Notification marked as read';
            } else {
                $response['message'] = 'Failed to mark notification as read';
            }
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'All notifications marked as read';
            } else {
                $response['message'] = 'Failed to mark notifications as read';
            }
            break;
            
        case 'get_unread_count':
            // Get count of unread notifications
            $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $unread_count = $stmt->fetchColumn();
            
            $response['success'] = true;
            $response['data'] = [
                'unread_count' => $unread_count
            ];
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Notifications error: " . $e->getMessage());
    $response['message'] = 'A database error occurred. Please try again.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>