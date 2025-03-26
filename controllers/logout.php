<?php
// Start session
session_start();

// Include database connection for token removal if needed
require_once('../config/database.php');

// Check if remember token cookie exists
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    // Delete token from database
    try {
        $userId = $_COOKIE['remember_user'];
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Just log the error, don't stop logout process
        error_log("Error removing remember token: " . $e->getMessage());
    }
    
    // Delete cookies by setting expiration in the past
    setcookie('remember_token', '', time() - 3600, "/", "", true, true);
    setcookie('remember_user', '', time() - 3600, "/", "", true, true);
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../views/login.php");
exit();
?>