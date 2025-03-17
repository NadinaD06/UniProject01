<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSpace - <?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="feed.php">
                    <h1>ArtSpace</h1>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="feed.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="explore.php"><i class="fas fa-compass"></i> Explore</a></li>
                    <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                    <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                </ul>
            </nav>
            <div class="search-box">
                <input type="text" placeholder="Search ArtSpace...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </header>

    <main class="profile-container">
        <section class="profile-header">
            <div class="profile-cover">
                <img src="<?php echo !empty($user['cover_image']) ? htmlspecialchars($user['cover_image']) : '../assets/images/cover-placeholder.jpg'; ?>" alt="Cover Image" class="cover-image">
                <?php if ($is_owner): ?>
                <button class="edit-cover"><i class="fas fa-camera"></i> Change Cover</button>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-picture">
                    <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../assets/images/profile-placeholder.jpg'; ?>" alt="Profile Picture">
                    <?php if ($is_owner): ?>
                    <button class="edit-profile-pic"><i class="fas fa-camera"></i></button>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h2 class="username">@<?php echo htmlspecialchars($user['username']); ?></h2>
                    <h3 class="full-name"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="bio"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet.'); ?></p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-count"><?php echo number_format($art_count); ?></span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count"><?php echo number_format($followers_count); ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count"><?php echo number_format($following_count); ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <?php if ($is_owner): ?>
                            <button class="edit-profile-btn"><i class="fas fa-edit"></i> Edit Profile</button>
                            <button class="settings-btn"><i class="fas fa-cog"></i> Settings</button>
                        <?php else: ?>
                            <button class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" data-user-id="<?php echo $user_id; ?>">
                                <?php echo $is_following ? '<i class="fas fa-user-check"></i> Following' : '<i class="fas fa-user-plus"></i> Follow'; ?>
                            </button>
                            <button class="message-btn"><i class="fas fa-envelope"></i> Message</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-tabs">
            <div class="tabs">
                <button class="tab-btn active" data-tab="artworks">Artworks</button>
                <button class="tab-btn" data-tab="collections">Collections</button>
                <button class="tab-btn" data-tab="favorites">Favorites</button>
                <button class="tab-btn" data-tab="about">About</button>
            </div>
        </section>

        <section class="tab-content" id="artworks">
            <div class="art-gallery">
                <div class="gallery-filters">
                    <select name="sort" id="sort">
                        <option value="recent">Most Recent</option>
                        <option value="popular">Most Popular</option>
                        <option value="oldest">Oldest</option>
                    </select>
                    <div class="gallery-view">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                    </div>
                </div>
                
                <div class="gallery-grid">
                    <?php if (empty($artworks)): ?>
                        <div class="no-content">
                            <p>No artworks posted yet.</p>
                            <?php if ($is_owner): ?>
                                <a href="upload.php" class="upload-btn"><i class="fas fa-upload"></i> Upload Your First Artwork</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($artworks as $artwork): ?>
                        <div class="art-item">
                            <a href="artwork.php?id=<?php echo $artwork['id']; ?>">
                                <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                                <div class="art-item-overlay">
                                    <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                                    <div class="art-item-stats">
                                        <span><i class="fas fa-heart"></i> <?php echo $artwork['likes_count']; ?></span>
                                        <span><i class="fas fa-comment"></i> <?php echo $artwork['comments_count']; ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (count($artworks) >= 6): ?>
                <div class="load-more">
                    <button class="load-more-btn" data-user="<?php echo $user_id; ?>" data-offset="6">Load More</button>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-links">
                <a href="about.php">About</a>
                <a href="terms.php">Terms of Service</a>
                <a href="privacy.php">Privacy Policy</a>
                <a href="help.php">Help Center</a>
                <a href="contact.php">Contact Us</a>
            </div>
            <div class="copyright">
                <p>&copy; 2025 ArtSpace. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/profile.js"></script>
</body>
</html>