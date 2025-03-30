<?php
// create_post.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
require_once('../config/database.php');

try {
    $stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If there's an error message from form submission, store it
    $error_message = '';
    if (isset($_SESSION['post_error'])) {
        $error_message = $_SESSION['post_error'];
        unset($_SESSION['post_error']);
    }
    
    // If there are form values from a previous submission attempt, retrieve them
    $form_values = array();
    if (isset($_SESSION['post_form_values'])) {
        $form_values = $_SESSION['post_form_values'];
        unset($_SESSION['post_form_values']);
    }
    
} catch(PDOException $e) {
    // Log error and redirect to error page
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while preparing to create a post.";
    header("Location: error.php");
    exit();
}

// Include the HTML template
include('create_post_template.php');
?>