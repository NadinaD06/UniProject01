<?php

namespace App\Models;

use App\Core\Model;

class Friend extends Model {
    public function __construct($pdo) {
        parent::__construct($pdo);
    }

    /**
     * Send a friend request
     */
    public function sendRequest($senderId, $receiverId) {
        // Check if request already exists
        if ($this->requestExists($senderId, $receiverId)) {
            return false;
        }

        $sql = "INSERT INTO friend_requests (sender_id, receiver_id, status, created_at) 
                VALUES (:sender_id, :receiver_id, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId
        ]);
    }

    /**
     * Accept a friend request
     */
    public function acceptRequest($requestId, $userId) {
        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update request status
            $sql = "UPDATE friend_requests 
                    SET status = 'accepted', updated_at = NOW() 
                    WHERE id = :id AND receiver_id = :user_id AND status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $requestId,
                'user_id' => $userId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new \Exception('Invalid request');
            }

            // Get request details
            $sql = "SELECT sender_id, receiver_id FROM friend_requests WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch();

            // Add friendship records
            $sql = "INSERT INTO friendships (user_id, friend_id, created_at) 
                    VALUES (:user1, :user2, NOW()), (:user2, :user1, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user1' => $request['sender_id'],
                'user2' => $request['receiver_id']
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Reject a friend request
     */
    public function rejectRequest($requestId, $userId) {
        $sql = "UPDATE friend_requests 
                SET status = 'rejected', updated_at = NOW() 
                WHERE id = :id AND receiver_id = :user_id AND status = 'pending'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $requestId,
            'user_id' => $userId
        ]);
    }

    /**
     * Get friend requests for a user
     */
    public function getRequests($userId, $status = 'pending') {
        $sql = "SELECT fr.*, u.username, u.profile_image 
                FROM friend_requests fr 
                JOIN users u ON fr.sender_id = u.id 
                WHERE fr.receiver_id = :user_id AND fr.status = :status 
                ORDER BY fr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'status' => $status
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Get user's friends
     */
    public function getFriends($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT u.*, 
                    (SELECT COUNT(*) FROM friendships f1 
                     JOIN friendships f2 ON f1.friend_id = f2.friend_id 
                     WHERE f1.user_id = :user_id AND f2.user_id = u.id) as mutual_friends 
                FROM friendships f 
                JOIN users u ON f.friend_id = u.id 
                WHERE f.user_id = :user_id 
                ORDER BY u.username 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Check if users are friends
     */
    public function areFriends($userId1, $userId2) {
        $sql = "SELECT COUNT(*) FROM friendships 
                WHERE user_id = :user1 AND friend_id = :user2";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user1' => $userId1,
            'user2' => $userId2
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a friend request exists
     */
    private function requestExists($senderId, $receiverId) {
        $sql = "SELECT COUNT(*) FROM friend_requests 
                WHERE sender_id = :sender_id AND receiver_id = :receiver_id 
                AND status = 'pending'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Remove a friend
     */
    public function removeFriend($userId, $friendId) {
        $sql = "DELETE FROM friendships 
                WHERE (user_id = :user1 AND friend_id = :user2) 
                OR (user_id = :user2 AND friend_id = :user1)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user1' => $userId,
            'user2' => $friendId
        ]);
    }
} 