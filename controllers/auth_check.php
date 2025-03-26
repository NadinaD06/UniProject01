<?php
/**
 * Helper file to check if a user is logged in
 * Include this file at the top of pages that require authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
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
        header("Location: login.php");
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
?>