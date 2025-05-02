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
     * @return bool Success status
     */
    public function blockUser($blockerId, $blockedId) {
        try {
            // Check if already blocked
            if ($this->isBlocked($blockerId, $blockedId)) {
                return false;
            }

            return (bool) $this->create([
                'blocker_id' => $blockerId,
                'blocked_id' => $blockedId
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Unblock a user
     * @param int $blockerId User doing the unblocking
     * @param int $blockedId User being unblocked
     * @return bool Success status
     */
    public function unblockUser($blockerId, $blockedId) {
        return (bool) $this->db->delete(
            $this->table,
            'blocker_id = ? AND blocked_id = ?',
            [$blockerId, $blockedId]
        );
    }

    /**
     * Check if a user is blocked
     * @param int $blockerId User who might have blocked
     * @param int $blockedId User who might be blocked
     * @return bool
     */
    public function isBlocked($blockerId, $blockedId) {
        return (bool) $this->db->fetch(
            "SELECT 1 FROM {$this->table} 
            WHERE blocker_id = ? AND blocked_id = ?",
            [$blockerId, $blockedId]
        );
    }

    /**
     * Get all users blocked by a user
     * @param int $userId User ID
     * @return array List of blocked users
     */
    public function getBlockedUsers($userId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, b.created_at as blocked_at
            FROM {$this->table} b
            JOIN users u ON b.blocked_id = u.id
            WHERE b.blocker_id = ?
            ORDER BY b.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get users who blocked a user
     * @param int $userId User ID
     * @return array List of users who blocked
     */
    public function getBlockerUsers($userId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, b.created_at as blocked_at
            FROM {$this->table} b
            JOIN users u ON b.blocker_id = u.id
            WHERE b.blocked_id = ?
            ORDER BY b.created_at DESC",
            [$userId]
        );
    }

    /**
     * Block a user
     */
    public function block($userId, $blockedId) {
        $sql = "INSERT INTO blocks (user_id, blocked_id, created_at) VALUES (:user_id, :blocked_id, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'blocked_id' => $blockedId
        ]);
    }
    
    /**
     * Unblock a user
     */
    public function unblock($userId, $blockedId) {
        $sql = "DELETE FROM blocks WHERE user_id = :user_id AND blocked_id = :blocked_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'blocked_id' => $blockedId
        ]);
    }
    
    /**
     * Check if user is blocked
     */
    public function isBlocked($userId, $blockedId) {
        $sql = "SELECT id FROM blocks WHERE user_id = :user_id AND blocked_id = :blocked_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'blocked_id' => $blockedId
        ]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get blocked users
     */
    public function getBlockedUsers($userId) {
        $sql = "SELECT b.*, u.username, u.profile_image
                FROM blocks b
                JOIN users u ON b.blocked_id = u.id
                WHERE b.user_id = :user_id
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get users who blocked me
     */
    public function getBlockedBy($userId) {
        $sql = "SELECT b.*, u.username, u.profile_image
                FROM blocks b
                JOIN users u ON b.user_id = u.id
                WHERE b.blocked_id = :user_id
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
} 