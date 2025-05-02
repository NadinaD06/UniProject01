<?php
/**
 * Block Model
 * Handles user blocking functionality
 */
namespace App\Models;

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
} 