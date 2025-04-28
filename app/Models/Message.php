<?php
namespace App\Models;

class Message {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function send($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, content)
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['sender_id'],
            $data['receiver_id'],
            $data['content']
        ]);
    }
    
    public function getConversation($userId1, $userId2, $limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT m.*, 
                   s.username as sender_username,
                   s.profile_image as sender_profile_image,
                   r.username as receiver_username,
                   r.profile_image as receiver_profile_image
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.sent_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getConversations($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.username as other_username,
                u.profile_image as other_profile_image,
                m.content as last_message,
                m.sent_at as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = other_user_id 
                 AND receiver_id = ? 
                 AND read_at IS NULL) as unread_count
            FROM messages m
            JOIN users u ON (
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END = u.id
            )
            WHERE m.id IN (
                SELECT MAX(id)
                FROM messages
                WHERE (sender_id = ? OR receiver_id = ?)
                GROUP BY 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id
                        ELSE sender_id
                    END
            )
            ORDER BY m.sent_at DESC
        ");
        
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($messageId) {
        $stmt = $this->pdo->prepare("
            UPDATE messages 
            SET read_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        return $stmt->execute([$messageId]);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM messages
            WHERE receiver_id = ? AND read_at IS NULL
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
} 