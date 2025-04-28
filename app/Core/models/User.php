<?php
/**
 * User Model
 * Handles all user data operations
 */
class User {
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
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|bool User data or false if not found
     */
    public function getUserById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                id, username, email, full_name, profile_picture, cover_image, 
                bio, website, is_verified, created_at
            FROM users 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|bool User data or false if not found
     */
    public function getUserByUsername($username) {
        $stmt = $this->conn->prepare("
            SELECT 
                id, username, email, full_name, profile_picture, cover_image, 
                bio, website, is_verified, created_at
            FROM users 
            WHERE username = :username
        ");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email Email address
     * @return array|bool User data or false if not found
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("
            SELECT 
                id, username, email, full_name, profile_picture, cover_image, 
                bio, website, is_verified, created_at
            FROM users 
            WHERE email = :email
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new user
     * 
     * @param array $userData User data
     * @return int|bool New user ID or false on failure
     */
    public function createUser($userData) {
        $stmt = $this->conn->prepare("
            INSERT INTO users (
                username, email, password_hash, full_name, bio, 
                interests, age, created_at
            ) VALUES (
                :username, :email, :password_hash, :full_name, :bio, 
                :interests, :age, NOW()
            )
        ");
        
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':full_name', $userData['full_name'] ?? null);
        $stmt->bindParam(':bio', $userData['bio'] ?? null);
        $stmt->bindParam(':interests', $userData['interests'] ?? null);
        $stmt->bindParam(':age', $userData['age'] ?? null);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $userData User data to update
     * @return bool Success status
     */
    public function updateUser($userId, $userData) {
        $fields = [];
        $params = [':user_id' => $userId];
        
        // Build dynamic query based on provided fields
        foreach ($userData as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($params);
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
        
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET password_hash = :password_hash 
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Verify user password
     * 
     * @param int $userId User ID
     * @param string $password Password to verify
     * @return bool Password is valid
     */
    public function verifyPassword($userId, $password) {
        $stmt = $this->conn->prepare("
            SELECT password_hash 
            FROM users 
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $hash = $stmt->fetchColumn();
        
        return password_verify($password, $hash);
    }
    
    /**
     * Authenticate user (login)
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @return array|bool User data or false if authentication fails
     */
    public function authenticate($username, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, username, email, password_hash, is_admin, profile_picture
            FROM users 
            WHERE username = :username OR email = :email
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get follower count
     * 
     * @param int $userId User ID
     * @return int Number of followers
     */
    public function getFollowerCount($userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM follows 
            WHERE followed_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get following count
     * 
     * @param int $userId User ID
     * @return int Number of users followed by this user
     */
    public function getFollowingCount($userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM follows 
            WHERE follower_id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Check if user is following another user
     * 
     * @param int $followerId Follower user ID
     * @param int $followedId Followed user ID
     * @return bool Is following
     */
    public function isFollowing($followerId, $followedId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM follows 
            WHERE follower_id = :follower_id AND followed_id = :followed_id
        ");
        
        $stmt->bindParam(':follower_id', $followerId);
        $stmt->bindParam(':followed_id', $followedId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Follow a user
     * 
     * @param int $followerId Follower user ID
     * @param int $followedId User ID to follow
     * @return bool Success status
     */
    public function followUser($followerId, $followedId) {
        // Don't allow following yourself
        if ($followerId === $followedId) {
            return false;
        }
        
        // Check if already following
        if ($this->isFollowing($followerId, $followedId)) {
            return true;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO follows (follower_id, followed_id, created_at) 
            VALUES (:follower_id, :followed_id, NOW())
        ");
        
        $stmt->bindParam(':follower_id', $followerId);
        $stmt->bindParam(':followed_id', $followedId);
        
        return $stmt->execute();
    }
    
    /**
     * Unfollow a user
     * 
     * @param int $followerId Follower user ID
     * @param int $followedId User ID to unfollow
     * @return bool Success status
     */
    public function unfollowUser($followerId, $followedId) {
        $stmt = $this->conn->prepare("
            DELETE FROM follows 
            WHERE follower_id = :follower_id AND followed_id = :followed_id
        ");
        
        $stmt->bindParam(':follower_id', $followerId);
        $stmt->bindParam(':followed_id', $followedId);
        
        return $stmt->execute();
    }
    
    /**
     * Get following users
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of users
     */
    public function getFollowing($userId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.full_name, u.profile_picture, f.created_at as followed_at
            FROM follows f
            JOIN users u ON f.followed_id = u.id
            WHERE f.follower_id = :user_id
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get followers
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of users
     */
    public function getFollowers($userId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.full_name, u.profile_picture, f.created_at as followed_at
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.followed_id = :user_id
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search users
     * 
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array List of users
     */
    public function searchUsers($query, $limit = 10) {
        $searchTerm = "%$query%";
        
        $stmt = $this->conn->prepare("
            SELECT id, username, full_name, profile_picture, bio
            FROM users
            WHERE username LIKE :query OR full_name LIKE :query OR bio LIKE :query
            LIMIT :limit
        ");
        
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user stat counts
     * 
     * @param int $userId User ID
     * @return array Stats with post count, follower count, following count
     */
    public function getUserStats($userId) {
        // Get post count
        $postStmt = $this->conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
        $postStmt->bindParam(':user_id', $userId);
        $postStmt->execute();
        $postCount = $postStmt->fetchColumn();
        
        // Get follower count
        $followerCount = $this->getFollowerCount($userId);
        
        // Get following count
        $followingCount = $this->getFollowingCount($userId);
        
        return [
            'posts_count' => $postCount,
            'followers_count' => $followerCount,
            'following_count' => $followingCount
        ];
    }
    
    /**
     * Block a user
     * 
     * @param int $blockerId User doing the blocking
     * @param int $blockedId User being blocked
     * @return bool Success status
     */
    public function blockUser($blockerId, $blockedId) {
        // Don't allow blocking yourself
        if ($blockerId === $blockedId) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO blocks (blocker_id, blocked_id, created_at)
            VALUES (:blocker_id, :blocked_id, NOW())
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ");
        
        $stmt->bindParam(':blocker_id', $blockerId);
        $stmt->bindParam(':blocked_id', $blockedId);
        
        if ($stmt->execute()) {
            // Remove any follow relationships
            $this->unfollowUser($blockerId, $blockedId);
            $this->unfollowUser($blockedId, $blockerId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Unblock a user
     * 
     * @param int $blockerId User who did the blocking
     * @param int $blockedId User who was blocked
     * @return bool Success status
     */
    public function unblockUser($blockerId, $blockedId) {
        $stmt = $this->conn->prepare("
            DELETE FROM blocks
            WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id
        ");
        
        $stmt->bindParam(':blocker_id', $blockerId);
        $stmt->bindParam(':blocked_id', $blockedId);
        
        return $stmt->execute();
    }
    
    /**
     * Check if a user is blocked
     * 
     * @param int $blockerId User who might have blocked
     * @param int $blockedId User who might be blocked
     * @return bool Is blocked
     */
    public function isUserBlocked($blockerId, $blockedId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM blocks
            WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id
        ");
        
        $stmt->bindParam(':blocker_id', $blockerId);
        $stmt->bindParam(':blocked_id', $blockedId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get blocked users list
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of blocked users
     */
    public function getBlockedUsers($userId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.full_name, u.profile_picture, b.created_at as blocked_at
            FROM blocks b
            JOIN users u ON b.blocked_id = u.id
            WHERE b.blocker_id = :user_id
            ORDER BY b.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get suggested users to follow
     * 
     * @param int $userId User ID
     * @param int $limit Result limit
     * @return array List of suggested users
     */
    public function getSuggestedUsers($userId, $limit = 5) {
        // This query finds users who:
        // 1. Are not the current user
        // 2. Are not already followed by the user
        // 3. Are not blocked by the user
        // 4. Have the most followers (popularity-based suggestion)
        
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.full_name, 
                u.profile_picture, 
                u.bio,
                (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as follower_count
            FROM users u
            WHERE u.id != :user_id
            AND u.id NOT IN (
                SELECT followed_id FROM follows WHERE follower_id = :user_id
            )
            AND u.id NOT IN (
                SELECT blocked_id FROM blocks WHERE blocker_id = :user_id
            )
            ORDER BY follower_count DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Report a user
     * 
     * @param int $reporterId User making the report
     * @param int $reportedId User being reported
     * @param string $reason Reason for the report
     * @param string $details Additional details
     * @return bool Success status
     */
    public function reportUser($reporterId, $reportedId, $reason, $details = null) {
        // Don't allow reporting yourself
        if ($reporterId === $reportedId) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO reports (
                reporter_id, reported_id, reason, details, created_at
            ) VALUES (
                :reporter_id, :reported_id, :reason, :details, NOW()
            )
        ");
        
        $stmt->bindParam(':reporter_id', $reporterId);
        $stmt->bindParam(':reported_id', $reportedId);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':details', $details);
        
        return $stmt->execute();
    }
    
    /**
     * Update user's profile picture
     * 
     * @param int $userId User ID
     * @param string $imagePath Path to profile picture
     * @return bool Success status
     */
    public function updateProfilePicture($userId, $imagePath) {
        $stmt = $this->conn->prepare("
            UPDATE users
            SET profile_picture = :profile_picture
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':profile_picture', $imagePath);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Update user's cover image
     * 
     * @param int $userId User ID
     * @param string $imagePath Path to cover image
     * @return bool Success status
     */
    public function updateCoverImage($userId, $imagePath) {
        $stmt = $this->conn->prepare("
            UPDATE users
            SET cover_image = :cover_image
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':cover_image', $imagePath);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
}