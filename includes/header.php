<?php
/**
 * Site Header Component
 * Includes navigation, search, and user menu
 */

// Make sure we have session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get config
$config = include_once __DIR__ . '/../config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Function to check if page is active
function is_active($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'ArtSpace'; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <?php if (isset($page_css) && !empty($page_css)): ?>
    <link rel="stylesheet" href="/assets/css/<?php echo $page_css; ?>.css">
    <?php endif; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="/index.php">
                    <h1>ArtSpace</h1>
                </a>
            </div>
            
            <?php if ($is_logged_in): ?>
            <nav class="main-nav">
                <ul>
                    <li class="<?php echo is_active('feed.php'); ?>">
                        <a href="/views/feed.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="<?php echo is_active('explore.php'); ?>">
                        <a href="/views/explore.php"><i class="fas fa-compass"></i> Explore</a>
                    </li>
                    <li class="<?php echo is_active('notifications.php'); ?>">
                        <a href="/views/notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                    </li>
                    <li class="<?php echo is_active('messages.php'); ?>">
                        <a href="/views/messages.php"><i class="fas fa-envelope"></i> Messages</a>
                    </li>
                    <li class="<?php echo is_active('profile.php'); ?>">
                        <a href="/views/profile.php"><i class="fas fa-user"></i> Profile</a>
                    </li>
                </ul>
            </nav>
            
            <div class="search-box">
                <input type="text" placeholder="Search ArtSpace...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
            <?php else: ?>
            <div class="nav-buttons">
                <a href="/views/login.php" class="login-btn">Log in</a>
                <a href="/views/register.php" class="register-btn">Join ArtSpace</a>
            </div>
            <?php endif; ?>
        </div>
    </header>