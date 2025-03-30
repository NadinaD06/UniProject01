<?php
/**
 * Login Page
 */

// Start session
session_start();

// Include utility functions
require_once '../includes/utilities.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: feed.php");
    exit();
}

// Set page title and CSS
$page_title = "ArtSpace - Login";
$page_css = "login";
$page_js = "login";

// Check for error/success message from redirect
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;

// Clear any session messages
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <a href="../index.php" class="logo-link">
            <h1>ArtSpace</h1>
        </a>
        <h2>Welcome Back</h2>
        <p>Share your art, grow your skills, and join a community that supports your creative journey.</p>
        
        <?php if ($error_message): ?>
            <div class="status-message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="status-message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form id="loginForm">
            <div>
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="password-container">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                </div>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Remember Me</label>
            </div>
            <div>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>
            <button type="submit">Log In</button>
        </form>
        <p class="signup-link">Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
    
    <script src="../assets/js/login.js"></script>
</body>
</html>