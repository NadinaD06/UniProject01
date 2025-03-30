<?php
/**
 * Friends Management Page
 * Manage connections, followers, requests and suggestions
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';
require_once '../controllers/users_controller.php';
require_once '../controllers/friends_controller.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to view your connections';
    header('Location: login.php');
    exit;
}

// Set page info
$page_title = "ArtSpace - Friends & Connections";
$page_css = "friends";
$page_js = "friends";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get connections data
$following = get_following($user_id);
$followers = get_followers($user_id);
$requests = get_follow_requests($user_id);
$suggestions = get_user_suggestions($user_id);
$blocked = get_blocked_users($user_id);

// Get counts
$following_count = count($following);
$followers_count = count($followers);
$requests_count = count($requests);
$blocked_count = count($blocked);

// Get active tab from URL or default to "following"
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'following';
?>

<?php require_once '../includes/header.php'; ?>

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
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </nav>
        <div class="search-box">
            <input type="text" placeholder="Search ArtSpace...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </div>
    </div>
</header>

<main class="friends-container">
    <div class="friends-sidebar">
        <h2>Connections</h2>
        <ul class="sidebar-menu">
            <li class="<?php echo $active_tab == 'following' ? 'active' : ''; ?>">
                <a href="?tab=following"><i class="fas fa-user-friends"></i> Following <span class="count"><?php echo $following_count; ?></span></a>
            </li>
            <li class="<?php echo $active_tab == 'followers' ? 'active' : ''; ?>">
                <a href="?tab=followers"><i class="fas fa-users"></i> Followers <span class="count"><?php echo $followers_count; ?></span></a>
            </li>
            <li class="<?php echo $active_tab == 'requests' ? 'active' : ''; ?>">
                <a href="?tab=requests"><i class="fas fa-user-plus"></i> Follow Requests <?php if ($requests_count > 0): ?><span class="count"><?php echo $requests_count; ?></span><?php endif; ?></a>
            </li>
            <li class="<?php echo $active_tab == 'suggestions' ? 'active' : ''; ?>">
                <a href="?tab=suggestions"><i class="fas fa-magic"></i> Suggested Artists</a>
            </li>
            <li class="<?php echo $active_tab == 'blocked' ? 'active' : ''; ?>">
                <a href="?tab=blocked"><i class="fas fa-ban"></i> Blocked Users <?php if ($blocked_count > 0): ?><span class="count"><?php echo $blocked_count; ?></span><?php endif; ?></a>
            </li>
        </ul>
        <div class="find-artists">
            <h3>Find Artists</h3>
            <div class="search-artists">
                <input type="text" id="artistSearch" placeholder="Search by name or username">
                <button><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-options">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="digital">Digital Art</option>
                    <option value="painting">Painting</option>
                    <option value="drawing">Drawing</option>
                    <option value="sculpture">Sculpture</option>
                    <option value="photography">Photography</option>
                    <option value="mixed_media">Mixed Media</option>
                    <option value="illustration">Illustration</option>
                    <option value="concept_art">Concept Art</option>
                    <option value="pixel_art">Pixel Art</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
    </div>

    <div class="friends-content">
        <!-- Following Tab -->
        <div class="tab-content <?php echo $active_tab == 'following' ? 'active' : ''; ?>" id="following">
            <div class="tab-header">
                <h2>People You Follow <span class="count">(<?php echo $following_count; ?>)</span></h2>
                <div class="tab-actions">
                    <div class="sort-by">
                        <label for="sortFollowing">Sort by:</label>
                        <select id="sortFollowing">
                            <option value="recent">Recently Added</option>
                            <option value="name">Name</option>
                            <option value="active">Recently Active</option>
                        </select>
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                    </div>
                </div>
            </div>
            
            <?php if ($following_count > 0): ?>
                <div class="user-grid">
                    <?php foreach ($following as $user): ?>
                        <div class="user-card">
                            <div class="user-card-header">
                                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/api/placeholder/80/80'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                                <button class="following-btn">Following <i class="fas fa-check"></i></button>
                            </div>
                            <div class="user-info">
                                <h3 class="user-name"><a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></a></h3>
                                <p class="user-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio available'; ?></p>
                            </div>
                            <div class="mutual-info">
                                <span><?php echo $user['mutual_count']; ?> mutual followers</span>
                            </div>
                            <div class="user-actions">
                                <button class="message-btn" data-username="<?php echo htmlspecialchars($user['username']); ?>"><i class="fas fa-envelope"></i> Message</button>
                                <div class="dropdown">
                                    <button class="more-btn"><i class="fas fa-ellipsis-h"></i></button>
                                    <div class="dropdown-content">
                                        <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>">View Profile</a>
                                        <a href="#" class="add-to-list" data-username="<?php echo htmlspecialchars($user['username']); ?>">Add to List</a>
                                        <a href="#" class="unfollow-user" data-user-id="<?php echo $user['id']; ?>">Unfollow</a>
                                        <a href="#" class="mute-user" data-user-id="<?php echo $user['id']; ?>">Mute</a>
                                        <a href="#" class="block-user" data-user-id="<?php echo $user['id']; ?>">Block</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($following_count > 20): ?>
                    <div class="load-more">
                        <button class="load-more-btn" data-page="2" data-type="following">Load More</button>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends empty-icon"></i>
                    <h3>You're not following anyone yet</h3>
                    <p>When you follow someone, they'll appear here. Find artists to follow in our suggestions.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Followers Tab -->
        <div class="tab-content <?php echo $active_tab == 'followers' ? 'active' : ''; ?>" id="followers">
            <div class="tab-header">
                <h2>Your Followers <span class="count">(<?php echo $followers_count; ?>)</span></h2>
                <div class="tab-actions">
                    <div class="sort-by">
                        <label for="sortFollowers">Sort by:</label>
                        <select id="sortFollowers">
                            <option value="recent">Recently Added</option>
                            <option value="name">Name</option>
                            <option value="active">Recently Active</option>
                        </select>
                    </div>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                    </div>
                </div>
            </div>
            
            <?php if ($followers_count > 0): ?>
                <div class="user-grid">
                    <?php foreach ($followers as $user): ?>
                        <div class="user-card">
                            <div class="user-card-header">
                                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/api/placeholder/80/80'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                                
                                <?php if ($user['is_following']): ?>
                                    <button class="following-btn">Following <i class="fas fa-check"></i></button>
                                <?php else: ?>
                                    <button class="follow-btn" data-user-id="<?php echo $user['id']; ?>">Follow <i class="fas fa-user-plus"></i></button>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <h3 class="user-name"><a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></a></h3>
                                <p class="user-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio available'; ?></p>
                            </div>
                            <div class="mutual-info">
                                <span><?php echo $user['mutual_count']; ?> mutual followers</span>
                            </div>
                            <div class="user-actions">
                                <button class="message-btn" data-username="<?php echo htmlspecialchars($user['username']); ?>"><i class="fas fa-envelope"></i> Message</button>
                                <div class="dropdown">
                                    <button class="more-btn"><i class="fas fa-ellipsis-h"></i></button>
                                    <div class="dropdown-content">
                                        <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>">View Profile</a>
                                        <a href="#" class="add-to-list" data-username="<?php echo htmlspecialchars($user['username']); ?>">Add to List</a>
                                        <a href="#" class="remove-follower" data-user-id="<?php echo $user['id']; ?>">Remove Follower</a>
                                        <a href="#" class="mute-user" data-user-id="<?php echo $user['id']; ?>">Mute</a>
                                        <a href="#" class="block-user" data-user-id="<?php echo $user['id']; ?>">Block</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($followers_count > 20): ?>
                    <div class="load-more">
                        <button class="load-more-btn" data-page="2" data-type="followers">Load More</button>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users empty-icon"></i>
                    <h3>You don't have any followers yet</h3>
                    <p>When someone follows you, they'll appear here. Share your artwork to attract followers!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Requests Tab -->
        <div class="tab-content <?php echo $active_tab == 'requests' ? 'active' : ''; ?>" id="requests">
            <div class="tab-header">
                <h2>Follow Requests <span class="count">(<?php echo $requests_count; ?>)</span></h2>
                <p class="tab-description">People who requested to follow you. Your account is partially private.</p>
            </div>
            
            <?php if ($requests_count > 0): ?>
                <div class="requests-list">
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card" data-request-id="<?php echo $request['id']; ?>">
                            <div class="request-user">
                                <img src="<?php echo !empty($request['profile_picture']) ? htmlspecialchars($request['profile_picture']) : '/api/placeholder/60/60'; ?>" alt="<?php echo htmlspecialchars($request['username']); ?>" class="request-avatar">
                                <div class="request-user-info">
                                    <h3 class="user-name"><a href="profile.php?username=<?php echo htmlspecialchars($request['username']); ?>"><?php echo htmlspecialchars($request['username']); ?></a></h3>
                                    <p class="user-bio"><?php echo !empty($request['bio']) ? htmlspecialchars($request['bio']) : 'No bio available'; ?></p>
                                    <span class="request-time">Requested <?php echo format_time_ago($request['created_at']); ?></span>
                                </div>
                            </div>
                            <div class="mutual-info">
                                <span><i class="fas fa-user-friends"></i> <?php echo $request['mutual_count'] > 0 ? $request['mutual_count'] . ' mutual connections' : 'No mutual connections'; ?></span>
                            </div>
                            <div class="request-actions">
                                <button class="accept-btn" data-request-id="<?php echo $request['id']; ?>" data-user-id="<?php echo $request['user_id']; ?>">Accept</button>
                                <button class="decline-btn" data-request-id="<?php echo $request['id']; ?>" data-user-id="<?php echo $request['user_id']; ?>">Decline</button>
                                <button class="view-profile-btn" data-username="<?php echo htmlspecialchars($request['username']); ?>">View Profile</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-check empty-icon"></i>
                    <h3>No Pending Requests</h3>
                    <p>You have no pending follow requests at this time.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Suggestions Tab -->
        <div class="tab-content <?php echo $active_tab == 'suggestions' ? 'active' : ''; ?>" id="suggestions">
            <div class="tab-header">
                <h2>Suggested Artists</h2>
                <p class="tab-description">Artists you might be interested in based on your activity and interests</p>
            </div>
            
            <?php if (!empty($suggestions)): ?>
                <div class="suggestions-grid">
                    <?php foreach ($suggestions as $user): ?>
                        <div class="user-card">
                            <div class="user-card-header">
                                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/api/placeholder/80/80'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                                <button class="follow-btn" data-user-id="<?php echo $user['id']; ?>">Follow <i class="fas fa-user-plus"></i></button>
                            </div>
                            <div class="user-info">
                                <h3 class="user-name"><a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></a></h3>
                                <p class="user-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio available'; ?></p>
                            </div>
                            <div class="mutual-info">
                                <span><?php echo $user['mutual_count']; ?> mutual followers</span>
                            </div>
                            <div class="user-actions">
                                <button class="message-btn" data-username="<?php echo htmlspecialchars($user['username']); ?>"><i class="fas fa-envelope"></i> Message</button>
                                <div class="dropdown">
                                    <button class="more-btn"><i class="fas fa-ellipsis-h"></i></button>
                                    <div class="dropdown-content">
                                        <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>">View Profile</a>
                                        <a href="#" class="add-to-list" data-username="<?php echo htmlspecialchars($user['username']); ?>">Add to List</a>
                                        <a href="#" class="hide-suggestion" data-user-id="<?php echo $user['id']; ?>">Not Interested</a>
                                        <a href="#" class="mute-user" data-user-id="<?php echo $user['id']; ?>">Mute</a>
                                        <a href="#" class="block-user" data-user-id="<?php echo $user['id']; ?>">Block</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="load-more">
                    <button class="load-more-btn" data-page="2" data-type="suggestions">Show More Suggestions</button>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-magic empty-icon"></i>
                    <h3>We're finding suggestions for you</h3>
                    <p>We'll show artist suggestions based on your activity and interests. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Blocked Users Tab -->
        <div class="tab-content <?php echo $active_tab == 'blocked' ? 'active' : ''; ?>" id="blocked">
            <div class="tab-header">
                <h2>Blocked Users</h2>
                <p class="tab-description">Blocked users cannot see your profile, posts, or interact with you.</p>
            </div>
            
            <?php if ($blocked_count > 0): ?>
                <div class="blocked-list">
                    <?php foreach ($blocked as $user): ?>
                        <div class="request-card">
                            <div class="request-user">
                                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/api/placeholder/60/60'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="request-avatar">
                                <div class="request-user-info">
                                    <h3 class="user-name"><a href="javascript:void(0)"><?php echo htmlspecialchars($user['username']); ?></a></h3>
                                    <p class="user-bio"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio available'; ?></p>
                                    <span class="request-time">Blocked on <?php echo date('M j, Y', strtotime($user['blocked_at'])); ?></span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="unblock-btn" data-user-id="<?php echo $user['id']; ?>">Unblock</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" id="noBlockedUsers">
                    <i class="fas fa-ban empty-icon"></i>
                    <h3>No Blocked Users</h3>
                    <p>You haven't blocked any users yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add to List Modal -->
<div class="modal" id="addToListModal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add to List</h2>
        <div class="list-selection">
            <div class="existing-lists">
                <h3>Your Lists</h3>
                <ul class="lists" id="existingLists">
                    <li>
                        <input type="checkbox" id="list1" name="list1">
                        <label for="list1">Favorite Artists</label>
                    </li>
                    <li>
                        <input type="checkbox" id="list2" name="list2">
                        <label for="list2">Digital Art Inspiration</label>
                    </li>
                    <li>
                        <input type="checkbox" id="list3" name="list3">
                        <label for="list3">Character Designers</label>
                    </li>
                </ul>
            </div>
            <div class="create-list">
                <h3>Create New List</h3>
                <div class="form-group">
                    <input type="text" id="newListName" placeholder="List name">
                </div>
                <div class="form-group">
                    <select id="listPrivacy">
                        <option value="private">Private (Only you)</option>
                        <option value="public">Public (Anyone)</option>
                    </select>
                </div>
                <button class="create-list-btn">Create</button>
            </div>
        </div>
        <div class="modal-actions">
            <button class="cancel-btn">Cancel</button>
            <button class="save-btn">Save</button>
        </div>
    </div>
</div>

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
            <p>&copy; <?php echo date('Y'); ?> ArtSpace. All rights reserved.</p>
        </div>
    </div>
</footer>

<div class="toast-container"></div>

<!-- Add JavaScript -->
<script src="../assets/js/friends.js"></script>
<?php require_once '../includes/footer.php'; ?>