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
     * Create new user
     * @param array $data User data
     * @return int User ID
     */
    public function createUser($data) {
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }

        return $this->create($data);
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
} 