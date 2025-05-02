<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ArtSpace'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'ArtSpace - Connect, Create, Inspire'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <?php if (isset($page_css)): ?>
    <link rel="stylesheet" href="/assets/css/<?php echo $page_css; ?>.css">
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <?php if ($this->auth->check() && !isset($hide_header)): ?>
    <!-- Main Navigation -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="/feed">
                    <h1>ArtSpace</h1>
                </a>
            </div>
            <div class="search-box">
                <form action="/search" method="GET">
                    <input type="text" name="q" placeholder="Search ArtSpace..." autocomplete="off" id="searchInput">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="search-results" id="searchResults" style="display: none;"></div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li class="<?php echo $this->isActiveRoute('/feed') ? 'active' : ''; ?>">
                        <a href="/feed" title="Home"><i class="fas fa-home"></i><span class="nav-text">Home</span></a>
                    </li>
                    <li class="<?php echo $this->isActiveRoute('/explore') ? 'active' : ''; ?>">
                        <a href="/explore" title="Explore"><i class="fas fa-compass"></i><span class="nav-text">Explore</span></a>
                    </li>
                    <li class="<?php echo $this->isActiveRoute('/notifications') ? 'active' : ''; ?>">
                        <a href="/notifications" title="Notifications" class="notification-link">
                            <i class="fas fa-bell"></i>
                            <span class="nav-text">Notifications</span>
                            <span class="notification-badge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li class="<?php echo $this->isActiveRoute('/messages') ? 'active' : ''; ?>">
                        <a href="/messages" title="Messages" class="message-link">
                            <i class="fas fa-envelope"></i>
                            <span class="nav-text">Messages</span>
                            <span class="message-badge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li class="<?php echo $this->isActiveRoute('/profile') ? 'active' : ''; ?>">
                        <a href="/profile" title="Profile" class="profile-link">
                            <img src="<?php echo $this->auth->user()['profile_picture'] ?: '/assets/images/default-avatar.png'; ?>" 
                                 alt="Profile" class="nav-profile-pic">
                            <span class="nav-text">Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="nav-mobile">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div class="mobile-menu" id="mobileMenu">
                    <ul>
                        <li><a href="/feed"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="/explore"><i class="fas fa-compass"></i> Explore</a></li>
                        <li><a href="/notifications"><i class="fas fa-bell"></i> Notifications</a></li>
                        <li><a href="/messages"><i class="fas fa-envelope"></i> Messages</a></li>
                        <li><a href="/profile"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="/settings"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php echo $content; ?>
    </main>

    <?php if (!isset($hide_footer)): ?>
    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links">
                <a href="/about">About</a>
                <a href="/terms">Terms</a>
                <a href="/privacy">Privacy</a>
                <a href="/help">Help</a>
                <a href="/contact">Contact</a>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> ArtSpace. All rights reserved.
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Create Post Button (for logged-in users) -->
    <?php if ($this->auth->check() && !isset($hide_create_btn)): ?>
    <a href="/create-post" class="create-post-btn" title="Create Post">
        <i class="fas fa-plus"></i>
    </a>
    <?php endif; ?>

    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Report Modal -->
    <div class="modal" id="reportModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Content</h3>
                <button class="close-modal" id="closeReportModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <input type="hidden" id="reportType" name="report_type">
                    <input type="hidden" id="contentId" name="content_id">
                    <div class="form-group">
                        <label for="reportReason">Reason for reporting</label>
                        <select id="reportReason" name="reason" required>
                            <option value="">Select a reason</option>
                            <option value="inappropriate">Inappropriate Content</option>
                            <option value="spam">Spam or Misleading</option>
                            <option value="harassment">Harassment or Bullying</option>
                            <option value="copyright">Copyright Infringement</option>
                            <option value="ai_disclosure">AI Usage Not Disclosed</option>
                            <option value="impersonation">Impersonation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reportDescription">Additional details (optional)</label>
                        <textarea id="reportDescription" name="description" rows="4"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="cancelReport" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <?php if (isset($page_js)): ?>
    <script src="/assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>

    <?php if ($this->auth->check() && isset($this->webSocket)): ?>
    <!-- WebSocket Script -->
    <?php echo $this->webSocket->getClientScript($this->auth->id()); ?>
    <?php endif; ?>
</body>
</html>