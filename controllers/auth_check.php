<?php
/**
 * Helper file to check if a user is logged in
 * Include this file at the top of pages that require authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once(__DIR__ . '/../config/database.php');

// Check if user is logged in via session
function is_logged_in() {
    if (isset($_SESSION['user_id'])) {
        // Check for session timeout
        $timeout = 3600; // 1 hour
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Session has expired, clear it
            session_unset();
            session_destroy();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Check for remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        return check_remember_token();
    }
    
    return false;
}

// Check remember me token
function check_remember_token() {
    global $conn;
    
    try {
        $token = $_COOKIE['remember_token'];
        
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.email, u.is_admin
            FROM users u
            JOIN auth_tokens t ON u.id = t.user_id
            WHERE t.token = :token AND t.expires_at > NOW()
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['last_activity'] = time();
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Remember token check error: " . $e->getMessage());
    }
    
    // If token invalid or expired, remove cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    return false;
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../gui/login.html");
        exit();
    }
}

// Check if user is an admin
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Redirect if not an admin
function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        header("Location: ../gui/login.html");
        exit();
    }
}

// Get current user ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get current username
function get_username() {
    return $_SESSION['username'] ?? null;
}

// Check session status (for AJAX requests)
function get_session_status() {
    return [
        'loggedIn' => is_logged_in(),
        'userId' => get_user_id(),
        'username' => get_username(),
        'isAdmin' => is_admin()
    ];
}
?>