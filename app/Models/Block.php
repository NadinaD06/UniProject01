<?php
/**
 * Block Model
 * Handles user blocking functionality
 */
namespace App\Models;

use App\Core\Model;

class Block extends Model {
    protected $table = 'blocks';
    protected $fillable = [
        'blocker_id',
        'blocked_id'
    ];

    /**
     * Block a user
     * @param int $blockerId User doing the blocking
     * @param int $blockedId User being blocked
     * @param string $reason Reason for blocking
     * @return bool Success status
     */
    public function blockUser($blockerId, $blockedId, $reason = null) {
        // Check if already blocked
        if ($this->isBlocked($blockerId, $blockedId)) {
            return false;
        }
        
        $sql = "INSERT INTO blocks (blocker_id, blocked_id, reason, expires_at, created_at) 
                VALUES (:blocker_id, :blocked_id, :reason, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
            'reason' => $reason
        ]);
    }

    /**
     * Unblock a user
     * @param int $blockerId User doing the unblocking
     * @param int $blockedId User being unblocked
     * @return bool Success status
     */
    public function unblockUser($blockerId, $blockedId) {
        $sql = "DELETE FROM blocks 
                WHERE blocker_id = :blocker_id 
                AND blocked_id = :blocked_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId
        ]);
    }

    /**
     * Check if a user is blocked
     * @param int $blockerId User who might have blocked
     * @param int $blockedId User who might be blocked
     * @return bool
     */
    public function isBlocked($blockerId, $blockedId) {
        $sql = "SELECT COUNT(*) FROM blocks 
                WHERE blocker_id = :blocker_id 
                AND blocked_id = :blocked_id
                AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all blocks for a user
     * @param int $userId User ID
     * @return array List of blocks
     */
    public function getUserBlocks($userId) {
        $sql = "SELECT b.*, u.username as blocked_username 
                FROM blocks b
                JOIN users u ON b.blocked_id = u.id
                WHERE b.blocker_id = :user_id
                AND (b.expires_at IS NULL OR b.expires_at > NOW())
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all users who blocked a user
     * @param int $userId User ID
     * @return array List of users who blocked
     */
    public function getBlockedBy($userId) {
        $sql = "SELECT b.*, u.username as blocker_username 
                FROM blocks b
                JOIN users u ON b.blocker_id = u.id
                WHERE b.blocked_id = :user_id
                AND (b.expires_at IS NULL OR b.expires_at > NOW())
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Clean up expired blocks
     * @return bool Success status
     */
    public function cleanupExpiredBlocks() {
        $sql = "DELETE FROM blocks WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Get recently unblocked users
     * @return array List of recently unblocked users
     */
    public function getRecentlyUnblockedUsers() {
        $sql = "SELECT DISTINCT blocked_id 
                FROM blocks 
                WHERE expires_at < NOW() 
                AND expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
} 