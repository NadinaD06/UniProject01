<?php
/**
 * Message Model
 * Handles message-related operations including sending, receiving, and managing messages
 */
namespace App\Models;

use App\Core\Model;

class Message extends Model {
    public function __construct($pdo) {
        parent::__construct($pdo);
    }
    
    /**
     * Get conversations for a user
     */
    public function getConversations($userId) {
        $sql = "SELECT DISTINCT 
                    u.id, u.username, u.profile_image,
                    (SELECT content FROM messages 
                     WHERE (sender_id = :user_id AND receiver_id = u.id) 
                        OR (sender_id = u.id AND receiver_id = :user_id)
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages 
                     WHERE (sender_id = :user_id AND receiver_id = u.id) 
                        OR (sender_id = u.id AND receiver_id = :user_id)
                     ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages 
                     WHERE sender_id = u.id 
                        AND receiver_id = :user_id 
                        AND is_read = 0) as unread_count
                FROM messages m
                JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
                WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id)
                    AND u.id != :user_id
                ORDER BY last_message_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get messages between two users
     */
    public function getMessages($userId1, $userId2, $limit = 50, $offset = 0) {
        $sql = "SELECT m.*, 
                    u.username, u.profile_image
                FROM messages m
                JOIN users u ON m.sender_id = u.id
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
     * Send a message
     */
    public function send($senderId, $receiverId, $content) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, content, created_at)
                VALUES (:sender_id, :receiver_id, :content, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content
        ]);
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead($senderId, $receiverId) {
        $sql = "UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = :sender_id 
                    AND receiver_id = :receiver_id 
                    AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId
        ]);
    }
    
    /**
     * Get unread message count for a user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) FROM messages 
                WHERE receiver_id = :user_id AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Delete a message
     */
    public function delete($messageId, $userId) {
        $sql = "DELETE FROM messages 
                WHERE id = :message_id 
                    AND (sender_id = :user_id OR receiver_id = :user_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'message_id' => $messageId,
            'user_id' => $userId
        ]);
    }
} 