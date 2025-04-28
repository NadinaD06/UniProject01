// State management
let currentPage = 1;
let isLoading = false;
let hasMorePosts = true;
let currentFilter = 'all';
let currentCategory = '';

// DOM Elements
const postsContainer = document.getElementById('postsContainer');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const logoutBtn = document.getElementById('logoutBtn');
const storiesContainer = document.getElementById('storiesContainer');
const artistSuggestions = document.getElementById('artistSuggestions');
const filterButtons = document.querySelectorAll('.filter-btn');
const categoryFilter = document.getElementById('categoryFilter');

// Initialize the feed
document.addEventListener('DOMContentLoaded', () => {
    loadUserInfo();
    loadStories();
    loadSuggestedArtists();
    loadPosts();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Load more posts when button is clicked
    loadMoreBtn.addEventListener('click', loadPosts);
    
    // Search functionality
    searchInput.addEventListener('input', debounce(handleSearch, 500));
    
    // Logout functionality
    logoutBtn.addEventListener('click', handleLogout);

    // Filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentFilter = button.getAttribute('data-filter');
            resetFeed();
            loadPosts();
        });
    });

    // Category filter
    categoryFilter.addEventListener('change', () => {
        currentCategory = categoryFilter.value;
        resetFeed();
        loadPosts();
    });

    // Infinite scroll
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 500) {
            if (!isLoading && hasMorePosts) {
                loadPosts();
            }
        }
    });
}

// Load user information
async function loadUserInfo() {
    try {
        const response = await fetch('../controllers/profile/profile_process.php');
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                const userInfo = data.user;
                
                // Update user avatar and info
                const userAvatar = document.getElementById('userAvatar');
                const sidebarUserAvatar = document.getElementById('sidebarUserAvatar');
                const sidebarUsername = document.getElementById('sidebarUsername');
                const sidebarUserBio = document.getElementById('sidebarUserBio');
                
                if (userAvatar) userAvatar.src = userInfo.avatar || '/api/placeholder/32/32';
                if (sidebarUserAvatar) sidebarUserAvatar.src = userInfo.avatar || '/api/placeholder/60/60';
                if (sidebarUsername) sidebarUsername.textContent = userInfo.username;
                if (sidebarUserBio) sidebarUserBio.textContent = userInfo.bio || 'Your artistic journey';
            }
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Load stories into the stories container
async function loadStories() {
    try {
        const response = await fetch('../controllers/feed/feed_process.php?action=get_stories');
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success && storiesContainer) {
                // Clear existing stories
                storiesContainer.innerHTML = '';
                
                // Add "Your Story" option first
                const yourStoryElement = document.createElement('div');
                yourStoryElement.className = 'story your-story';
                yourStoryElement.innerHTML = `
                    <div class="story-avatar">
                        <img src="/api/placeholder/60/60" alt="Your story" id="yourStoryAvatar">
                        <div class="add-story">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                    <span>Your Story</span>
                `;
                yourStoryElement.addEventListener('click', () => {
                    window.location.href = 'create_story.html';
                });
                storiesContainer.appendChild(yourStoryElement);
                
                // Add stories from other users
                data.data.forEach(user => {
                    const storyElement = document.createElement('div');
                    storyElement.className = 'story';
                    storyElement.setAttribute('data-user-id', user.user_id);
                    storyElement.innerHTML = `
                        <div class="story-avatar has-story">
                            <img src="/api/placeholder/60/60" alt="${user.username}'s story">
                        </div>
                        <span>${user.username}</span>
                    `;
                    storyElement.addEventListener('click', () => viewStory(user.user_id));
                    storiesContainer.appendChild(storyElement);
                });
            }
        }
    } catch (error) {
        console.error('Error loading stories:', error);
    }
}

// Load suggested artists
async function loadSuggestedArtists() {
    try {
        const response = await fetch('../controllers/feed/feed_process.php?action=get_suggestions');
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success && artistSuggestions) {
                // Clear existing suggestions
                artistSuggestions.innerHTML = '';
                
                // Add artist suggestions
                data.data.forEach(artist => {
                    const artistElement = document.createElement('div');
                    artistElement.className = 'artist-card';
                    artistElement.innerHTML = `
                        <img src="/api/placeholder/40/40" alt="${artist.username}" class="artist-img">
                        <div class="artist-info">
                            <a href="profile.html?id=${artist.id}" class="artist-name">${artist.username}</a>
                            <div class="artist-followers">${artist.formatted_follower_count} followers</div>
                        </div>
                        <button class="follow-button" data-user-id="${artist.id}">Follow</button>
                    `;
                    
                    // Add follow button functionality
                    const followButton = artistElement.querySelector('.follow-button');
                    followButton.addEventListener('click', () => handleFollow(artist.id, followButton));
                    
                    artistSuggestions.appendChild(artistElement);
                });
            }
        }
    } catch (error) {
        console.error('Error loading suggested artists:', error);
    }
}

// Load posts
async function loadPosts() {
    if (isLoading || !hasMorePosts) return;
    
    isLoading = true;
    loadMoreBtn.textContent = 'Loading...';
    loadMoreBtn.disabled = true;

    try {
        // Build URL with filters
        let url = `../controllers/feed/feed_process.php?action=get_feed&page=${currentPage}`;
        
        if (currentFilter === 'following') {
            url += '&following_only=true';
        }
        
        if (currentCategory) {
            url += `&category=${currentCategory}`;
        }
        
        const response = await fetch(url);
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                renderPosts(data.data.posts);
                
                // Update pagination
                currentPage = data.data.pagination.current_page + 1;
                hasMorePosts = data.data.pagination.has_more;
                
                // Show/hide load more button
                loadMoreBtn.style.display = hasMorePosts ? 'block' : 'none';
            }
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showToast('Failed to load posts. Please try again later.', 'error');
    } finally {
        isLoading = false;
        loadMoreBtn.textContent = 'Load More';
        loadMoreBtn.disabled = false;
    }
}

// Render posts to the DOM
function renderPosts(posts) {
    if (!posts || posts.length === 0) {
        if (currentPage === 1) {
            postsContainer.innerHTML = `
                <div class="no-posts">
                    <div class="no-posts-icon">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h3>No posts found</h3>
                    <p>Start following artists or adjust your filters to see more content</p>
                </div>
            `;
        }
        return;
    }
    
    posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'post-card';
        postElement.setAttribute('data-post-id', post.id);
        
        // Format tags for display
        let tagsHTML = '';
        if (post.tags) {
            const tagsList = post.tags.split(',').map(tag => tag.trim());
            const tagsFormatted = tagsList.map(tag => `<a href="#" class="post-tag">#${tag}</a>`).join(' ');
            tagsHTML = `<div class="post-tags">${tagsFormatted}</div>`;
        }
        
        // Build comments HTML
        let commentsHTML = '';
        if (post.comments && post.comments.length > 0) {
            commentsHTML = '<div class="post-comments">';
            post.comments.forEach(comment => {
                commentsHTML += `
                    <div class="post-comment">
                        <a href="profile.html?id=${comment.user.id}" class="comment-author">${comment.user.username}</a>
                        <span class="comment-content">${comment.content}</span>
                    </div>
                `;
            });
            
            // Add "View all comments" link if there are more than 2 comments
            if (post.stats.comments > 2) {
                commentsHTML += `
                    <a href="post.html?id=${post.id}" class="view-all-comments">
                        View all ${post.stats.comments} comments
                    </a>
                `;
            }
            
            commentsHTML += '</div>';
        }
        
        // AI badge if artwork used AI
        const aiBadge = post.ai_info.used_ai ? 
            `<div class="ai-badge" title="Created with ${post.ai_info.ai_tools || 'AI tools'}">
                <i class="fas fa-robot"></i> AI Assisted
            </div>` : '';
        
        postElement.innerHTML = `
            <div class="post-header">
                <div class="post-user">
                    <a href="profile.html?id=${post.author.id}">
                        <img src="/api/placeholder/40/40" alt="${post.author.username}" class="post-user-img">
                    </a>
                    <div class="post-user-info">
                        <a href="profile.html?id=${post.author.id}" class="post-username">${post.author.username}</a>
                        <span class="post-time">${post.created_at_formatted}</span>
                    </div>
                </div>
                <div class="post-options">
                    <button class="options-btn"><i class="fas fa-ellipsis-h"></i></button>
                </div>
            </div>
            
            <div class="post-image-container">
                <a href="post.html?id=${post.id}">
                    <img src="${post.image_url}" alt="${post.title}" class="post-image">
                </a>
                ${aiBadge}
            </div>
            
            <div class="post-content">
                <div class="post-actions">
                    <div class="action-buttons">
                        <button class="action-button like-button ${post.user_interaction.liked ? 'liked' : ''}" data-post-id="${post.id}">
                            <i class="fa${post.user_interaction.liked ? 's' : 'r'} fa-heart"></i>
                            <span class="like-count">${post.stats.likes}</span>
                        </button>
                        <button class="action-button comment-button" data-post-id="${post.id}">
                            <i class="far fa-comment"></i>
                            <span class="comment-count">${post.stats.comments}</span>
                        </button>
                        <button class="action-button share-button" data-post-id="${post.id}">
                            <i class="far fa-paper-plane"></i>
                        </button>
                    </div>
                    <button class="action-button save-button ${post.user_interaction.saved ? 'saved' : ''}" data-post-id="${post.id}">
                        <i class="fa${post.user_interaction.saved ? 's' : 'r'} fa-bookmark"></i>
                    </button>
                </div>
                
                <h3 class="post-title">
                    <a href="post.html?id=${post.id}">${post.title}</a>
                </h3>
                
                <p class="post-description">${post.description}</p>
                
                ${tagsHTML}
                
                ${commentsHTML}
                
                <div class="add-comment">
                    <form class="comment-form" data-post-id="${post.id}">
                        <input type="text" class="comment-input" placeholder="Add a comment...">
                        <button type="submit" class="comment-submit">Post</button>
                    </form>
                </div>
            </div>
        `;
        
        postsContainer.appendChild(postElement);
        
        // Add event listeners for post interactions
        setupPostInteractions(postElement, post);
    });
}

// Setup interactions for a post (likes, comments, etc.)
function setupPostInteractions(postElement, post) {
    // Like button
    const likeButton = postElement.querySelector('.like-button');
    likeButton.addEventListener('click', () => handleLike(post.id, likeButton));
    
    // Comment button
    const commentButton = postElement.querySelector('.comment-button');
    commentButton.addEventListener('click', () => {
        postElement.querySelector('.comment-input').focus();
    });
    
    // Share button
    const shareButton = postElement.querySelector('.share-button');
    shareButton.addEventListener('click', () => handleShare(post.id));
    
    // Save button
    const saveButton = postElement.querySelector('.save-button');
    saveButton.addEventListener('click', () => handleSave(post.id, saveButton));
    
    // Comment form
    const commentForm = postElement.querySelector('.comment-form');
    commentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const commentInput = commentForm.querySelector('.comment-input');
        const commentContent = commentInput.value.trim();
        
        if (commentContent) {
            handleComment(post.id, commentContent, commentForm);
            commentInput.value = '';
        }
    });
    
    // Options button
    const optionsButton = postElement.querySelector('.options-btn');
    optionsButton.addEventListener('click', () => showPostOptions(post.id, post.author.id));
}

// Handle post like/unlike
async function handleLike(postId, likeButton) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('../controllers/feed/feed_process.php?action=like_post', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                const likeIcon = likeButton.querySelector('i');
                const likeCount = likeButton.querySelector('.like-count');
                
                if (data.data.action === 'liked') {
                    likeButton.classList.add('liked');
                    likeIcon.classList.replace('far', 'fas');
                } else {
                    likeButton.classList.remove('liked');
                    likeIcon.classList.replace('fas', 'far');
                }
                
                likeCount.textContent = data.data.likes_count;
            }
        }
    } catch (error) {
        console.error('Error handling like:', error);
        showToast('Failed to process like. Please try again.', 'error');
    }
}

// Handle post save/unsave
async function handleSave(postId, saveButton) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('../controllers/feed/feed_process.php?action=save_post', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                const saveIcon = saveButton.querySelector('i');
                
                if (data.data.action === 'saved') {
                    saveButton.classList.add('saved');
                    saveIcon.classList.replace('far', 'fas');
                    showToast('Post saved to your collection', 'success');
                } else {
                    saveButton.classList.remove('saved');
                    saveIcon.classList.replace('fas', 'far');
                    showToast('Post removed from your collection', 'info');
                }
            }
        }
    } catch (error) {
        console.error('Error handling save:', error);
        showToast('Failed to save post. Please try again.', 'error');
    }
}

// Handle adding a comment
async function handleComment(postId, content, form) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', content);
        
        const response = await fetch('../controllers/feed/feed_process.php?action=add_comment', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                // Update comment count
                const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                const commentCount = postElement.querySelector('.comment-count');
                commentCount.textContent = data.data.comments_count;
                
                // Add the new comment to the comments section
                const commentsSection = postElement.querySelector('.post-comments');
                const newComment = document.createElement('div');
                newComment.className = 'post-comment';
                newComment.innerHTML = `
                    <a href="profile.html?id=${data.data.comment.user.id}" class="comment-author">${data.data.comment.user.username}</a>
                    <span class="comment-content">${data.data.comment.content}</span>
                `;
                
                // Create comments section if it doesn't exist
                if (!commentsSection) {
                    const newCommentsSection = document.createElement('div');
                    newCommentsSection.className = 'post-comments';
                    newCommentsSection.appendChild(newComment);
                    
                    // Insert before the add-comment section
                    const addCommentSection = postElement.querySelector('.add-comment');
                    addCommentSection.parentNode.insertBefore(newCommentsSection, addCommentSection);
                } else {
                    // Add comment to existing section, removing "View all" if it's the first or second comment
                    const viewAllLink = commentsSection.querySelector('.view-all-comments');
                    
                    if (commentsSection.querySelectorAll('.post-comment').length < 2) {
                        commentsSection.appendChild(newComment);
                    } else {
                        // Keep only first comment and add this as second
                        const firstComment = commentsSection.querySelector('.post-comment');
                        commentsSection.innerHTML = '';
                        commentsSection.appendChild(firstComment);
                        commentsSection.appendChild(newComment);
                        
                        // Add "View all" link
                        const viewAllLink = document.createElement('a');
                        viewAllLink.href = `post.html?id=${postId}`;
                        viewAllLink.className = 'view-all-comments';
                        viewAllLink.textContent = `View all ${data.data.comments_count} comments`;
                        commentsSection.appendChild(viewAllLink);
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        showToast('Failed to add comment. Please try again.', 'error');
    }
}

// Handle follow/unfollow user
async function handleFollow(userId, button) {
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('action', 'toggle_follow');
        
        const response = await fetch('../controllers/profile/profile_process.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success) {
                if (data.is_following) {
                    button.textContent = 'Following';
                    button.classList.add('following');
                    showToast('You are now following this artist', 'success');
                } else {
                    button.textContent = 'Follow';
                    button.classList.remove('following');
                }
            }
        }
    } catch (error) {
        console.error('Error handling follow:', error);
        showToast('Failed to update follow status. Please try again.', 'error');
    }
}

// Handle post sharing
function handleShare(postId) {
    const shareURL = `${window.location.origin}/post.html?id=${postId}`;
    
    // Check if Web Share API is supported
    if (navigator.share) {
        navigator.share({
            title: 'Check out this artwork on ArtSpace',
            url: shareURL
        }).catch(error => {
            console.error('Error sharing:', error);
            
            // Fallback to copy to clipboard
            copyToClipboard(shareURL);
        });
    } else {
        // Fallback to copy to clipboard
        copyToClipboard(shareURL);
    }
}

// Copy text to clipboard
function copyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    showToast('Link copied to clipboard', 'success');
}

// Show post options menu
function showPostOptions(postId, authorId) {
    const optionsMenu = document.createElement('div');
    optionsMenu.className = 'post-options-menu';
    
    // Different options depending on whether user is the author
    const isAuthor = authorId === parseInt(localStorage.getItem('user_id'));
    
    if (isAuthor) {
        optionsMenu.innerHTML = `
            <div class="option-item edit-post" data-post-id="${postId}">
                <i class="fas fa-edit"></i> Edit Post
            </div>
            <div class="option-item delete-post" data-post-id="${postId}">
                <i class="fas fa-trash"></i> Delete Post
            </div>
        `;
    } else {
        optionsMenu.innerHTML = `
            <div class="option-item report-post" data-post-id="${postId}">
                <i class="fas fa-flag"></i> Report Post
            </div>
            <div class="option-item hide-post" data-post-id="${postId}">
                <i class="fas fa-eye-slash"></i> Hide Post
            </div>
        `;
    }
    
    // Add to DOM
    document.body.appendChild(optionsMenu);
    
    // Position near the options button
    const optionsBtn = document.querySelector(`.post-card[data-post-id="${postId}"] .options-btn`);
    const rect = optionsBtn.getBoundingClientRect();
    
    optionsMenu.style.top = rect.bottom + window.scrollY + 'px';
    optionsMenu.style.right = (window.innerWidth - rect.right) + 'px';
    
    // Handle option clicks
    optionsMenu.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', () => {
            const action = item.classList[1];
            
            switch (action) {
                case 'edit-post':
                    window.location.href = `edit_post.html?id=${postId}`;
                    break;
                case 'delete-post':
                    handleDeletePost(postId);
                    break;
                case 'report-post':
                    handleReportPost(postId);
                    break;
                case 'hide-post':
                    handleHidePost(postId);
                    break;
                default:
                    break;
            }
            document.body.removeChild(optionsMenu);
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!optionsMenu.contains(e.target) && !optionsBtn.contains(e.target)) {
            document.body.removeChild(optionsMenu);
        }
    }, { once: true });
}

// Handle post deletion
async function handleDeletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) return;

    try {
        const formData = new FormData();
        formData.append('post_id', postId);

        const response = await fetch('../controllers/feed/feed_process.php?action=delete_post', {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            const data = await response.json();

            if (data.success) {
                const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                postElement.remove();
                showToast('Post deleted successfully', 'success');
            }
        }
    } catch (error) {
        console.error('Error deleting post:', error);
        showToast('Failed to delete post. Please try again.', 'error');
    }
}

// Handle post reporting
async function handleReportPost(postId) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);

        const response = await fetch('../controllers/feed/feed_process.php?action=report_post', {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            const data = await response.json();

            if (data.success) {
                showToast('Post reported successfully', 'success');
            }
        }
    } catch (error) {
        console.error('Error reporting post:', error);
        showToast('Failed to report post. Please try again.', 'error');
    }
}

// Handle post hiding
function handleHidePost(postId) {
    const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    postElement.style.display = 'none';
    showToast('Post hidden from your feed', 'info');
}

// Reset feed
function resetFeed() {
    currentPage = 1;
    hasMorePosts = true;
    postsContainer.innerHTML = '';
}

// Debounce function to limit the rate of function execution
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Handle search input
async function handleSearch() {
    const query = searchInput.value.trim();

    if (query.length < 3) {
        searchResults.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`../controllers/feed/feed_process.php?action=search&query=${query}`);

        if (response.ok) {
            const data = await response.json();

            if (data.success) {
                renderSearchResults(data.data.results);
            }
        }
    } catch (error) {
        console.error('Error handling search:', error);
        showToast('Failed to search. Please try again.', 'error');
    }
}

// Render search results
function renderSearchResults(results) {
    searchResults.innerHTML = '';

    if (results.length === 0) {
        searchResults.innerHTML = '<p>No results found</p>';
        return;
    }

    results.forEach(result => {
        const resultElement = document.createElement('div');
        resultElement.className = 'search-result';
        resultElement.innerHTML = `
            <a href="profile.html?id=${result.id}">
                <img src="/api/placeholder/40/40" alt="${result.username}" class="result-img">
                <div class="result-info">
                    <span class="result-username">${result.username}</span>
                    <span class="result-bio">${result.bio || ''}</span>
                </div>
            </a>
        `;
        searchResults.appendChild(resultElement);
    });
}

// Handle logout
function handleLogout() {
    localStorage.removeItem('user_id');
    window.location.href = 'login.html';
}

// Show toast notification
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}