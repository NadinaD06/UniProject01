<?php
/**
 * Notification Controller
 * Handles notification-related actions
 */
namespace App\Controllers;

use App\Models\Notification;

class NotificationController extends BaseController {
    private $notificationModel;

    public function __construct() {
        parent::__construct();
        $this->notificationModel = new Notification();
    }

    /**
     * Get user's notifications
     * @return void
     */
    public function index() {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        $page = $_GET['page'] ?? 1;
        $notifications = $this->notificationModel->getUserNotifications(
            $_SESSION['user_id'],
            $page
        );

        $this->view('notifications/index', [
            'notifications' => $notifications['data'],
            'pagination' => [
                'current_page' => $notifications['current_page'],
                'last_page' => $notifications['last_page'],
                'per_page' => $notifications['per_page'],
                'total' => $notifications['total']
            ]
        ]);
    }

    /**
     * Get unread notification count
     * @return void
     */
    public function getUnreadCount() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $count = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        $this->jsonResponse(['count' => $count]);
    }

    /**
     * Mark notifications as read
     * @return void
     */
    public function markAsRead() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $notificationIds = $_POST['notification_ids'] ?? null;
        if ($notificationIds) {
            $notificationIds = json_decode($notificationIds, true);
        }

        $success = $this->notificationModel->markAsRead(
            $_SESSION['user_id'],
            $notificationIds
        );

        if ($success) {
            $this->jsonResponse(['message' => 'Notifications marked as read']);
        } else {
            $this->jsonResponse(['error' => 'Failed to mark notifications as read'], 500);
        }
    }

    /**
     * Delete old notifications
     * @return void
     */
    public function deleteOld() {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $daysOld = $_POST['days_old'] ?? 30;
        $success = $this->notificationModel->deleteOldNotifications($daysOld);

        if ($success) {
            $this->jsonResponse(['message' => 'Old notifications deleted']);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete old notifications'], 500);
        }
    }
}