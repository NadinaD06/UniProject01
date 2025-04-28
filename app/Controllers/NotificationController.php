<?php
/**
* app/Controllers/NotificationController.php
**/
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller {
    private $notification;
    
    public function __construct() {
        parent::__construct();
        $this->notification = new Notification();
    }
    
    public function index() {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        
        // Get notifications
        $notifications = $this->notification->getForUser($userId);
        
        // Mark all as read
        $this->notification->markAsRead($userId);
        
        return $this->view('notifications/index', [
            'notifications' => $notifications
        ]);
    }
    
    public function getUnreadCount() {
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $userId = $this->auth->id();
        
        $count = $this->notification->getUnreadCount($userId);
        
        return $this->success([
            'count' => $count
        ]);
    }
    
    public function markAsRead() {
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        $notificationIds = $data['notification_ids'] ?? null;
        
        $this->notification->markAsRead($userId, $notificationIds);
        
        return $this->success([], 'Notifications marked as read');
    }
}