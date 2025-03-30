<?php
/**
 * User Profile Page
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';
require_once '../controllers/profile_controller.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to view profiles';
    header('Location: login.php');
    exit;
}

// Get user ID from URL or use current user
$user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

// Get profile data
$profile_data = get_user_profile($user_id);

// Check if user exists
if (!$profile_data['success']) {
    $_SESSION['error_message'] = $profile_data['message'];
    header('Location: feed.php');
    exit;
}

$user = $profile_data['user'];
$is_owner = $user['is_current_user'];
$is_following = $user['is_following'];

// Get user artworks
$artworks_data = get_user_artworks($user_id);
$artworks = $artworks_data['success'] ? $artworks_data['artworks'] : [];

// Set page info
$page_title = "ArtSpace - " . $user['username'] . "'s Profile";
$page_css = "profile";
$page_js = "profile";
?>

<?php require_once '../includes/header.php'; ?>

<main class="profile-container">
    <section class="profile-header">
        <div class="profile-cover">
            <img src="<?php echo htmlspecialchars($user['cover_image']); ?>" alt="Cover Image" class="cover-image">
            <?php if ($is_owner): ?>
            <button class="edit-cover"><i class="fas fa-camera"></i> Change Cover</button>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
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
                        <span class="stat-count"><?php echo number_format($user['posts_count']); ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-count"><?php echo number_format($user['followers_count']); ?></span>
                        <span class="stat-label">Followers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-count"><?php echo number_format($user['following_count']); ?></span>
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
                            <a href="createPost.php" class="upload-btn"><i class="fas fa-upload"></i> Upload Your First Artwork</a>
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
            
            <?php if ($artworks_data['has_more']): ?>
            <div class="load-more">
                <button class="load-more-btn" data-user="<?php echo $user_id; ?>" data-offset="<?php echo count($artworks); ?>">Load More</button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="tab-content" id="collections" style="display: none;">
        <div class="no-content">
            <p>No collections yet.</p>
            <?php if ($is_owner): ?>
                <a href="collections.php" class="create-btn"><i class="fas fa-plus"></i> Create Your First Collection</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="tab-content" id="favorites" style="display: none;">
        <div class="no-content">
            <p>No favorites yet.</p>
            <?php if ($is_owner): ?>
                <a href="explore.php" class="explore-btn"><i class="fas fa-compass"></i> Explore Artworks</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="tab-content" id="about" style="display: none;">
        <div class="about-content">
            <h3>About <?php echo htmlspecialchars($user['username']); ?></h3>
            
            <?php if (!empty($user['bio'])): ?>
            <div class="about-section">
                <h4>Bio</h4>
                <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($user['website'])): ?>
            <div class="about-section">
                <h4>Website</h4>
                <p><a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($user['website']); ?></a></p>
            </div>
            <?php endif; ?>

            <div class="about-section">
                <h4>Member Since</h4>
                <p><?php echo htmlspecialchars($user['joined_at']); ?></p>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>