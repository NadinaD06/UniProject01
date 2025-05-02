<?php
/**
* app/Models/Notification.php
**/

namespace App\Models;

use App\Core\Model;

class Notification extends Model {
    protected $table = 'notifications';
    
    // Notification types
    const TYPE_LIKE = 'like';
    const TYPE_COMMENT = 'comment';
    const TYPE_FOLLOW = 'follow';
    const TYPE_MESSAGE = 'message';
    const TYPE_MENTION = 'mention';
    const TYPE_SYSTEM = 'system';
    
    /**
     * Create a notification
     * 
     * @param int $userId User receiving the notification
     * @param string $type Notification type
     * @param int $actorId User causing the notification
     * @param int $entityId Related entity ID (post, comment, etc.)
     * @param string $message Notification message
     * @return int|bool New notification ID or false on failure
     */
    public function createNotification($userId, $type, $actorId = null, $entityId = null, $message = null) {
        return $this->create([
            'user_id' => $userId,
            'type' => $type,
            'actor_id' => $actorId,
            'entity_id' => $entityId,
            'message' => $message,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get notifications for a user
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of notifications
     */
    public function getForUser($userId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT n.*, 
                u.username as actor_username, 
                u.profile_picture as actor_profile_picture
            FROM {$this->table} n
            LEFT JOIN users u ON n.actor_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }
    
    /**
     * Get unread notifications count for a user
     * 
     * @param int $userId User ID
     * @return int Count of unread notifications
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        
        return $result['count'];
    }
    
    /**
     * Mark notifications as read
     * 
     * @param int $userId User ID
     * @param array|int|null $notificationIds Notification IDs (null for all)
     * @return bool Success status
     */
    public function markAsRead($userId, $notificationIds = null) {
        if ($notificationIds === null) {
            // Mark all notifications as read
            return $this->db->update(
                $this->table,
                ['is_read' => 1],
                'user_id = ?',
                [$userId]
            );
        }
        
        if (is_array($notificationIds)) {
            // Mark specific notifications as read
            $placeholders = implode(', ', array_fill(0, count($notificationIds), '?'));
            
            return $this->db->query(
                "UPDATE {$this->table}
                SET is_read = 1
                WHERE user_id = ? AND id IN ({$placeholders})",
                array_merge([$userId], $notificationIds)
            );
        }
        
        // Mark a single notification as read
        return $this->db->update(
            $this->table,
            ['is_read' => 1],
            'user_id = ? AND id = ?',
            [$userId, $notificationIds]
        );
    }
    
    /**
     * Delete old notifications
     * 
     * @param int $days Notifications older than this many days
     * @return bool Success status
     */
    public function deleteOld($days = 30) {
        return $this->db->query(
            "DELETE FROM {$this->table}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }
}