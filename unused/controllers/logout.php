<?php
// Start session
session_start();

// Include database connection
require_once('../config/database.php');

// Remove remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        
        // Delete token from database
        $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        // Remove cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    } catch (PDOException $e) {
        // Log error but continue with logout
        error_log("Logout error: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Determine response type based on request
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
} else {
    // Redirect for normal requests
    header("Location: ../gui/login.html");
    exit();
}
?>