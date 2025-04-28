<?php
/**
 * Feed view - Main content feed
 */
$page_title = 'Feed - ArtSpace';
$page_description = 'Your personalized feed of artwork and creative content';
$body_class = 'feed-page';
$page_js = 'feed';
?>

<div class="main-container">
    <div class="feed-container">
        <!-- Feed Header with Filters -->
        <div class="feed-header">
            <div class="feed-filters">
                <a href="/feed?filter=following" class="filter-btn <?php echo ($filter === 'following') ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i> Following
                </a>
                <a href="/feed?filter=trending" class="filter-btn <?php echo ($filter === 'trending') ? 'active' : ''; ?>">
                    <i class="fas fa-fire"></i> Trending
                </a>
                <a href="/feed?filter=latest" class="filter-btn <?php echo ($filter === 'latest') ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Latest
                </a>
            </div>
            
            <div class="feed-categories">
                <select id="categorySelect" onchange="window.location.href='/feed?filter=<?php echo $filter; ?>&category=' + this.value">
                    <option value="">All Categories</option>
                    <?php foreach ($categories ?? [] as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['slug']); ?>" 
                        <?php echo ($category === $cat['slug']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Posts Container -->
        <div class="posts-container" id="postsContainer">
            <?php if (empty($posts)): ?>
            <div class="no-posts">
                <?php if ($filter === 'following'): ?>
                <div class="message">
                    <i class="fas fa-users"></i>
                    <h3>No posts in your feed yet</h3>
                    <p>Follow artists to see their work in your feed</p>
                    <a href="/explore" class="btn btn-primary">Explore Artists</a>
                </div>
                <?php else: ?>
                <div class="message">
                    <i class="fas fa-image"></i>
                    <h3>No posts to display</h3>
                    <p>Be the first to share your artwork</p>
                    <a href="/create-post" class="btn btn-primary">Create Post</a>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                    <!-- Post Header -->
                    <div class="post-header">
                        <div class="post-user">
                            <a href="/profile/<?php echo htmlspecialchars($post['author_username']); ?>">
                                <img src="<?php echo $post['author_profile_pic'] ?: '/assets/images/default-avatar.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($post['author_username']); ?>" class="post-avatar">
                            </a>
                            <div class="post-user-info">
                                <a href="/profile/<?php echo htmlspecialchars($post['author_username']); ?>" class="post-username">
                                    <?php echo htmlspecialchars($post['author_username']); ?>
                                </a>
                                <span class="post-time"><?php echo $this->formatTimeAgo($post['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <button class="post-action-btn" aria-label="More options" 
                                    onclick="togglePostActions(this, <?php echo $post['id']; ?>)">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="post-action-dropdown">
                                <?php if ($post['user_id'] === $this->auth->id()): ?>
                                <a href="/post/<?php echo $post['id']; ?>/edit">
                                    <i class="fas fa-edit"></i> Edit Post
                                </a>
                                <a href="#" onclick="confirmDeletePost(<?php echo $post['id']; ?>); return false;">
                                    <i class="fas fa-trash"></i> Delete Post
                                </a>
                                <?php else: ?>
                                <a href="#" onclick="reportPost(<?php echo $post['id']; ?>); return false;">
                                    <i class="fas fa-flag"></i> Report Post
                                </a>
                                <?php endif; ?>
                                <a href="/post/<?php echo $post['id']; ?>">
                                    <i class="fas fa-external-link-alt"></i> View Post
                                </a>
                                <a href="#" onclick="copyPostLink(<?php echo $post['id']; ?>); return false;">
                                    <i class="fas fa-link"></i> Copy Link
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php if (!empty($post['title'])): ?>
                        <h3 class="post-title">
                            <a href="/post/<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h3>
                        <?php endif; ?>
                        
                        <?php if (!empty($post['description'])): ?>
                        <div class="post-description">
                            <?php 
                            $description = htmlspecialchars($post['description']);
                            if (strlen($description) > 200) {
                                echo substr($description, 0, 200) . '... <a href="/post/' . $post['id'] . '" class="read-more">Read more</a>';
                            } else {
                                echo $description;
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-image">
                            <a href="/post/<?php echo $post['id']; ?>">
                                <img src="<?php echo $post['image_url']; ?>" alt="<?php echo htmlspecialchars($post['title'] ?? 'Post image'); ?>"
                                     loading="lazy" onclick="viewPost(<?php echo $post['id']; ?>)">
                            </a>
                            <?php if (!empty($post['used_ai'])): ?>
                            <div class="ai-badge" title="Created with AI">AI</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($post['tags'])): ?>
                        <div class="post-tags">
                            <?php 
                            $tags = explode(',', $post['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                            <a href="/explore?tag=<?php echo urlencode($tag); ?>" class="tag">
                                #<?php echo htmlspecialchars($tag); ?>
                            </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Post Footer -->
                    <div class="post-footer">
                        <div class="post-stats">
                            <div class="post-likes">
                                <button class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(this, <?php echo $post['id']; ?>)">
                                    <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span class="likes-count"><?php echo $post['likes_count']; ?></span>
                                </button>
                            </div>
                            <div class="post-comments">
                                <a href="/post/<?php echo $post['id']; ?>#comments">
                                    <i class="far fa-comment"></i>
                                    <span class="comments-count"><?php echo $post['comments_count']; ?></span>
                                </a>
                            </div>
                            <div class="post-save">
                                <button class="save-btn <?php echo $post['user_saved'] ? 'saved' : ''; ?>" 
                                        onclick="toggleSave(this, <?php echo $post['id']; ?>)">
                                    <i class="<?php echo $post['user_saved'] ? 'fas' : 'far'; ?> fa-bookmark"></i>
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($post['comments_count'] > 0 && !empty($post['comments'])): ?>
                        <div class="post-comments-preview">
                            <?php foreach ($post['comments'] as $comment): ?>
                            <div class="comment-preview">
                                <a href="/profile/<?php echo $comment['user']['username']; ?>" class="comment-user">
                                    <img src="<?php echo $comment['user']['profile_picture']; ?>" 
                                         alt="<?php echo htmlspecialchars($comment['user']['username']); ?>" class="comment-avatar">
                                </a>
                                <div class="comment-content">
                                    <a href="/profile/<?php echo htmlspecialchars($comment['user']['username']); ?>" class="comment-username">
                                        <?php echo htmlspecialchars($comment['user']['username']); ?>
                                    </a>
                                    <?php 
                                    $commentText = htmlspecialchars($comment['content']);
                                    if (strlen($commentText) > 100) {
                                        echo substr($commentText, 0, 100) . '...';
                                    } else {
                                        echo $commentText;
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if ($post['comments_count'] > count($post['comments'])): ?>
                            <a href="/post/<?php echo $post['id']; ?>#comments" class="view-more-comments">
                                View all <?php echo $post['comments_count']; ?> comments
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-comment-form">
                            <img src="<?php echo $this->auth->user()['profile_picture'] ?: '/assets/images/default-avatar.png'; ?>" 
                                 alt="Your profile" class="comment-avatar">
                            <input type="text" placeholder="Add a comment..." 
                                   onkeydown="if(event.key==='Enter')submitComment(this, <?php echo $post['id']; ?>)">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Load More Button -->
                <div class="load-more-container">
                    <button id="loadMoreBtn" class="load-more-btn" onclick="loadMorePosts()">
                        Load More
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Profile Card -->
        <div class="sidebar-card profile-card">
            <?php $user = $this->auth->user(); ?>
            <div class="profile-header">
                <img src="<?php echo $user['profile_picture'] ?: '/assets/images/default-avatar.png'; ?>" 
                     alt="<?php echo htmlspecialchars($user['username']); ?>" class="profile-avatar">
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></p>
                </div>
            </div>
            <div class="profile-stats">
                <div class="stat">
                    <span class="stat-value"><?php echo $user['stats']['posts_count']; ?></span>
                    <span class="stat-label">Posts</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?php echo $user['stats']['followers_count']; ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?php echo $user['stats']['following_count']; ?></span>
                    <span class="stat-label">Following</span>
                </div>
            </div>
            <div class="profile-actions">
                <a href="/profile" class="btn btn-secondary btn-sm">View Profile</a>
                <a href="/create-post" class="btn btn-primary btn-sm">Create Post</a>
            </div>
        </div>
        
        <!-- Suggested Users Card -->
        <?php if (!empty($suggested_users)): ?>
        <div class="sidebar-card suggested-users-card">
            <h3 class="card-title">Suggested Artists</h3>
            <div class="suggested-users">
                <?php foreach ($suggested_users as $suggested): ?>
                <div class="suggested-user">
                    <a href="/profile/<?php echo htmlspecialchars($suggested['username']); ?>" class="user-avatar">
                        <img src="<?php echo $suggested['profile_picture'] ?: '/assets/images/default-avatar.png'; ?>" 
                             alt="<?php echo htmlspecialchars($suggested['username']); ?>">
                    </a>
                    <div class="user-info">
                        <a href="/profile/<?php echo htmlspecialchars($suggested['username']); ?>" class="user-name">
                            <?php echo htmlspecialchars($suggested['username']); ?>
                        </a>
                        <span class="user-meta"><?php echo $suggested['follower_count']; ?> followers</span>
                    </div>
                    <button class="follow-btn" onclick="followUser(this, <?php echo $suggested['id']; ?>)">
                        Follow
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="/explore" class="see-more-link">See More</a>
        </div>
        <?php endif; ?>
        
        <!-- Trending Tags Card -->
        <?php if (!empty($trending_tags)): ?>
        <div class="sidebar-card trending-tags-card">
            <h3 class="card-title">Trending Tags</h3>
            <div class="trending-tags">
                <?php foreach ($trending_tags as $tag): ?>
                <a href="/explore?tag=<?php echo urlencode($tag['name']); ?>" class="trending-tag">
                    <span class="tag-name">#<?php echo htmlspecialchars($tag['name']); ?></span>
                    <span class="tag-count"><?php echo $tag['count']; ?> posts</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer Links -->
        <div class="sidebar-footer">
            <div class="footer-links">
                <a href="/about">About</a>
                <a href="/terms">Terms</a>
                <a href="/privacy">Privacy</a>
                <a href="/help">Help</a>
                <a href="/contact">Contact</a>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> ArtSpace
            </div>
        </div>
    </div>
</div>

<!-- Hidden fields for JavaScript -->
<input type="hidden" id="currentFilter" value="<?php echo htmlspecialchars($filter); ?>">
<input type="hidden" id="currentCategory" value="<?php echo htmlspecialchars($category ?? ''); ?>">
<input type="hidden" id="currentOffset" value="<?php echo count($posts); ?>">

<script>
    // This JavaScript will be enhanced by the feed.js file included via the layout
    
    // Helper function to format timestamp as "time ago"
    function formatTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffSeconds = Math.floor((now - date) / 1000);
        
        if (diffSeconds < 60) {
            return 'just now';
        } else if (diffSeconds < 3600) {
            const minutes = Math.floor(diffSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffSeconds < 86400) {
            const hours = Math.floor(diffSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else if (diffSeconds < 604800) {
            const days = Math.floor(diffSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString();
        }
    }
</script>