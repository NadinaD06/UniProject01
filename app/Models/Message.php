<?php
/**
 * Message Model
 * Handles message-related operations including sending, receiving, and managing messages
 */
namespace App\Models;

class Message {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Send a message
     * 
     * @param int $senderId
     * @param int $receiverId
     * @param string $content
     * @return bool
     */
    public function send($senderId, $receiverId, $content) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, content) VALUES (:sender_id, :receiver_id, :content)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':sender_id' => $senderId,
            ':receiver_id' => $receiverId,
            ':content' => $content
        ]);
    }
    
    /**
     * Get conversation between two users
     * 
     * @param int $userId1
     * @param int $userId2
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getConversation($userId1, $userId2, $limit = 50, $offset = 0) {
        $sql = "SELECT m.*, 
                s.username as sender_username,
                r.username as receiver_username
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
                   OR (m.sender_id = :user2 AND m.receiver_id = :user1)
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user1', $userId1, \PDO::PARAM_INT);
        $stmt->bindValue(':user2', $userId2, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mark messages as read
     * 
     * @param int $senderId
     * @param int $receiverId
     * @return bool
     */
    public function markAsRead($senderId, $receiverId) {
        $sql = "UPDATE messages 
                SET is_read = TRUE 
                WHERE sender_id = :sender_id 
                AND receiver_id = :receiver_id 
                AND is_read = FALSE";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':sender_id' => $senderId,
            ':receiver_id' => $receiverId
        ]);
    }
    
    /**
     * Get unread message count for a user
     * 
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM messages 
                WHERE receiver_id = :user_id 
                AND is_read = FALSE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return (int) $stmt->fetch()['count'];
    }
    
    /**
     * Get recent conversations for a user
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentConversations($userId, $limit = 10) {
        $sql = "SELECT 
                CASE 
                    WHEN m.sender_id = :user_id THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.username as other_username,
                m.content as last_message,
                m.created_at as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = other_user_id 
                 AND receiver_id = :user_id 
                 AND is_read = FALSE) as unread_count
                FROM messages m
                JOIN users u ON u.id = CASE 
                    WHEN m.sender_id = :user_id THEN m.receiver_id
                    ELSE m.sender_id
                END
                WHERE m.id IN (
                    SELECT MAX(id)
                    FROM messages
                    WHERE (sender_id = :user_id OR receiver_id = :user_id)
                    GROUP BY CASE 
                        WHEN sender_id = :user_id THEN receiver_id
                        ELSE sender_id
                    END
                )
                ORDER BY m.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
} 