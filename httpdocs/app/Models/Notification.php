<?php
/**
 * Notification Model
 * Handles notification-related operations
 */
namespace App\Models;

use App\Core\Model;

class Notification extends Model {
    protected $table = 'notifications';
    protected $fillable = [
        'user_id',
        'type',
        'content',
        'reference_id',
        'is_read'
    ];

    public function __construct($pdo) {
        parent::__construct($pdo);
    }

    /**
     * Create a new notification
     * @param int $userId User ID to notify
     * @param string $type Notification type
     * @param string $content Notification content
     * @param int|null $referenceId Related entity ID
     * @return int|bool Notification ID or false on failure
     */
    public function createNotification($userId, $type, $content, $referenceId = null) {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, type, content, reference_id, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $type, $content, $referenceId]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's notifications
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Notifications per page
     * @return array
     */
    public function getUserNotifications($userId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $notifications = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?",
            [$userId]
        )['count'];

        return [
            'data' => $notifications,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get unread notification count
     * @param int $userId User ID
     * @return int
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} 
            WHERE user_id = ? AND is_read = FALSE",
            [$userId]
        );
        
        return (int) $result['count'];
    }

    /**
     * Mark notifications as read
     * @param int $userId User ID
     * @param array|null $notificationIds Specific notification IDs to mark as read
     * @return bool Success status
     */
    public function markAsRead($userId, $notificationIds = null) {
        try {
            if ($notificationIds) {
                $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
                $sql = "UPDATE {$this->table} 
                        SET is_read = TRUE 
                        WHERE user_id = ? AND id IN ($placeholders)";
                $params = array_merge([$userId], $notificationIds);
            } else {
                $sql = "UPDATE {$this->table} 
                        SET is_read = TRUE 
                        WHERE user_id = ?";
                $params = [$userId];
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error marking notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete old notifications
     * @param int $daysOld Number of days after which to delete notifications
     * @return bool Success status
     */
    public function deleteOldNotifications($daysOld = 30) {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$daysOld]);
        } catch (\PDOException $e) {
            error_log("Error deleting old notifications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new notification
     */
    public function create($userId, $type, $data) {
        $sql = "INSERT INTO notifications (user_id, type, data, created_at) 
                VALUES (:user_id, :type, :data, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'data' => json_encode($data)
        ]);
    }

    /**
     * Get notifications for a user
     */
    public function getForUser($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll();
        
        // Decode JSON data
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true);
        }
        
        return $notifications;
    }

    /**
     * Delete a notification
     */
    public function delete($notificationId, $userId) {
        $sql = "DELETE FROM notifications 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }
} 