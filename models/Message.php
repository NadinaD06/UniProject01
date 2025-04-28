<?php
/**
 * Message Model
 * Handles all message data operations
 */
class Message {
    private $conn;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get message by ID
     * 
     * @param int $id Message ID
     * @return array|bool Message data or false if not found
     */
    public function getMessageById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                m.id, m.sender_id, m.receiver_id, m.content, m.is_read, m.created_at,
                s.username as sender_username, s.profile_picture as sender_profile_pic,
                r.username as receiver_username, r.profile_picture as receiver_profile_pic
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE m.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get conversation between two users
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of messages
     */
    public function getConversation($user1_id, $user2_id, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                m.id, m.sender_id, m.receiver_id, m.content, m.is_read, m.created_at
            FROM messages m
            WHERE (m.sender_id = :user1_id AND m.receiver_id = :user2_id)
               OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id)
            ORDER BY m.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read if the receiver is user1
        $this->markMessagesAsRead($user2_id, $user1_id);
        
        return array_reverse($messages); // Return in chronological order
    }
    
    /**
     * Get user conversations (unique users a user has chatted with)
     * 
     * @param int $user_id User ID
     * @return array List of users with last message
     */
    public function getUserConversations($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, u.username, u.profile_picture, u.is_online, u.last_active,
                m.content as last_message, m.created_at as last_message_time, 
                m.sender_id as last_message_sender_id,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = u.id AND receiver_id = :user_id AND is_read = 0) as unread_count
            FROM users u
            JOIN (
                SELECT 
                    CASE 
                        WHEN sender_id = :user_id THEN receiver_id
                        ELSE sender_id
                    END as contact_id,
                    MAX(created_at) as max_date
                FROM messages
                WHERE sender_id = :user_id OR receiver_id = :user_id
                GROUP BY contact_id
            ) contacts ON u.id = contacts.contact_id
            JOIN messages m ON (
                (m.sender_id = :user_id AND m.receiver_id = u.id) OR
                (m.sender_id = u.id AND m.receiver_id = :user_id)
            ) AND m.created_at = contacts.max_date
            WHERE u.id NOT IN (
                SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
                UNION
                SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
            )
            ORDER BY m.created_at DESC
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Send a message
     * 
     * @param int $sender_id Sender user ID
     * @param int $receiver_id Receiver user ID
     * @param string $content Message content
     * @return int|bool The new message ID or false on failure
     */
    public function sendMessage($sender_id, $receiver_id, $content) {
        // Check if users are blocked
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM blocks
            WHERE (blocker_id = :sender_id AND blocked_id = :receiver_id)
               OR (blocker_id = :receiver_id AND blocked_id = :sender_id)
        ");
        
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Users are blocked
        }
        
        // Insert the message
        $stmt = $this->conn->prepare("
            INSERT INTO messages (
                sender_id, receiver_id, content, is_read, created_at
            ) VALUES (
                :sender_id, :receiver_id, :content, 0, NOW()
            )
        ");
        
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->bindParam(':content', $content);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Mark messages as read
     * 
     * @param int $sender_id Sender user ID
     * @param int $receiver_id Receiver user ID
     * @return bool Success status
     */
    public function markMessagesAsRead($sender_id, $receiver_id) {
        $stmt = $this->conn->prepare("
            UPDATE messages
            SET is_read = 1
            WHERE sender_id = :sender_id 
              AND receiver_id = :receiver_id 
              AND is_read = 0
        ");
        
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get unread message count for a user
     * 
     * @param int $user_id User ID
     * @return int Count of unread messages
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE receiver_id = :user_id AND is_read = 0
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Search for users to message
     * 
     * @param int $user_id Current user ID
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array List of users
     */
    public function searchUsers($user_id, $query, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, u.username, u.profile_picture, u.bio
            FROM users u
            LEFT JOIN blocks b ON (b.blocker_id = :user_id AND b.blocked_id = u.id)
                             OR (b.blocker_id = u.id AND b.blocked_id = :user_id)
            WHERE u.id != :user_id
              AND b.id IS NULL
              AND (u.username LIKE :query OR u.bio LIKE :query)
            ORDER BY 
                CASE WHEN u.username LIKE :exact_query THEN 0 ELSE 1 END,
                u.username
            LIMIT :limit
        ");
        
        $queryString = "%{$query}%";
        $exactQuery = $query;
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':query', $queryString);
        $stmt->bindParam(':exact_query', $exactQuery);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete a message
     * 
     * @param int $message_id Message ID
     * @param int $user_id User ID (must be sender or receiver)
     * @return bool Success status
     */
    public function deleteMessage($message_id, $user_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM messages
            WHERE id = :message_id
              AND (sender_id = :user_id OR receiver_id = :user_id)
        ");
        
        $stmt->bindParam(':message_id', $message_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Clear conversation between two users
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @return bool Success status
     */
    public function clearConversation($user1_id, $user2_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM messages
            WHERE (sender_id = :user1_id AND receiver_id = :user2_id)
               OR (sender_id = :user2_id AND receiver_id = :user1_id)
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        
        return $stmt->execute();
    }
    
    /**
     * Get shared media between users
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @param string $type Media type (images, files, links)
     * @param int $limit Result limit
     * @return array List of messages with media
     */
    public function getSharedMedia($user1_id, $user2_id, $type = 'images', $limit = 12) {
        $condition = "";
        
        switch ($type) {
            case 'images':
                $condition = "m.content LIKE '%<img%' OR m.content LIKE '%uploaded an image%'";
                break;
            case 'files':
                $condition = "m.content LIKE '%<file%' OR m.content LIKE '%uploaded a file%'";
                break;
            case 'links':
                $condition = "m.content LIKE '%http%'";
                break;
            default:
                return [];
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                m.id, m.sender_id, m.content, m.created_at
            FROM messages m
            WHERE ((m.sender_id = :user1_id AND m.receiver_id = :user2_id)
               OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id))
              AND ($condition)
            ORDER BY m.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if users have a conversation
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @return bool True if a conversation exists
     */
    public function hasConversation($user1_id, $user2_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM messages
            WHERE (sender_id = :user1_id AND receiver_id = :user2_id)
               OR (sender_id = :user2_id AND receiver_id = :user1_id)
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}