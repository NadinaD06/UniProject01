<?php
namespace App\Models;

/**
 * User Model
 * Handles user-related database operations
 */
class User extends Model {
    protected $table = 'users';
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'is_blocked',
        'block_until',
        'last_login'
    ];
    protected $hidden = ['password'];

    public function __construct($db) {
        parent::__construct($db);
    }

    /**
     * Find user by username
     * @param string $username Username
     * @return array|false
     */
    public function findByUsername($username) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );
    }

    /**
     * Find user by email
     * @param string $email Email address
     * @return array|false
     */
    public function findByEmail($email) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Check if username exists
     * @param string $username Username to check
     * @return bool
     */
    public function usernameExists($username) {
        return (bool) $this->db->fetch(
            "SELECT 1 FROM {$this->table} WHERE username = ?",
            [$username]
        );
    }

    /**
     * Check if email exists
     * @param string $email Email to check
     * @return bool
     */
    public function emailExists($email) {
        return (bool) $this->db->fetch(
            "SELECT 1 FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Create a new user
     * @param array $data User data (username, email, password)
     * @return int|false User ID on success, false on failure
     */
    public function create($data) {
        if (!isset($data['password'])) {
            return false;
        }
        
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, created_at) 
                VALUES (:username, :email, :password, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update user password
     * @param int $userId User ID
     * @param string $password New password
     * @return int Number of affected rows
     */
    public function updatePassword($userId, $password) {
        return $this->update($userId, [
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Block user
     * @param int $userId User ID
     * @param int $duration Block duration in seconds
     * @return int Number of affected rows
     */
    public function blockUser($userId, $duration) {
        return $this->update($userId, [
            'is_blocked' => 1,
            'block_until' => date('Y-m-d H:i:s', time() + $duration)
        ]);
    }

    /**
     * Unblock user
     * @param int $userId User ID
     * @return int Number of affected rows
     */
    public function unblockUser($userId) {
        return $this->update($userId, [
            'is_blocked' => 0,
            'block_until' => null
        ]);
    }

    /**
     * Check if user is blocked
     * @param int $userId User ID
     * @return bool
     */
    public function isBlocked($userId) {
        $user = $this->find($userId);
        if (!$user) return false;

        if (!$user['is_blocked']) return false;

        // Check if block period has expired
        if ($user['block_until'] && strtotime($user['block_until']) < time()) {
            $this->unblockUser($userId);
            return false;
        }

        return true;
    }

    /**
     * Update last login time
     * @param int $userId User ID
     * @return int Number of affected rows
     */
    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user's posts
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Posts per page
     * @return array
     */
    public function getPosts($userId, $page = 1, $perPage = 10) {
        $postModel = new Post();
        return $postModel->paginate($page, $perPage, ['user_id = ?'], [$userId]);
    }

    /**
     * Get user's messages
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Messages per page
     * @return array
     */
    public function getMessages($userId, $page = 1, $perPage = 50) {
        $messageModel = new Message();
        return $messageModel->getUserMessages($userId, $page, $perPage);
    }

    /**
     * Get user's notifications
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Notifications per page
     * @return array
     */
    public function getNotifications($userId, $page = 1, $perPage = 20) {
        $notificationModel = new Notification();
        return $notificationModel->getUserNotifications($userId, $page, $perPage);
    }

    /**
     * Get unread notification count
     * @param int $userId User ID
     * @return int
     */
    public function getUnreadNotificationCount($userId) {
        $notificationModel = new Notification();
        return $notificationModel->getUnreadCount($userId);
    }

    /**
     * Register a new user
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword
        ]);
    }
    
    /**
     * Login user
     * 
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Don't return password
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT id, username, email, role, created_at FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Delete user (admin only)
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([':id' => $userId]);
    }
    
    /**
     * Block user by another user
     * @param int $blockerId ID of the user doing the blocking
     * @param int $blockedId ID of the user being blocked
     * @return int Number of affected rows
     */
    public function blockUserByUser($blockerId, $blockedId) {
        $sql = "INSERT INTO user_blocks (blocker_id, blocked_id, created_at) 
                VALUES (:blocker_id, :blocked_id, NOW())
                ON DUPLICATE KEY UPDATE created_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':blocker_id' => $blockerId,
            ':blocked_id' => $blockedId
        ]);
    }
    
    /**
     * Unblock user by another user
     * @param int $blockerId ID of the user doing the unblocking
     * @param int $blockedId ID of the user being unblocked
     * @return int Number of affected rows
     */
    public function unblockUserByUser($blockerId, $blockedId) {
        $sql = "DELETE FROM user_blocks 
                WHERE blocker_id = :blocker_id 
                AND blocked_id = :blocked_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':blocker_id' => $blockerId,
            ':blocked_id' => $blockedId
        ]);
    }
    
    /**
     * Report a user
     * 
     * @param int $reporterId
     * @param int $reportedId
     * @param string $reason
     * @return bool
     */
    public function reportUser($reporterId, $reportedId, $reason) {
        $sql = "INSERT INTO user_reports (reporter_id, reported_id, reason) VALUES (:reporter_id, :reported_id, :reason)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':reporter_id' => $reporterId,
            ':reported_id' => $reportedId,
            ':reason' => $reason
        ]);
    }
    
    /**
     * Get all user reports (admin only)
     * 
     * @return array
     */
    public function getAllReports() {
        $sql = "SELECT r.*, 
                u1.username as reporter_username,
                u2.username as reported_username
                FROM user_reports r
                JOIN users u1 ON r.reporter_id = u1.id
                JOIN users u2 ON r.reported_id = u2.id
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($id, $data) {
        $allowed_fields = ['username', 'email', 'password', 'bio', 'profile_image'];
        $updates = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'password') {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                $updates[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 