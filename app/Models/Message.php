<?php
/**
 * Message Model
 * Handles messaging functionality between users
 */
namespace App\Models;

class Message extends Model {
    protected $table = 'messages';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read'
    ];

    /**
     * Get conversation between two users
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @param int $page Page number
     * @param int $perPage Messages per page
     * @return array
     */
    public function getConversation($userId1, $userId2, $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;

        $messages = $this->db->fetchAll("
            SELECT 
                m.*,
                sender.username as sender_username,
                receiver.username as receiver_username
            FROM {$this->table} m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users receiver ON m.receiver_id = receiver.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ", [$userId1, $userId2, $userId2, $userId1, $perPage, $offset]);

        $total = $this->db->fetch("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)
        ", [$userId1, $userId2, $userId2, $userId1])['count'];

        return [
            'data' => $messages,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get user's conversations
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Conversations per page
     * @return array
     */
    public function getUserConversations($userId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $conversations = $this->db->fetchAll("
            WITH LastMessages AS (
                SELECT 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id 
                        ELSE sender_id 
                    END as other_user_id,
                    MAX(created_at) as last_message_time
                FROM {$this->table}
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY other_user_id
            )
            SELECT 
                u.id,
                u.username,
                m.content as last_message,
                m.created_at as last_message_time,
                m.sender_id = ? as is_sender,
                COUNT(CASE WHEN m2.is_read = 0 AND m2.receiver_id = ? THEN 1 END) as unread_count
            FROM LastMessages lm
            JOIN users u ON u.id = lm.other_user_id
            JOIN {$this->table} m ON (
                (m.sender_id = ? AND m.receiver_id = u.id) OR
                (m.sender_id = u.id AND m.receiver_id = ?)
            ) AND m.created_at = lm.last_message_time
            LEFT JOIN {$this->table} m2 ON m2.sender_id = u.id AND m2.receiver_id = ?
            GROUP BY u.id, u.username, m.content, m.created_at, m.sender_id
            ORDER BY last_message_time DESC
            LIMIT ? OFFSET ?
        ", [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $perPage, $offset]);

        $total = $this->db->fetch("
            SELECT COUNT(DISTINCT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END
            ) as count
            FROM {$this->table}
            WHERE sender_id = ? OR receiver_id = ?
        ", [$userId, $userId, $userId])['count'];

        return [
            'data' => $conversations,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Send message
     * @param int $senderId Sender user ID
     * @param int $receiverId Receiver user ID
     * @param string $content Message content
     * @return int Message ID
     */
    public function sendMessage($senderId, $receiverId, $content) {
        return $this->create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
            'is_read' => 0
        ]);
    }

    /**
     * Mark message as read
     * @param int $messageId Message ID
     * @param int $userId User ID (for verification)
     * @return bool
     */
    public function markAsRead($messageId, $userId) {
        return (bool) $this->db->update(
            $this->table,
            ['is_read' => 1],
            'id = ? AND receiver_id = ? AND is_read = 0',
            [$messageId, $userId]
        );
    }

    /**
     * Mark all messages in conversation as read
     * @param int $userId Current user ID
     * @param int $otherUserId Other user ID
     * @return int Number of affected rows
     */
    public function markConversationAsRead($userId, $otherUserId) {
        return $this->db->update(
            $this->table,
            ['is_read' => 1],
            'receiver_id = ? AND sender_id = ? AND is_read = 0',
            [$userId, $otherUserId]
        );
    }

    /**
     * Get unread message count
     * @param int $userId User ID
     * @return int
     */
    public function getUnreadCount($userId) {
        return (int) $this->db->fetch("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE receiver_id = ? AND is_read = 0
        ", [$userId])['count'];
    }

    /**
     * Delete message
     * @param int $messageId Message ID
     * @param int $userId User ID (must be sender or receiver)
     * @return bool
     */
    public function deleteMessage($messageId, $userId) {
        return (bool) $this->db->delete(
            $this->table,
            'id = ? AND (sender_id = ? OR receiver_id = ?)',
            [$messageId, $userId, $userId]
        );
    }

    /**
     * Delete entire conversation
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @return int Number of affected rows
     */
    public function deleteConversation($userId1, $userId2) {
        return $this->db->delete(
            $this->table,
            '(sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)',
            [$userId1, $userId2, $userId2, $userId1]
        );
    }
} 