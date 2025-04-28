<?php
/**
* app/Controllers/NotificationController.php
* Improved implementation with proper error handling
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;
use App\Services\WebSocketService;

class NotificationController extends Controller {
    private $notification;
    private $webSocket;
    
    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
        
        // Initialize WebSocket service if WebSockets are enabled
        if (defined('WEBSOCKET_ENABLED') && WEBSOCKET_ENABLED) {
            $this->webSocket = new WebSocketService();
        }
    }
    
    /**
     * Display notifications page
     * 
     * @return string Rendered view
     */
    public function index() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        
        // Get notifications for user with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Get notifications
        $notifications = $this->notification->getForUser($userId, $limit, $offset);
        
        // Get total count for pagination
        $totalCount = $this->notification->getCountForUser($userId);
        $totalPages = ceil($totalCount / $limit);
        
        // Mark all as read
        $this->notification->markAsRead($userId);
        
        return $this->view('notifications/index', [
            'notifications' => $notifications,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount
        ]);
    }
    
    /**
     * Get unread notification count
     * 
     * @return void JSON response
     */
    public function getUnreadCount() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $userId = $this->auth->id();
        
        // Get unread count
        $count = $this->notification->getUnreadCount($userId);
        
        return $this->success([
            'count' => $count
        ]);
    }
    
    /**
     * Mark notifications as read
     * 
     * @return void JSON response
     */
    public function markAsRead() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Get notification IDs to mark as read
        $notificationIds = $data['notification_ids'] ?? null;
        
        // Validate notification IDs
        if (!is_null($notificationIds) && !is_array($notificationIds)) {
            return $this->error('Invalid notification IDs format');
        }
        
        // Mark notifications as read
        $success = $this->notification->markAsRead($userId, $notificationIds);
        
        if ($success) {
            return $this->success([], 'Notifications marked as read');
        } else {
            return $this->error('Failed to mark notifications as read');
        }
    }
    
    /**
     * Mark all notifications as read
     * 
     * @return void JSON response
     */
    public function markAllAsRead() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $userId = $this->auth->id();
        
        // Mark all notifications as read
        $success = $this->notification->markAsRead($userId);
        
        if ($success) {
            return $this->success([], 'All notifications marked as read');
        } else {
            return $this->error('Failed to mark notifications as read');
        }
    }
    
    /**
     * Delete a notification
     * 
     * @return void JSON response
     */
    public function delete() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Get notification ID
        $notificationId = $data['notification_id'] ?? null;
        
        // Validate notification ID
        if (is_null($notificationId) || !is_numeric($notificationId)) {
            return $this->error('Invalid notification ID');
        }
        
        // Delete notification
        $success = $this->notification->deleteUserNotification($userId, $notificationId);
        
        if ($success) {
            return $this->success([], 'Notification deleted');
        } else {
            return $this->error('Failed to delete notification');
        }
    }
    
    /**
     * Delete all notifications
     * 
     * @return void JSON response
     */
    public function deleteAll() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $userId = $this->auth->id();
        
        // Delete all notifications
        $success = $this->notification->deleteAllUserNotifications($userId);
        
        if ($success) {
            return $this->success([], 'All notifications deleted');
        } else {
            return $this->error('Failed to delete notifications');
        }
    }
    
    /**
     * Create a notification (internal use)
     * 
     * @param int $userId User ID to notify
     * @param string $type Notification type
     * @param int|null $actorId User ID who triggered the notification
     * @param int|null $entityId Related entity ID
     * @param string $message Notification message
     * @return int|bool New notification ID or false
     */
    public function createNotification($userId, $type, $actorId = null, $entityId = null, $message = null) {
        // Create notification in database
        $notificationId = $this->notification->createNotification(
            $userId, $type, $actorId, $entityId, $message
        );
        
        if ($notificationId) {
            // Send real-time notification if WebSockets are enabled
            if (isset($this->webSocket)) {
                $this->sendRealTimeNotification($notificationId);
            }
            
            return $notificationId;
        }
        
        return false;
    }
    
    /**
     * Send real-time notification via WebSocket
     * 
     * @param int $notificationId Notification ID
     * @return bool Success status
     */
    private function sendRealTimeNotification($notificationId) {
        // Get notification details
        $notification = $this->notification->find($notificationId);
        
        if (!$notification) {
            return false;
        }
        
        // Prepare notification data
        $notificationData = [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'message' => $notification['message'],
            'created_at' => $notification['created_at'],
            'is_read' => (bool)$notification['is_read']
        ];
        
        // Add actor information if available
        if ($notification['actor_id']) {
            $actor = $this->user->find($notification['actor_id']);
            
            if ($actor) {
                $notificationData['actor'] = [
                    'id' => $actor['id'],
                    'username' => $actor['username'],
                    'profile_picture' => $actor['profile_picture'] ?: '/assets/images/default-avatar.png'
                ];
            }
        }
        
        // Send to WebSocket server
        return $this->webSocket->send(
            'user.' . $notification['user_id'],
            'notification',
            $notificationData
        );
    }
    
    /**
     * Load more notifications (AJAX)
     * 
     * @return void JSON response
     */
    public function loadMore() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Get page number
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Validate page number
        if ($page < 1) {
            return $this->error('Invalid page number');
        }
        
        // Get notifications
        $notifications = $this->notification->getForUser($userId, $limit, $offset);
        
        // Get total count for pagination
        $totalCount = $this->notification->getCountForUser($userId);
        $totalPages = ceil($totalCount / $limit);
        
        return $this->success([
            'notifications' => $notifications,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
}