/**
 * Feed JavaScript
 * Handles all interactions and AJAX requests for the feed page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize any components that need setup
    initializePostActions();
    trackPostViews();
    
    // Set up infinite scroll
    setupInfiniteScroll();
});

/**
 * Initialize post action dropdowns
 */
function initializePostActions() {
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdowns = document.querySelectorAll('.post-action-dropdown');
        dropdowns.forEach(dropdown => {
            if (dropdown.classList.contains('active') && 
                !dropdown.parentNode.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
}

/**
 * Toggle post action dropdown
 * 
 * @param {HTMLElement} button The button element
 * @param {number} postId Post ID
 */
function togglePostActions(button, postId) {
    const dropdown = button.nextElementSibling;
    
    // Close any other open dropdowns
    document.querySelectorAll('.post-action-dropdown.active').forEach(active => {
        if (active !== dropdown) {
            active.classList.remove('active');
        }
    });
    
    // Toggle this dropdown
    dropdown.classList.toggle('active');
}

/**
 * Toggle like status for a post
 * 
 * @param {HTMLElement} button The like button
 * @param {number} postId Post ID
 */
function toggleLike(button, postId) {
    // Prevent double-clicks
    if (button.disabled) return;
    button.disabled = true;
    
    // Get the current state
    const isLiked = button.classList.contains('liked');
    const countElement = button.querySelector('.likes-count');
    const currentCount = parseInt(countElement.textContent);
    
    // Optimistically update UI
    if (isLiked) {
        button.classList.remove('liked');
        button.querySelector('i').className = 'far fa-heart';
        countElement.textContent = Math.max(0, currentCount - 1);
    } else {
        button.classList.add('liked');
        button.querySelector('i').className = 'fas fa-heart';
        countElement.textContent = currentCount + 1;
    }
    
    // Send AJAX request
    fetch('/post/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update count to the server's value (in case of race conditions)
            countElement.textContent = data.data.likes_count;
        } else {
            // Revert UI changes if there was an error
            if (isLiked) {
                button.classList.add('liked');
                button.querySelector('i').className = 'fas fa-heart';
                countElement.textContent = currentCount;
            } else {
                button.classList.remove('liked');
                button.querySelector('i').className = 'far fa-heart';
                countElement.textContent = currentCount;
            }
            
            // Show error
            showToast(data.message || 'Error toggling like', 'error');
        }
    })
    .catch(error => {
        console.error('Error toggling like:', error);
        
        // Revert UI changes
        if (isLiked) {
            button.classList.add('liked');
            button.querySelector('i').className = 'fas fa-heart';
            countElement.textContent = currentCount;
        } else {
            button.classList.remove('liked');
            button.querySelector('i').className = 'far fa-heart';
            countElement.textContent = currentCount;
        }
        
        // Show error
        showToast('Network error while toggling like', 'error');
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
    });
}

/**
 * Toggle save status for a post
 * 
 * @param {HTMLElement} button The save button
 * @param {number} postId Post ID
 */
function toggleSave(button, postId) {
    // Prevent double-clicks
    if (button.disabled) return;
    button.disabled = true;
    
    // Get the current state
    const isSaved = button.classList.contains('saved');
    
    // Optimistically update UI
    if (isSaved) {
        button.classList.remove('saved');
        button.querySelector('i').className = 'far fa-bookmark';
    } else {
        button.classList.add('saved');
        button.querySelector('i').className = 'fas fa-bookmark';
    }
    
    // Send AJAX request
    fetch('/post/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revert UI changes if there was an error
            if (isSaved) {
                button.classList.add('saved');
                button.querySelector('i').className = 'fas fa-bookmark';
            } else {
                button.classList.remove('saved');
                button.querySelector('i').className = 'far fa-bookmark';
            }
            
            // Show error
            showToast(data.message || 'Error toggling save', 'error');
        } else {
            // Show success message
            const action = data.data.action === 'saved' ? 'saved' : 'removed from saved';
            showToast(`Post ${action}`, 'success');
        }
    })
    .catch(error => {
        console.error('Error toggling save:', error);
        
        // Revert UI changes
        if (isSaved) {
            button.classList.add('saved');
            button.querySelector('i').className = 'fas fa-bookmark';
        } else {
            button.classList.remove('saved');
            button.querySelector('i').className = 'far fa-bookmark';
        }
        
        // Show error
        showToast('Network error while toggling save', 'error');
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
    });
}

/**
 * Submit a comment on a post
 * 
 * @param {HTMLElement} input The comment input
 * @param {number} postId Post ID
 */
function submitComment(input, postId) {
    const content = input.value.trim();
    
    // Don't submit empty comments
    if (!content) return;
    
    // Disable input while submitting
    input.disabled = true;
    
    // Send AJAX request
    fetch('/comment/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            
            // Update comment count
            const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            const commentCountElement = postElement.querySelector('.comments-count');
            const commentCount = parseInt(commentCountElement.textContent) + 1;
            commentCountElement.textContent = commentCount;
            
            // Add new comment to preview if there's a preview section
            const previewSection = postElement.querySelector('.post-comments-preview');
            if (previewSection) {
                const comment = data.data.comment;
                
                // Get user data
                const currentUser = {
                    username: document.querySelector('.profile-info h3').textContent,
                    profile_picture: document.querySelector('.profile-avatar').src
                };
                
                // Create comment element
                const commentElement = document.createElement('div');
                commentElement.className = 'comment-preview';
                commentElement.innerHTML = `
                    <a href="/profile/${currentUser.username}" class="comment-user">
                        <img src="${currentUser.profile_picture}" alt="${currentUser.username}" class="comment-avatar">
                    </a>
                    <div class="comment-content">
                        <a href="/profile/${currentUser.username}" class="comment-username">
                            ${currentUser.username}
                        </a>
                        ${comment.content.length > 100 ? comment.content.substring(0, 100) + '...' : comment.content}
                    </div>
                `;
                
                // If no comments existed before, create the preview section
                if (!previewSection) {
                    const newPreviewSection = document.createElement('div');
                    newPreviewSection.className = 'post-comments-preview';
                    newPreviewSection.appendChild(commentElement);
                    
                    // Add view more link if there are multiple comments
                    if (commentCount > 1) {
                        const viewMoreLink = document.createElement('a');
                        viewMoreLink.href = `/post/${postId}#comments`;
                        viewMoreLink.className = 'view-more-comments';
                        viewMoreLink.textContent = `View all ${commentCount} comments`;
                        newPreviewSection.appendChild(viewMoreLink);
                    }
                    
                    // Insert after post stats
                    const postFooter = postElement.querySelector('.post-footer');
                    const postStats = postElement.querySelector('.post-stats');
                    postFooter.insertBefore(newPreviewSection, postStats.nextSibling);
                } else {
                    // If there are already comments, just prepend the new one
                    const viewMoreLink = previewSection.querySelector('.view-more-comments');
                    
                    if (viewMoreLink) {
                        // Update the view more link text
                        viewMoreLink.textContent = `View all ${commentCount} comments`;
                        // Insert before the view more link
                        previewSection.insertBefore(commentElement, viewMoreLink);
                    } else {
                        // No view more link yet, just append
                        previewSection.appendChild(commentElement);
                        
                        // Add view more link if needed
                        if (commentCount > 2) {
                            const viewMoreLink = document.createElement('a');
                            viewMoreLink.href = `/post/${postId}#comments`;
                            viewMoreLink.className = 'view-more-comments';
                            viewMoreLink.textContent = `View all ${commentCount} comments`;
                            previewSection.appendChild(viewMoreLink);
                        }
                    }
                }
            }
            
            // Show success message
            showToast('Comment added', 'success');
        } else {
            // Show error
            showToast(data.message || 'Error adding comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting comment:', error);
        showToast('Network error while adding comment', 'error');
    })
    .finally(() => {
        // Re-enable input
        input.disabled = false;
    });
}

/**
 * Show confirmation dialog for post deletion
 * 
 * @param {number} postId Post ID
 */
function confirmDeletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        deletePost(postId);
    }
}

/**
 * Delete a post
 * 
 * @param {number} postId Post ID
 */
function deletePost(postId) {
    // Show loading state
    const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    postElement.classList.add('deleting');
    
    // Send AJAX request
    fetch(`/post/${postId}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove post element with animation
            postElement.classList.add('deleted');
            setTimeout(() => {
                postElement.remove();
            }, 300);
            
            // Show success message
            showToast('Post deleted successfully', 'success');
        } else {
            // Remove loading state
            postElement.classList.remove('deleting');
            
            // Show error
            showToast(data.message || 'Error deleting post', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting post:', error);
        
        // Remove loading state
        postElement.classList.remove('deleting');
        
        // Show error
        showToast('Network error while deleting post', 'error');
    });
}

/**
 * Report a post
 * 
 * @param {number} postId Post ID
 */
function reportPost(postId) {
    // Use the global report modal defined in footer.php
    window.openReportModal('post', postId);
}

/**
 * Copy post link to clipboard
 * 
 * @param {number} postId Post ID
 */
function copyPostLink(postId) {
    const url = `${window.location.origin}/post/${postId}`;
    
    // Use Clipboard API if available
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
            .then(() => {
                showToast('Link copied to clipboard', 'success');
            })
            .catch(() => {
                // Fallback to older method
                fallbackCopyToClipboard(url);
            });
    } else {
        // Fallback for browsers that don't support clipboard API
        fallbackCopyToClipboard(url);
    }
}

/**
 * Fallback method to copy text to clipboard
 * 
 * @param {string} text Text to copy
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    
    // Make the textarea out of viewport
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showToast('Link copied to clipboard', 'success');
        } else {
            showToast('Failed to copy link', 'error');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showToast('Failed to copy link', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Follow or unfollow a user
 * 
 * @param {HTMLElement} button The follow button
 * @param {number} userId User ID
 */
function followUser(button, userId) {
    // Prevent double-clicks
    if (button.disabled) return;
    button.disabled = true;
    
    // Get current state
    const isFollowing = button.classList.contains('following');
    
    // Optimistically update UI
    if (isFollowing) {
        button.classList.remove('following');
        button.textContent = 'Follow';
    } else {
        button.classList.add('following');
        button.textContent = 'Following';
    }
    
    // Send AJAX request
    fetch('/follow', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Already handled optimistically above
            
            // Update following count in profile stats if on feed page
            const followingCountElement = document.querySelector('.profile-stats .stat:nth-child(3) .stat-value');
            if (followingCountElement) {
                const currentCount = parseInt(followingCountElement.textContent);
                followingCountElement.textContent = isFollowing ? currentCount - 1 : currentCount + 1;
            }
            
            // Show success message
            showToast(isFollowing ? 'Unfollowed user' : 'Now following user', 'success');
        } else {
            // Revert UI changes
            if (isFollowing) {
                button.classList.add('following');
                button.textContent = 'Following';
            } else {
                button.classList.remove('following');
                button.textContent = 'Follow';
            }
            
            // Show error
            showToast(data.message || 'Error following user', 'error');
        }
    })
    .catch(error => {
        console.error('Error following user:', error);
        
        // Revert UI changes
        if (isFollowing) {
            button.classList.add('following');
            button.textContent = 'Following';
        } else {
            button.classList.remove('following');
            button.textContent = 'Follow';
        }
        
        // Show error
        showToast('Network error while following user', 'error');
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
    });
}

/**
 * View a post (track view)
 * 
 * @param {number} postId Post ID
 */
function viewPost(postId) {
    // We'll do this quietly in the background
    fetch('/post/view', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .catch(error => {
        console.error('Error tracking post view:', error);
    });
}

/**
 * Track views for visible posts
 */
function trackPostViews() {
    // Use Intersection Observer to track when posts are viewed
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const postId = entry.target.dataset.postId;
                    if (postId) {
                        viewPost(postId);
                        // Stop observing after view is tracked
                        observer.unobserve(entry.target);
                    }
                }
            });
        }, {
            threshold: 0.5 // Post must be 50% visible
        });
        
        // Observe all post cards
        document.querySelectorAll('.post-card').forEach(post => {
            observer.observe(post);
        });
    }
}

/**
 * Load more posts (infinite scroll)
 */
function loadMorePosts() {
    // Get current parameters
    const filter = document.getElementById('currentFilter').value;
    const category = document.getElementById('currentCategory').value;
    const offset = parseInt(document.getElementById('currentOffset').value);
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    // Show loading state
    loadMoreBtn.disabled = true;
    loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    // Send AJAX request
    fetch('/feed/load-more', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            filter: filter,
            category: category,
            offset: offset
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Append new posts
            const postsContainer = document.getElementById('postsContainer');
            const loadMoreContainer = document.querySelector('.load-more-container');
            
            if (data.data.posts && data.data.posts.length > 0) {
                // Update offset for next request
                document.getElementById('currentOffset').value = offset + data.data.posts.length;
                
                // Create HTML for new posts
                const postsHTML = data.data.posts.map(post => {
                    return createPostHTML(post);
                }).join('');
                
                // Insert new posts before the load more button container
                loadMoreContainer.insertAdjacentHTML('beforebegin', postsHTML);
                
                // Hide load more button if no more posts
                if (!data.data.has_more) {
                    loadMoreContainer.style.display = 'none';
                }
                
                // Initialize any new elements
                initializePostActions();
                trackPostViews();
            } else {
                // No more posts
                loadMoreContainer.style.display = 'none';
            }
        } else {
            // Show error
            showToast(data.message || 'Error loading more posts', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading more posts:', error);
        showToast('Network error while loading more posts', 'error');
    })
    .finally(() => {
        // Reset button state
        loadMoreBtn.disabled = false;
        loadMoreBtn.innerHTML = 'Load More';
    });
}

/**
 * Set up infinite scroll functionality
 */
function setupInfiniteScroll() {
    // Check if we have the load more button
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (!loadMoreBtn) return;
    
    // Use Intersection Observer if available
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !loadMoreBtn.disabled) {
                    loadMorePosts();
                }
            });
        }, {
            rootMargin: '200px' // Load more when button is within 200px of viewport
        });
        
        // Observe the load more button
        observer.observe(loadMoreBtn);
    } else {
        // Fallback to scroll event for older browsers
        window.addEventListener('scroll', () => {
            const rect = loadMoreBtn.getBoundingClientRect();
            if (rect.top <= window.innerHeight && !loadMoreBtn.disabled) {
                loadMorePosts();
            }
        });
    }
}

/**
 * Create HTML for a post
 * 
 * @param {Object} post Post data
 * @return {string} HTML for the post
 */
function createPostHTML(post) {
    // Format post creation time
    const createdAt = formatTimeAgo(post.created_at);
    
    // Format post tags
    const tagsHTML = post.tags ? post.tags.split(',').map(tag => {
        tag = tag.trim();
        if (tag) {
            return `<a href="/explore?tag=${encodeURIComponent(tag)}" class="tag">#${tag}</a>`;
        }
        return '';
    }).join('') : '';
    
    // Format comments preview
    let commentsHTML = '';
    if (post.comments_count > 0 && post.comments && post.comments.length > 0) {
        commentsHTML = `
            <div class="post-comments-preview">
                ${post.comments.map(comment => `
                    <div class="comment-preview">
                        <a href="/profile/${comment.user.username}" class="comment-user">
                            <img src="${comment.user.profile_picture}" 
                                 alt="${comment.user.username}" class="comment-avatar">
                        </a>
                        <div class="comment-content">
                            <a href="/profile/${comment.user.username}" class="comment-username">
                                ${comment.user.username}
                            </a>
                            ${comment.content.length > 100 ? comment.content.substring(0, 100) + '...' : comment.content}
                        </div>
                    </div>
                `).join('')}
                
                ${post.comments_count > post.comments.length ? `
                    <a href="/post/${post.id}#comments" class="view-more-comments">
                        View all ${post.comments_count} comments
                    </a>
                ` : ''}
            </div>
        `;
    }
    
    return `
    <div class="post-card" data-post-id="${post.id}">
        <!-- Post Header -->
        <div class="post-header">
            <div class="post-user">
                <a href="/profile/${post.author_username}">
                    <img src="${post.author_profile_pic || '/assets/images/default-avatar.png'}" 
                         alt="${post.author_username}" class="post-avatar">
                </a>
                <div class="post-user-info">
                    <a href="/profile/${post.author_username}" class="post-username">
                        ${post.author_username}
                    </a>
                    <span class="post-time">${createdAt}</span>
                </div>
            </div>
            <div class="post-actions">
                <button class="post-action-btn" aria-label="More options" 
                        onclick="togglePostActions(this, ${post.id})">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <div class="post-action-dropdown">
                    ${post.user_id === currentUserId ? `
                    <a href="/post/${post.id}/edit">
                        <i class="fas fa-edit"></i> Edit Post
                    </a>
                    <a href="#" onclick="confirmDeletePost(${post.id}); return false;">
                        <i class="fas fa-trash"></i> Delete Post
                    </a>
                    ` : `
                    <a href="#" onclick="reportPost(${post.id}); return false;">
                        <i class="fas fa-flag"></i> Report Post
                    </a>
                    `}
                    <a href="/post/${post.id}">
                        <i class="fas fa-external-link-alt"></i> View Post
                    </a>
                    <a href="#" onclick="copyPostLink(${post.id}); return false;">
                        <i class="fas fa-link"></i> Copy Link
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Post Content -->
        <div class="post-content">
            ${post.title ? `
            <h3 class="post-title">
                <a href="/post/${post.id}">${post.title}</a>
            </h3>
            ` : ''}
            
            ${post.description ? `
            <div class="post-description">
                ${post.description.length > 200 
                    ? post.description.substring(0, 200) + `... <a href="/post/${post.id}" class="read-more">Read more</a>` 
                    : post.description}
            </div>
            ` : ''}
            
            <div class="post-image">
                <a href="/post/${post.id}">
                    <img src="${post.image_url}" alt="${post.title || 'Post image'}"
                         loading="lazy" onclick="viewPost(${post.id})">
                </a>
                ${post.used_ai ? '<div class="ai-badge" title="Created with AI">AI</div>' : ''}
            </div>
            
            ${post.tags ? `<div class="post-tags">${tagsHTML}</div>` : ''}
        </div>
        
        <!-- Post Footer -->
        <div class="post-footer">
            <div class="post-stats">
                <div class="post-likes">
                    <button class="like-btn ${post.user_liked ? 'liked' : ''}" 
                            onclick="toggleLike(this, ${post.id})">
                        <i class="${post.user_liked ? 'fas' : 'far'} fa-heart"></i>
                        <span class="likes-count">${post.likes_count}</span>
                    </button>
                </div>
                <div class="post-comments">
                    <a href="/post/${post.id}#comments">
                        <i class="far fa-comment"></i>
                        <span class="comments-count">${post.comments_count}</span>
                    </a>
                </div>
                <div class="post-save">
                    <button class="save-btn ${post.user_saved ? 'saved' : ''}" 
                            onclick="toggleSave(this, ${post.id})">
                        <i class="${post.user_saved ? 'fas' : 'far'} fa-bookmark"></i>
                    </button>
                </div>
            </div>
            
            ${commentsHTML}
            
            <div class="post-comment-form">
                <img src="${currentUserProfilePic || '/assets/images/default-avatar.png'}" 
                     alt="Your profile" class="comment-avatar">
                <input type="text" placeholder="Add a comment..." 
                       onkeydown="if(event.key==='Enter')submitComment(this, ${post.id})">
            </div>
        </div>
    </div>
    `;
}

// Store current user data for post creation
const currentUserId = document.querySelector('.profile-info')?.getAttribute('data-user-id');
const currentUserProfilePic = document.querySelector('.profile-avatar')?.src;