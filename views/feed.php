<?php
/**
 * Feed Page
 * Shows posts from followed users and trending content
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to view your feed';
    header('Location: login.php');
    exit;
}

// Set page info
$page_title = "ArtSpace - Your Feed";
$page_css = "feed";
$page_js = "feed";

// Get user ID
$user_id = $_SESSION['user_id'];
?>

<?php require_once '../includes/header.php'; ?>

<div class="main-container">
    <div class="side-nav">
        <ul class="side-nav-menu">
            <li class="side-nav-item">
                <a href="feed.php" class="side-nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="explore.php" class="side-nav-link">
                    <i class="fas fa-compass"></i>
                    <span>Explore</span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="notifications.php" class="side-nav-link">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="messages.php" class="side-nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="profile.php" class="side-nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="left-column">
        <div class="feed-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="following">Following</button>
            <button class="filter-btn" data-filter="trending">Trending</button>
            
            <div class="category-filters">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="digital-art">Digital Art</option>
                    <option value="traditional">Traditional</option>
                    <option value="photography">Photography</option>
                    <option value="3d-art">3D Art</option>
                    <option value="illustration">Illustration</option>
                    <option value="animation">Animation</option>
                    <option value="concept-art">Concept Art</option>
                    <option value="character-design">Character Design</option>
                </select>
            </div>
        </div>

        <div class="stories-section">
            <h3>Stories</h3>
            <div class="stories-container" id="storiesContainer">
                <!-- Story items will be loaded dynamically -->
                <div class="story your-story">
                    <div class="story-avatar">
                        <img src="/api/placeholder/60/60" alt="Your story" id="yourStoryAvatar">
                        <div class="add-story">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                    <span>Your Story</span>
                </div>
                
                <!-- Story skeleton loaders -->
                <?php for ($i = 0; $i < 5; $i++): ?>
                <div class="story skeleton">
                    <div class="story-avatar skeleton-circle"></div>
                    <span class="skeleton-text"></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="feed-posts" id="postsContainer">
            <!-- Posts will be loaded dynamically -->
            
            <!-- Post skeleton loaders -->
            <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="post-card skeleton">
                <div class="post-header">
                    <div class="post-user">
                        <div class="skeleton-circle post-user-img"></div>
                        <div class="post-user-info">
                            <div class="skeleton-text"></div>
                            <div class="skeleton-text"></div>
                        </div>
                    </div>
                </div>
                <div class="skeleton-image post-image-container"></div>
                <div class="post-content">
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="load-more">
            <button id="loadMoreBtn" disabled>Loading...</button>
        </div>
    </div>

    <div class="right-column">
        <div class="user-card">
            <div class="user-info">
                <img src="/api/placeholder/60/60" alt="Your Profile" id="sidebarUserAvatar">
                <div>
                    <h3 id="sidebarUsername"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                    <p id="sidebarUserBio">Your artistic journey</p>
                </div>
            </div>
        </div>

        <div class="suggestions-section">
            <div class="suggestions-header">
                <h3>Suggested Artists</h3>
                <a href="explore.php" class="see-all">See All</a>
            </div>
            <div class="artist-suggestions" id="artistSuggestions">
                <!-- Artist suggestions will be loaded dynamically -->
                
                <!-- Suggestion skeleton loaders -->
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="artist-card skeleton">
                    <div class="skeleton-circle artist-img"></div>
                    <div class="artist-info">
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text"></div>
                    </div>
                    <div class="skeleton-button"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="trending-tags">
            <h3>Trending Tags</h3>
            <div class="tags-list">
                <a href="#" class="tag">#DigitalArt</a>
                <a href="#" class="tag">#Illustration</a>
                <a href="#" class="tag">#ConceptArt</a>
                <a href="#" class="tag">#CharacterDesign</a>
                <a href="#" class="tag">#Animation</a>
                <a href="#" class="tag">#Watercolor</a>
                <a href="#" class="tag">#StreetArt</a>
            </div>
        </div>
    </div>
</div>

<a href="createPost.php" class="create-post-btn">
    <i class="fas fa-plus"></i>
</a>

<div id="toast-container"></div>

<?php require_once '../includes/footer.php'; ?>