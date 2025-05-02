<?php
/**
 * Message Model
 * Handles message-related operations including sending, receiving, and managing messages
 */
namespace App\Models;

class Message extends Model {
    protected $table = 'messages';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content'
    ];

    /**
     * Send a message
     * @param int $senderId Sender's user ID
     * @param int $receiverId Receiver's user ID
     * @param string $content Message content
     * @return int|bool Message ID or false on failure
     */
    public function sendMessage($senderId, $receiverId, $content) {
        return $this->create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content
        ]);
    }

    /**
     * Get conversation between two users
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @param int $page Page number
     * @param int $perPage Messages per page
     * @return array
     */
    public function getConversation($userId1, $userId2, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $messages = $this->db->fetchAll(
            "SELECT m.*, 
                s.username as sender_username, s.profile_image as sender_image,
                r.username as receiver_username, r.profile_image as receiver_image
            FROM {$this->table} m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?",
            [$userId1, $userId2, $userId2, $userId1, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table}
            WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)",
            [$userId1, $userId2, $userId2, $userId1]
        )['count'];

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

        $conversations = $this->db->fetchAll(
            "SELECT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.username as other_username,
                u.profile_image as other_image,
                m.content as last_message,
                m.created_at as last_message_time,
                COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
            FROM {$this->table} m
            JOIN users u ON (
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END = u.id
            )
            WHERE m.id IN (
                SELECT MAX(id)
                FROM {$this->table}
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id
                        ELSE sender_id
                    END
            )
            GROUP BY other_user_id, u.username, u.profile_image, m.content, m.created_at
            ORDER BY last_message_time DESC
            LIMIT ? OFFSET ?",
            [$userId, $userId, $userId, $userId, $userId, $userId, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(DISTINCT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id
                    ELSE sender_id
                END
            ) as count
            FROM {$this->table}
            WHERE sender_id = ? OR receiver_id = ?",
            [$userId, $userId, $userId]
        )['count'];

        return [
            'data' => $conversations,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Mark messages as read
     * @param int $senderId Sender's user ID
     * @param int $receiverId Receiver's user ID
     * @return bool Success status
     */
    public function markAsRead($senderId, $receiverId) {
        return (bool) $this->db->update(
            $this->table,
            ['is_read' => true],
            'sender_id = ? AND receiver_id = ? AND is_read = 0',
            [$senderId, $receiverId]
        );
    }

    /**
     * Get unread message count for a user
     * @param int $userId User ID
     * @return int
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table}
            WHERE receiver_id = ? AND is_read = 0",
            [$userId]
        );
        return (int) $result['count'];
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