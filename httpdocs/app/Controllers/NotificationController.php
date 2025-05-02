<?php
/**
 * Notification Controller
 * Handles notification-related actions
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;
use App\Models\User;

class NotificationController extends Controller {
    private $notificationModel;
    private $userModel;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->notificationModel = new Notification($pdo);
        $this->userModel = new User($pdo);
    }

    /**
     * Display notifications page
     */
    public function index() {
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        $page = (int) $this->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Get notifications
        $notifications = $this->notificationModel->getForUser($userId, $perPage, $offset);
        
        // Get unread count
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        // Mark notifications as read
        $this->notificationModel->markAsRead($userId);
        
        $this->render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'user' => $this->userModel->find($userId)
        ]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount() {
        $this->requireLogin();
        
        try {
            $count = $this->notificationModel->getUnreadCount($this->getCurrentUserId());
            $this->json(['count' => $count]);
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }
        
        $notificationIds = $this->post('notification_ids', []);
        
        try {
            $this->notificationModel->markAsRead($this->getCurrentUserId(), $notificationIds);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Delete a notification
     */
    public function delete($id) {
        $this->requireLogin();
        
        try {
            $success = $this->notificationModel->delete($id, $this->getCurrentUserId());
            
            if ($success) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Notification not found.'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
}