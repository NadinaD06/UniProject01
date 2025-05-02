<?php
/**
* app/Services/NotificationService.php
**/

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService {
    private $notification;
    private $user;
    
    public function __construct() {
        $this->notification = new Notification();
        $this->user = new User();
    }
    
    /**
     * Create a like notification
     * 
     * @param int $postId Post ID
     * @param int $actorId User who liked the post
     * @return int|bool New notification ID or false on failure
     */
    public function createLikeNotification($postId, $actorId) {
        // Get post author
        $post = $this->db->fetch(
            "SELECT user_id, title FROM posts WHERE id = ?",
            [$postId]
        );
        
        if (!$post) {
            return false;
        }
        
        // Don't notify if liking own post
        if ($post['user_id'] == $actorId) {
            return false;
        }
        
        // Get actor username
        $actor = $this->user->find($actorId);
        
        if (!$actor) {
            return false;
        }
        
        // Create notification message
        $message = "{$actor['username']} liked your post \"{$post['title']}\"";
        
        // Create notification
        return $this->notification->createNotification(
            $post['user_id'],
            Notification::TYPE_LIKE,
            $actorId,
            $postId,
            $message
        );
    }
    
    /**
     * Create a comment notification
     * 
     * @param int $postId Post ID
     * @param int $commentId Comment ID
     * @param int $actorId User who commented
     * @return int|bool New notification ID or false on failure
     */
    public function createCommentNotification($postId, $commentId, $actorId) {
        // Get post author
        $post = $this->db->fetch(
            "SELECT user_id, title FROM posts WHERE id = ?",
            [$postId]
        );
        
        if (!$post) {
            return false;
        }
        
        // Don't notify if commenting on own post
        if ($post['user_id'] == $actorId) {
            return false;
        }
        
        // Get actor username
        $actor = $this->user->find($actorId);
        
        if (!$actor) {
            return false;
        }
        
        // Create notification message
        $message = "{$actor['username']} commented on your post \"{$post['title']}\"";
        
        // Create notification
        return $this->notification->createNotification(
            $post['user_id'],
            Notification::TYPE_COMMENT,
            $actorId,
            $commentId, // Use comment ID as entity_id
            $message
        );
    }
    
    /**
     * Create a follow notification
     * 
     * @param int $followedId User being followed
     * @param int $followerId User who followed
     * @return int|bool New notification ID or false on failure
     */
    public function createFollowNotification($followedId, $followerId) {
        // Get follower username
        $follower = $this->user->find($followerId);
        
        if (!$follower) {
            return false;
        }
        
        // Create notification message
        $message = "{$follower['username']} started following you";
        
        // Create notification
        return $this->notification->createNotification(
            $followedId,
            Notification::TYPE_FOLLOW,
            $followerId,
            null,
            $message
        );
    }
    
    /**
     * Create a mention notification
     * 
     * @param int $postId Post ID
     * @param int $userId User being mentioned
     * @param int $actorId User who mentioned
     * @return int|bool New notification ID or false on failure
     */
    public function createMentionNotification($postId, $userId, $actorId) {
        // Don't notify if mentioning yourself
        if ($userId == $actorId) {
            return false;
        }
        
        // Get actor username
        $actor = $this->user->find($actorId);
        
        if (!$actor) {
            return false;
        }
        
        // Get post title
        $post = $this->db->fetch(
            "SELECT title FROM posts WHERE id = ?",
            [$postId]
        );
        
        if (!$post) {
            return false;
        }
        
        // Create notification message
        $message = "{$actor['username']} mentioned you in the post \"{$post['title']}\"";
        
        // Create notification
        return $this->notification->createNotification(
            $userId,
            Notification::TYPE_MENTION,
            $actorId,
            $postId,
            $message
        );
    }
    
    /**
     * Create a system notification
     * 
     * @param int $userId User ID
     * @param string $message Notification message
     * @return int|bool New notification ID or false on failure
     */
    public function createSystemNotification($userId, $message) {
        return $this->notification->createNotification(
            $userId,
            Notification::TYPE_SYSTEM,
            null,
            null,
            $message
        );
    }
    
    /**
     * Send real-time notification
     * 
     * @param int $notificationId Notification ID
     */
    public function sendRealTimeNotification($notificationId) {
        // Get notification
        $notification = $this->notification->find($notificationId);
        
        if (!$notification) {
            return;
        }
        
        // Get actor info
        $actor = null;
        
        if ($notification['actor_id']) {
            $actor = $this->user->find($notification['actor_id']);
        }
        
        // Prepare notification data
        $data = [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'message' => $notification['message'],
            'created_at' => $notification['created_at'],
            'is_read' => (bool) $notification['is_read']
        ];
        
        if ($actor) {
            $data['actor'] = [
                'id' => $actor['id'],
                'username' => $actor['username'],
                'profile_picture' => $actor['profile_picture'] ?: '/assets/images/default-avatar.png'
            ];
        }
        
        // Send to WebSocket server
        // In a real implementation, this would use a queue system
        // For simplicity, we're just showing the concept
        // sendWebSocketNotification($notification['user_id'], 'notification', $data);
    }
}