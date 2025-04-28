<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? $config['APP_NAME']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? ''); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Base CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css) && $page_css !== 'main'): ?>
    <link href="/assets/css/<?php echo $page_css; ?>.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- CSRF Token for AJAX calls -->
    <script>
        const CSRF_TOKEN = "<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>";
    </script>
</head>
<body class="<?php echo $body_class ?? ''; ?>">
    <header>
        <nav class="main-nav">
            <div class="nav-brand">
                <a href="/"><?php echo htmlspecialchars($config['APP_NAME']); ?></a>
            </div>
            
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/feed">Feed</a>
                    <a href="/messages">Messages</a>
                    <a href="/notifications">Notifications</a>
                    <a href="/profile">Profile</a>
                    <a href="/logout">Logout</a>
                <?php else: ?>
                    <a href="/login">Login</a>
                    <a href="/register">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <main>
        <?php echo $content; ?>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="/about">About</a>
                <a href="/terms">Terms</a>
                <a href="/privacy">Privacy</a>
                <a href="/help">Help</a>
                <a href="/contact">Contact</a>
            </div>
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['APP_NAME']); ?>. All rights reserved.
            </div>
        </div>
    </footer>
    
    <!-- Base JavaScript -->
    <script src="/assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
    <script src="/assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>
</body>
</html> 