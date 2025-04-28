<?php
/**
 * Block Model
 * Handles all blocking functionality between users
 */
class Block {
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
     * Block a user
     * 
     * @param int $blocker_id User who is blocking
     * @param int $blocked_id User being blocked
     * @return bool Success status
     */
    public function blockUser($blocker_id, $blocked_id) {
        // Check if already blocked
        if ($this->isBlocked($blocker_id, $blocked_id)) {
            return true; // Already blocked
        }
        
        // Insert new block record
        $stmt = $this->conn->prepare("
            INSERT INTO blocks (
                blocker_id, blocked_id, blocked_at
            ) VALUES (
                :blocker_id, :blocked_id, NOW()
            )
        ");
        
        $stmt->bindParam(':blocker_id', $blocker_id);
        $stmt->bindParam(':blocked_id', $blocked_id);
        
        if ($stmt->execute()) {
            // Also remove any follow relationships between these users
            $this->removeFollowRelationships($blocker_id, $blocked_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Unblock a user
     * 
     * @param int $blocker_id User who blocked
     * @param int $blocked_id User who was blocked
     * @return bool Success status
     */
    public function unblockUser($blocker_id, $blocked_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM blocks 
            WHERE blocker_id = :blocker_id 
              AND blocked_id = :blocked_id
        ");
        
        $stmt->bindParam(':blocker_id', $blocker_id);
        $stmt->bindParam(':blocked_id', $blocked_id);
        
        return $stmt->execute();
    }
    
    /**
     * Check if a user is blocked
     * 
     * @param int $blocker_id User who blocks
     * @param int $blocked_id User who might be blocked
     * @return bool True if user is blocked
     */
    public function isBlocked($blocker_id, $blocked_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM blocks 
            WHERE blocker_id = :blocker_id 
              AND blocked_id = :blocked_id
        ");
        
        $stmt->bindParam(':blocker_id', $blocker_id);
        $stmt->bindParam(':blocked_id', $blocked_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if users have any block relationship
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @return bool True if either user blocked the other
     */
    public function hasBlockRelationship($user1_id, $user2_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM blocks 
            WHERE (blocker_id = :user1_id AND blocked_id = :user2_id)
               OR (blocker_id = :user2_id AND blocked_id = :user1_id)
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all users blocked by a user
     * 
     * @param int $user_id User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of blocked users
     */
    public function getBlockedUsers($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                b.id, b.blocked_id, b.blocked_at,
                u.username, u.profile_picture, u.bio
            FROM blocks b
            JOIN users u ON b.blocked_id = u.id
            WHERE b.blocker_id = :user_id
            ORDER BY b.blocked_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count how many users are blocked by a user
     * 
     * @param int $user_id User ID
     * @return int Count of blocked users
     */
    public function getBlockedCount($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM blocks 
            WHERE blocker_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Check if content should be hidden from a user (because the creator is blocked)
     * 
     * @param int $user_id User viewing content
     * @param int $creator_id Content creator ID
     * @return bool True if content should be hidden
     */
    public function shouldHideContent($user_id, $creator_id) {
        return $this->hasBlockRelationship($user_id, $creator_id);
    }
    
    /**
     * Get IDs of users who blocked this user or are blocked by this user
     * Used for filtering content in feeds and searches
     * 
     * @param int $user_id User ID
     * @return array Array of user IDs
     */
    public function getBlockedUserIds($user_id) {
        $stmt = $this->conn->prepare("
            SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
            UNION
            SELECT blocker_id FROM blocks WHERE blocked_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Remove any follow relationships between users when they block each other
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @return bool Success status
     */
    private function removeFollowRelationships($user1_id, $user2_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM follows 
            WHERE (follower_id = :user1_id AND followed_id = :user2_id)
               OR (follower_id = :user2_id AND followed_id = :user1_id)
        ");
        
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        
        return $stmt->execute();
    }

    /**
     * Get block status information between two users
     * 
     * @param int $user1_id First user ID
     * @param int $user2_id Second user ID
     * @return array Block status details
     */
    public function getBlockStatus($user1_id, $user2_id) {
        $user1_blocked_user2 = $this->isBlocked($user1_id, $user2_id);
        $user2_blocked_user1 = $this->isBlocked($user2_id, $user1_id);
        
        return [
            'is_blocked' => $user1_blocked_user2 || $user2_blocked_user1,
            'user_blocked_other' => $user1_blocked_user2,
            'blocked_by_other' => $user2_blocked_user1
        ];
    }
}