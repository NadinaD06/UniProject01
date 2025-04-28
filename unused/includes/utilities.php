<?php
/**
 * Utility Functions for ArtSpace
 * Common functions used throughout the application
 */

/**
 * Sanitize user input
 * 
 * @param string $data User input to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format a timestamp to a human-readable time ago string
 * 
 * @param string $timestamp Database timestamp
 * @return string Formatted time, e.g. "2h ago", "5d ago"
 */
function format_time_ago($timestamp) {
    if (!$timestamp) return '';
    
    $time = strtotime($timestamp);
    $time_diff = time() - $time;
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . 'm ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . 'h ago';
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . 'd ago';
    } elseif ($time_diff < 2592000) {
        $weeks = floor($time_diff / 604800);
        return $weeks . 'w ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Generate CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token from form
 * @return bool True if token is valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Get file extension from MIME type
 * 
 * @param string $mime_type MIME type
 * @return string File extension
 */
function get_file_extension($mime_type) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    
    return $extensions[$mime_type] ?? 'jpg';
}

/**
 * Truncate text to specified length with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Format number for display (e.g. 1K, 1M)
 * 
 * @param int $number Number to format
 * @return string Formatted number
 */
function format_number($number) {
    if ($number < 1000) {
        return $number;
    } elseif ($number < 1000000) {
        return round($number / 1000, 1) . 'K';
    } else {
        return round($number / 1000000, 1) . 'M';
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login or redirect
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error_message'] = 'Please log in to access this page';
        header('Location: login.php');
        exit;
    }
}

/**
 * Generate a unique filename for uploaded files
 * 
 * @param string $original_filename Original filename
 * @param string $prefix Prefix for the filename
 * @return string Unique filename
 */
function generate_unique_filename($original_filename, $prefix = '') {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return $prefix . uniqid() . '_' . time() . '.' . $extension;
}
?>