<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? $config['APP_NAME']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? ''); ?>">
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    
    <!-- Base CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css)): ?>
    <link href="/assets/css/<?php echo $page_css; ?>.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="<?php echo $body_class ?? ''; ?>">
    <header>
        <nav class="main-nav">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="nav-brand">
                        <a href="/"><?php echo htmlspecialchars($config['APP_NAME']); ?></a>
                    </div>
                    
                    <div class="nav-links">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/feed">Feed</a>
                            <a href="/messages">Messages</a>
                            <a href="/notifications">
                                Notifications
                                <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                         alt="Profile" 
                                         class="rounded-circle"
                                         width="32" 
                                         height="32">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/profile">Profile</a></li>
                                    <li><a class="dropdown-item" href="/settings">Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/logout">Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/login">Login</a>
                            <a href="/register">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['flash_message']['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <?php echo $content; ?>
    </main>
    
    <footer>
        <div class="container">
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
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <!-- Base JavaScript -->
    <script src="/assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
    <script src="/assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>
</body>
</html> 