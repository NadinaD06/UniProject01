<?php
/**
 * app/Services/AuthService.php
 */
namespace App\Services;

use App\Models\User;

class AuthService {
    private $user;
    
    public function __construct() {
        $this->user = new User();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function attempt($usernameOrEmail, $password, $remember = false) {
        $user = $this->user->authenticate($usernameOrEmail, $password);
        
        if ($user) {
            $this->login($user, $remember);
            return true;
        }
        
        return false;
    }
    
    public function login($user, $remember = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        
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
                $expiry,
                '/',
                '',
                true, // Secure
                true  // HttpOnly
            );
        }
    }
    
    public function check() {
        return isset($_SESSION['user_id']);
    }
    
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return $this->user->find($_SESSION['user_id']);
    }
    
    public function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
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
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Additional methods...
}