<?php
/**
 * Authentication Helper
 * Provides authentication utilities for controllers
 */

require_once '../config/Database.php';

class AuthHelper {
    private $conn;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize database connection
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in
     */
    public function isLoggedIn() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user ID exists in session
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verify user exists in database
        return $this->verifyUser($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public function getCurrentUserId() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_id'];
    }
    
    /**
     * Check if current user is an admin
     * 
     * @return bool True if user is an admin
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check if is_admin flag is set in session
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            return true;
        }
        
        // Verify admin status from database
        return $this->verifyAdminStatus($_SESSION['user_id']);
    }
    
    /**
     * Check if current user is a moderator
     * 
     * @return bool True if user is a moderator
     */
    public function isModerator() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check if is_moderator flag is set in session
        if (isset($_SESSION['is_moderator']) && $_SESSION['is_moderator']) {
            return true;
        }
        
        // Verify moderator status from database
        return $this->verifyModeratorStatus($_SESSION['user_id']);
    }
    
    /**
     * Check if current user has permission for a specific action
     * 
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Admins have all permissions
        if ($this->isAdmin()) {
            return true;
        }
        
        // Check user permissions from database
        return $this->checkUserPermission($_SESSION['user_id'], $permission);
    }
    
    /**
     * Verify a user exists in the database
     * 
     * @param int $user_id User ID to verify
     * @return bool True if user exists
     */
    private function verifyUser($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE id = :user_id
            AND is_active = 1
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verify a user's admin status
     * 
     * @param int $user_id User ID to check
     * @return bool True if user is an admin
     */
    private function verifyAdminStatus($user_id) {
        $stmt = $this->conn->prepare("
            SELECT is_admin 
            FROM users 
            WHERE id = :user_id
            AND is_active = 1
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Verify a user's moderator status
     * 
     * @param int $user_id User ID to check
     * @return bool True if user is a moderator
     */
    private function verifyModeratorStatus($user_id) {
        $stmt = $this->conn->prepare("
            SELECT is_moderator 
            FROM users 
            WHERE id = :user_id
            AND is_active = 1
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Check if a user has a specific permission
     * 
     * @param int $user_id User ID
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    private function checkUserPermission($user_id, $permission) {
        // In a complete implementation, you would have a user_permissions table
        // For simplicity, we'll check roles and use a mapping of roles to permissions
        
        // First check if user is admin (admins have all permissions)
        if ($this->verifyAdminStatus($user_id)) {
            return true;
        }
        
        // Next check if user is moderator
        $is_moderator = $this->verifyModeratorStatus($user_id);
        
        // Define permissions for moderators
        $moderator_permissions = [
            'manage_reports',
            'remove_content',
            'warn_users',
            'view_reports',
            'review_content'
        ];
        
        // Check if moderator has the requested permission
        if ($is_moderator && in_array($permission, $moderator_permissions)) {
            return true;
        }
        
        // Check user-specific permissions from database
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM user_permissions 
            WHERE user_id = :user_id 
              AND permission = :permission
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':permission', $permission);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Require user to be logged in
     * Redirects to login page if not logged in
     * 
     * @param string $redirect_url Optional URL to redirect back to after login
     */
    public function requireLogin($redirect_url = '') {
        if (!$this->isLoggedIn()) {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set redirect URL in session if provided
            if (!empty($redirect_url)) {
                $_SESSION['redirect_after_login'] = $redirect_url;
            }
            
            // Set error message
            $_SESSION['error_message'] = 'Please log in to access this page';
            
            // Redirect to login page
            header('Location: /login.php');
            exit;
        }
    }
    
    /**
     * Require user to be an admin
     * Redirects to access denied page if not an admin
     */
    public function requireAdmin() {
        // First make sure user is logged in
        $this->requireLogin();
        
        // Then check admin status
        if (!$this->isAdmin()) {
            // Set error message
            $_SESSION['error_message'] = 'You do not have permission to access this page';
            
            // Redirect to access denied page
            header('Location: /access_denied.php');
            exit;
        }
    }
    
    /**
     * Require user to have a specific permission
     * Redirects to access denied page if permission is not granted
     * 
     * @param string $permission Permission to check
     */
    public function requirePermission($permission) {
        // First make sure user is logged in
        $this->requireLogin();
        
        // Then check permission
        if (!$this->hasPermission($permission)) {
            // Set error message
            $_SESSION['error_message'] = 'You do not have permission to perform this action';
            
            // Redirect to access denied page
            header('Location: /access_denied.php');
            exit;
        }
    }
    
    /**
     * Get user role name
     * 
     * @return string User role name
     */
    public function getUserRole() {
        if (!$this->isLoggedIn()) {
            return 'Guest';
        }
        
        if ($this->isAdmin()) {
            return 'Administrator';
        }
        
        if ($this->isModerator()) {
            return 'Moderator';
        }
        
        return 'User';
    }
    
    /**
     * Check if current user owns a specific resource
     * 
     * @param string $resource_type Type of resource (post, comment, etc.)
     * @param int $resource_id ID of resource
     * @return bool True if user owns the resource
     */
    public function isResourceOwner($resource_type, $resource_id) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        
        switch ($resource_type) {
            case 'post':
                return $this->isPostOwner($user_id, $resource_id);
                
            case 'comment':
                return $this->isCommentOwner($user_id, $resource_id);
                
            case 'message':
                return $this->isMessageParticipant($user_id, $resource_id);
                
            case 'collection':
                return $this->isCollectionOwner($user_id, $resource_id);
                
            default:
                return false;
        }
    }
    
    /**
     * Check if a user owns a specific post
     * 
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return bool True if user owns the post
     */
    private function isPostOwner($user_id, $post_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM posts 
            WHERE id = :post_id 
              AND user_id = :user_id
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if a user owns a specific comment
     * 
     * @param int $user_id User ID
     * @param int $comment_id Comment ID
     * @return bool True if user owns the comment
     */
    private function isCommentOwner($user_id, $comment_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM comments 
            WHERE id = :comment_id 
              AND user_id = :user_id
        ");
        
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if a user is a participant in a message conversation
     * 
     * @param int $user_id User ID
     * @param int $message_id Message ID
     * @return bool True if user is a participant
     */
    private function isMessageParticipant($user_id, $message_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM messages 
            WHERE id = :message_id 
              AND (sender_id = :user_id OR receiver_id = :user_id)
        ");
        
        $stmt->bindParam(':message_id', $message_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if a user owns a specific collection
     * 
     * @param int $user_id User ID
     * @param int $collection_id Collection ID
     * @return bool True if user owns the collection
     */
    private function isCollectionOwner($user_id, $collection_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM collections 
            WHERE id = :collection_id 
              AND user_id = :user_id
        ");
        
        $stmt->bindParam(':collection_id', $collection_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    } }