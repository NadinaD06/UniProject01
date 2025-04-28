<?php
/**
 * Header include file
 * Contains the main navigation and header elements
 */

// Get the current page URI to set active nav items
$current_uri = $_SERVER['REQUEST_URI'];
$current_page = explode('?', $current_uri)[0];
$current_page = rtrim($current_page, '/');

// Helper function to determine if a nav item is active
function isActive($page) {
    global $current_page;
    
    if ($page === '/' && $current_page === '') {
        return true;
    }
    
    return $current_page === $page;
}

// Get unread notifications count
$unread_notifications = 0;
$unread_messages = 0;

if (isset($_SESSION['user_id'])) {
    // This would normally query the database to get these counts
    // For now, we'll just use placeholders
    $unread_notifications = 0;
    $unread_messages = 0;
}
?>

<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="/">
                <h1>ArtSpace</h1>
            </a>
        </div>
        <nav class="main-nav">
            <ul>
                <li <?php echo isActive('/feed') ? 'class="active"' : ''; ?>>
                    <a href="/feed"><i class="fas fa-home"></i> Home</a>
                </li>
                <li <?php echo isActive('/explore') ? 'class="active"' : ''; ?>>
                    <a href="/explore"><i class="fas fa-compass"></i> Explore</a>
                </li>
                <li <?php echo isActive('/notifications') ? 'class="active"' : ''; ?>>
                    <a href="/notifications">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li <?php echo isActive('/messages') ? 'class="active"' : ''; ?>>
                    <a href="/messages">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($unread_messages > 0): ?>
                        <span class="notification-badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li <?php echo isActive('/profile') ? 'class="active"' : ''; ?>>
                    <a href="/profile"><i class="fas fa-user"></i> Profile</a>
                </li>
            </ul>
        </nav>
        <div class="search-box">
            <input type="text" id="globalSearch" placeholder="Search ArtSpace...">
            <button type="submit"><i class="fas fa-search"></i></button>
            <div id="searchResults" class="search-results-dropdown" style="display: none;"></div>
        </div>
        <div class="user-menu">
            <div class="user-menu-trigger">
                <img src="/api/placeholder/32/32" alt="User Profile" id="headerUserAvatar">
                <i class="fas fa-caret-down"></i>
            </div>
            <div class="user-menu-dropdown">
                <ul>
                    <li><a href="/profile"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="/settings"><i class="fas fa-cog"></i> Settings</a></li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li class="admin-menu-item"><a href="/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li class="divider"></li>
                    <li><a href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<script>
    // User menu dropdown toggle
    document.querySelector('.user-menu-trigger').addEventListener('click', function() {
        document.querySelector('.user-menu-dropdown').classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const userMenu = document.querySelector('.user-menu');
        if (!userMenu.contains(e.target)) {
            document.querySelector('.user-menu-dropdown').classList.remove('active');
        }
    });
    
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    
    globalSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        // In a real app, this would make an AJAX call to search the database
        // For now, we'll just show a loading indicator
        searchResults.innerHTML = '<div class="searching">Searching...</div>';
        searchResults.style.display = 'block';
        
        // Simulate AJAX call
        setTimeout(() => {
            // This would be replaced with actual search results
            searchResults.innerHTML = `
                <div class="search-category">
                    <h4>Users</h4>
                    <div class="search-item">
                        <img src="/api/placeholder/32/32" alt="User">
                        <div class="search-item-info">
                            <span class="search-item-name">ArtisticSoul</span>
                            <span class="search-item-meta">Digital Artist</span>
                        </div>
                    </div>
                </div>
                <div class="search-category">
                    <h4>Posts</h4>
                    <div class="search-item">
                        <img src="/api/placeholder/32/32" alt="Post">
                        <div class="search-item-info">
                            <span class="search-item-name">Sunset Dreams</span>
                            <span class="search-item-meta">by ColorMaster</span>
                        </div>
                    </div>
                </div>
                <div class="search-category">
                    <h4>Tags</h4>
                    <div class="search-tags">
                        <a href="/explore?tag=DigitalArt">#DigitalArt</a>
                        <a href="/explore?tag=Illustration">#Illustration</a>
                    </div>
                </div>
                <div class="view-all">
                    <a href="/search?q=${encodeURIComponent(query)}">View all results</a>
                </div>
            `;
        }, 500);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!globalSearch.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
</script>