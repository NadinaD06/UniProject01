<?php
/**
 * app/Models/User.php
 * User model for handling user data and authentication
 */

namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected $table = 'users';
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @return int|bool New user ID or false on failure
     */
    public function createUser($userData) {
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        return $this->create([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => $passwordHash,
            'full_name' => $userData['full_name'] ?? null,
            'bio' => $userData['bio'] ?? null,
            'website' => $userData['website'] ?? null,
            'profile_picture' => $userData['profile_picture'] ?? null,
            'cover_image' => $userData['cover_image'] ?? null,
            'is_verified' => 0,
            'is_admin' => 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Authenticate user (login)
     * 
     * @param string $usernameOrEmail Username or email
     * @param string $password Password
     * @return array|bool User data or false if authentication fails
     */
    public function authenticate($usernameOrEmail, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM {$this->table} 
            WHERE (username = ? OR email = ?) AND is_active = 1",
            [$usernameOrEmail, $usernameOrEmail]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Don't return the password hash to the caller
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update user's password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return bool Success status
     */
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($userId, [
            'password_hash' => $passwordHash,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Verify user password
     * 
     * @param int $userId User ID
     * @param string $password Password to verify
     * @return bool Password is valid
     */
    public function verifyPassword($userId, $password) {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password_hash']);
    }
    
    /**
     * Store remember token for user
     * 
     * @param int $userId User ID
     * @param string $token Hashed token
     * @param int $expiry Expiry timestamp
     * @return bool Success status
     */
    public function storeRememberToken($userId, $token, $expiry) {
        // Delete any existing tokens
        $this->db->query(
            "DELETE FROM user_tokens 
            WHERE user_id = ? AND type = 'remember'",
            [$userId]
        );
        
        // Insert new token
        return $this->db->query(
            "INSERT INTO user_tokens (user_id, token, type, expires_at, created_at) 
            VALUES (?, ?, 'remember', ?, NOW())",
            [$userId, $token, date('Y-m-d H:i:s', $expiry)]
        );
    }
    
    /**
     * Get user by remember token
     * 
     * @param string $token Remember token
     * @return array|bool User data or false if not found
     */
    public function getUserByRememberToken($token) {
        $userToken = $this->db->fetch(
            "SELECT ut.user_id, ut.token 
            FROM user_tokens ut 
            WHERE ut.type = 'remember' 
            AND ut.expires_at > NOW()",
            []
        );
        
        if (!$userToken) {
            return false;
        }
        
        // Verify token using hash_equals (to prevent timing attacks)
        if (!password_verify($token, $userToken['token'])) {
            return false;
        }
        
        // Get user
        return $this->find($userToken['user_id']);
    }
    
    /**
     * Remove remember token
     * 
     * @param int $userId User ID
     * @param string $token Remember token
     * @return bool Success status
     */
    public function removeRememberToken($userId, $token) {
        // Delete token
        return $this->db->query(
            "DELETE FROM user_tokens 
            WHERE user_id = ? AND type = 'remember'",
            [$userId]
        );
    }
    
    /**
     * Store password reset token
     * 
     * @param int $userId User ID
     * @param string $token Password reset token
     * @param int $expiry Expiry timestamp
     * @return bool Success status
     */
    public function storePasswordResetToken($userId, $token, $expiry) {
        // Delete any existing tokens
        $this->db->query(
            "DELETE FROM user_tokens 
            WHERE user_id = ? AND type = 'password_reset'",
            [$userId]
        );
        
        // Insert new token
        return $this->db->query(
            "INSERT INTO user_tokens (user_id, token, type, expires_at, created_at) 
            VALUES (?, ?, 'password_reset', ?, NOW())",
            [$userId, $token, date('Y-m-d H:i:s', $expiry)]
        );
    }
    
    /**
     * Check if password reset token is valid
     * 
     * @param string $token Password reset token
     * @return bool Token is valid
     */
    public function isValidPasswordResetToken($token) {
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM user_tokens 
            WHERE token = ? AND type = 'password_reset' 
            AND expires_at > NOW()",
            [$token]
        );
        
        return $count && $count['count'] > 0;
    }
    
    /**
     * Get user by password reset token
     * 
     * @param string $token Password reset token
     * @return array|bool User data or false if not found
     */
    public function getUserByPasswordResetToken($token) {
        $userToken = $this->db->fetch(
            "SELECT user_id 
            FROM user_tokens 
            WHERE token = ? AND type = 'password_reset' 
            AND expires_at > NOW()",
            [$token]
        );
        
        if (!$userToken) {
            return false;
        }
        
        // Get user
        return $this->find($userToken['user_id']);
    }
    
    /**
     * Delete password reset token
     * 
     * @param string $token Password reset token
     * @return bool Success status
     */
    public function deletePasswordResetToken($token) {
        return $this->db->query(
            "DELETE FROM user_tokens 
            WHERE token = ? AND type = 'password_reset'",
            [$token]
        );
    }
    
    /**
     * Get followers count
     * 
     * @param int $userId User ID
     * @return int Followers count
     */
    public function getFollowersCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM follows 
            WHERE followed_id = ?",
            [$userId]
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get following count
     * 
     * @param int $userId User ID
     * @return int Following count
     */
    public function getFollowingCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM follows 
            WHERE follower_id = ?",
            [$userId]
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Check if user is following another user
     * 
     * @param int $followerId Follower ID
     * @param int $followedId Followed ID
     * @return bool Is following
     */
    public function isFollowing($followerId, $followedId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM follows 
            WHERE follower_id = ? AND followed_id = ?",
            [$followerId, $followedId]
        );
        
        return $result && (int)$result['count'] > 0;
    }
    
    /**
     * Follow a user
     * 
     * @param int $followerId Follower ID
     * @param int $followedId Followed ID
     * @return bool Success status
     */
    public function follow($followerId, $followedId) {
        // Cannot follow self
        if ($followerId === $followedId) {
            return false;
        }
        
        // Check if already following
        if ($this->isFollowing($followerId, $followedId)) {
            return true;
        }
        
        return $this->db->query(
            "INSERT INTO follows (follower_id, followed_id, created_at) 
            VALUES (?, ?, NOW())",
            [$followerId, $followedId]
        );
    }
    
    /**
     * Unfollow a user
     * 
     * @param int $followerId Follower ID
     * @param int $followedId Followed ID
     * @return bool Success status
     */
    public function unfollow($followerId, $followedId) {
        return $this->db->query(
            "DELETE FROM follows 
            WHERE follower_id = ? AND followed_id = ?",
            [$followerId, $followedId]
        );
    }
    
    /**
     * Get user stats (post count, followers, following)
     * 
     * @param int $userId User ID
     * @return array User stats
     */
    public function getUserStats($userId) {
        // Get posts count
        $postsCount = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM posts 
            WHERE user_id = ?",
            [$userId]
        );
        
        // Get followers count
        $followersCount = $this->getFollowersCount($userId);
        
        // Get following count
        $followingCount = $this->getFollowingCount($userId);
        
        return [
            'posts_count' => $postsCount ? (int)$postsCount['count'] : 0,
            'followers_count' => $followersCount,
            'following_count' => $followingCount
        ];
    }
    
    /**
     * Get users suggested to follow
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of users to return
     * @return array List of suggested users
     */
    public function getSuggestedUsers($userId, $limit = 5) {
        // Get users current user is not following and who are not the current user
        $query = "
            SELECT 
                u.id, u.username, u.profile_picture, u.bio,
                (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as follower_count
            FROM users u 
            WHERE u.id != ? 
            AND u.id NOT IN (
                SELECT followed_id FROM follows WHERE follower_id = ?
            )
            AND u.id NOT IN (
                SELECT blocked_id FROM blocks WHERE blocker_id = ?
                UNION
                SELECT blocker_id FROM blocks WHERE blocked_id = ?
            )
            ORDER BY follower_count DESC, RAND()
            LIMIT ?
        ";
        
        return $this->db->fetchAll($query, [$userId, $userId, $userId, $userId, $limit]);
    }
    
    /**
     * Search users
     * 
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array List of matching users
     */
    public function searchUsers($query, $limit = 10) {
        $searchTerm = "%$query%";
        
        $sql = "
            SELECT id, username, profile_picture, bio
            FROM {$this->table}
            WHERE username LIKE ? OR full_name LIKE ?
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $limit]);
    }
    
    /**
     * Check if a user is blocked
     * 
     * @param int $userId User ID
     * @param int $targetId Target user ID to check
     * @return bool True if user is blocked
     */
    public function isUserBlocked($userId, $targetId) {
        $sql = "
            SELECT COUNT(*) as count 
            FROM blocks
            WHERE (blocker_id = ? AND blocked_id = ?)
        ";
        
        $result = $this->db->fetch($sql, [$userId, $targetId]);
        
        return $result && (int)$result['count'] > 0;
    }
}