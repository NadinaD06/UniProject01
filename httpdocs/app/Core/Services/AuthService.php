<?php
/**
 * app/Services/AuthService.php
 * Authentication service handling user auth, sessions, and CSRF protection
 */
namespace App\Services;

use App\Models\User;

class AuthService {
    private $user;
    
    /**
     * Constructor initializes User model and starts session if needed
     */
    public function __construct() {
        $this->user = new User();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Attempt to authenticate a user
     * 
     * @param string $usernameOrEmail Username or email
     * @param string $password Password
     * @param bool $remember Remember login
     * @return bool Success status
     */
    public function attempt($usernameOrEmail, $password, $remember = false) {
        $user = $this->user->authenticate($usernameOrEmail, $password);
        
        if ($user) {
            $this->login($user, $remember);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log in a user
     * 
     * @param array $user User data
     * @param bool $remember Remember login
     */
    public function login($user, $remember = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool) ($user['is_admin'] ?? false);
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            $this->user->storeRememberToken($user['id'], $hashedToken, $expiry);
            
            setcookie(
                'remember_token',
                $token,
                [
                    'expires' => $expiry,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool User is logged in
     */
    public function check() {
        // Check if user is logged in via session
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Check if user is logged in via remember token
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $user = $this->user->getUserByRememberToken($token);
            
            if ($user) {
                $this->login($user);
                return true;
            }
            
            // Invalid token, delete cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        return false;
    }
    
    /**
     * Get authenticated user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return $this->user->find($_SESSION['user_id']);
    }
    
    /**
     * Get authenticated user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if authenticated user is admin
     * 
     * @return bool User is admin
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    /**
     * Log out user
     */
    public function logout() {
        // Remove remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->user->removeRememberToken($this->id(), $_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Clear session
        $_SESSION = [];
        
        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool Token is valid
     */
    public function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}