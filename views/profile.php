<?php
// profile.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID - either the logged-in user or a user being viewed
$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

// Database connection
require_once('../config/database.php');

try {
    // Fetch user profile information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // If user doesn't exist, redirect to 404 page
        header("Location: 404.php");
        exit();
    }
    
    // Check if this is the profile owner
    $is_owner = ($user_id == $_SESSION['user_id']);
    
    // Fetch user's artwork count
    $stmt = $conn->prepare("SELECT COUNT(*) as art_count FROM artworks WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $art_count = $stmt->fetch(PDO::FETCH_ASSOC)['art_count'];
    
    // Fetch followers count
    $stmt = $conn->prepare("SELECT COUNT(*) as followers_count FROM followers WHERE followed_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $followers_count = $stmt->fetch(PDO::FETCH_ASSOC)['followers_count'];
    
    // Fetch following count
    $stmt = $conn->prepare("SELECT COUNT(*) as following_count FROM followers WHERE follower_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $following_count = $stmt->fetch(PDO::FETCH_ASSOC)['following_count'];
    
    // Fetch user's artworks
    $stmt = $conn->prepare("SELECT * FROM artworks WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 6");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if logged-in user is following this profile
    if (!$is_owner) {
        $stmt = $conn->prepare("SELECT * FROM followers WHERE follower_id = :follower_id AND followed_id = :followed_id");
        $stmt->bindParam(':follower_id', $_SESSION['user_id']);
        $stmt->bindParam(':followed_id', $user_id);
        $stmt->execute();
        $is_following = ($stmt->rowCount() > 0);
    } else {
        $is_following = false;
    }
    
} catch(PDOException $e) {
    // Log the error and show a generic message
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while loading the profile.";
    header("Location: error.php");
    exit();
}

// Include the HTML template
include('profile_template.php');
?>