// State management
let currentPage = 1;
let isLoading = false;
let hasMoreArtworks = true;
let currentFilter = 'trending';
let currentCategory = '';
let currentTag = '';
let searchQuery = '';

// DOM Elements
const exploreGrid = document.getElementById('exploreGrid');
const tagsContainer = document.getElementById('tagsContainer');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const filterButtons = document.querySelectorAll('.filter-btn');
const categorySelect = document.getElementById('categorySelect');
const searchInput = document.getElementById('searchInput');

// Modals
const artistPreviewModal = document.getElementById('artistPreviewModal');
const artworkDetailModal = document.getElementById('artworkDetailModal');

// Initialize the explore page
document.addEventListener('DOMContentLoaded', () => {
    loadTrendingTags();
    loadArtworks();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // Filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentFilter = button.getAttribute('data-filter');
            resetExplore();
            loadArtworks();
        });
    });

    // Category filter
    categorySelect.addEventListener('change', () => {
        currentCategory = categorySelect.value;
        resetExplore();
        loadArtworks();
    });

    // Load more button
    loadMoreBtn.addEventListener('click', loadArtworks);

    // Search input
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            searchQuery = searchInput.value.trim();
            resetExplore();
            loadArtworks();
        }
    });

    // Close modals when clicking on X or outside
    document.querySelectorAll('.close-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            artistPreviewModal.style.display = 'none';
            artworkDetailModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === artistPreviewModal) {
            artistPreviewModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
        if (e.target === artworkDetailModal) {
            artworkDetailModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });

    // Comment form submission
    document.getElementById('commentForm').addEventListener('submit', handleCommentSubmit);
}

// Load trending tags
async function loadTrendingTags() {
    try {
        const response = await fetch(`../controllers/explore/explore_process.php?action=get_tags`);
        const data = await response.json();

        if (data.success) {
            const tags = data.data;
            
            // Clear skeletons
            tagsContainer.innerHTML = '';
            
            // Add tags to container
            tags.forEach(tag => {
                const tagElement = document.createElement('a');
                tagElement.className = 'tag';
                tagElement.href = '#';
                tagElement.innerHTML = `#${tag.name} <span class="tag-count">${tag.count}</span>`;
                tagElement.setAttribute('data-tag', tag.name);
                
                tagElement.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentTag = tag.name;
                    
                    // Update active state on tags
                    document.querySelectorAll('.tag').forEach(t => t.classList.remove('active'));
                    tagElement.classList.add('active');
                    
                    resetExplore();
                    loadArtworks();
                });
                
                tagsContainer.appendChild(tagElement);
            });
        }
    } catch (error) {
        console.error('Error loading trending tags:', error);
    }
}

// Load artworks
async function loadArtworks() {
    if (isLoading || !hasMoreArtworks) return;
    
    isLoading = true;
    updateLoadMoreButton('Loading...');

    try {
        // Build URL with filters
        let url = `../controllers/explore/explore_process.php?action=get_artworks&page=${currentPage}&filter=${currentFilter}`;
        
        if (currentCategory) {
            url += `&category=${currentCategory}`;
        }
        
        if (currentTag) {
            url += `&tag=${currentTag}`;
        }
        
        if (searchQuery) {
            url += `&q=${encodeURIComponent(searchQuery)}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            // Remove skeletons when loading first page
            if (currentPage === 1) {
                exploreGrid.innerHTML = '';
            }
            
            // Render artworks
            renderArtworks(data.data.artworks);
            
            // Update pagination
            currentPage++;
            hasMoreArtworks = data.data.has_more;
            
            // Update load more button
            if (hasMoreArtworks) {
                updateLoadMoreButton('Load More');
                loadMoreBtn.style.display = 'block';
            } else {
                loadMoreBtn.style.display = 'none';
            }
        } else {
            console.error('Error loading artworks:', data.message);
        }
    } catch (error) {
        console.error('Error loading artworks:', error);
    } finally {
        isLoading = false;
    }
}

// Render artworks to the grid
function renderArtworks(artworks) {
    if (artworks.length === 0 && currentPage === 1) {
        // No results found
        exploreGrid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No artworks found</h3>
                <p>Try adjusting your filters or search query</p>
            </div>
        `;
        return;
    }
    
    artworks.forEach(artwork => {
        const artworkElement = document.createElement('div');
        artworkElement.className = 'artwork-item';
        artworkElement.setAttribute('data-id', artwork.id);
        
        // Create the AI badge if artwork used AI
        const aiBadge = artwork.used_ai ? 
            `<div class="ai-badge" title="Created with ${artwork.ai_tools || 'AI tools'}">
                <i class="fas fa-robot"></i>
            </div>` : '';
        
        artworkElement.innerHTML = `
            <div class="artwork-image">
                <img src="${artwork.image_url}" alt="${artwork.title}">
                ${aiBadge}
                <div class="artwork-overlay">
                    <div class="artwork-actions">
                        <button class="like-btn ${artwork.user_liked ? 'liked' : ''}" data-id="${artwork.id}">
                            <i class="fa${artwork.user_liked ? 's' : 'r'} fa-heart"></i>
                            <span>${artwork.likes_count}</span>
                        </button>
                        <button class="view-btn" data-id="${artwork.id}">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="artwork-details">
                <h3>${artwork.title}</h3>
                <div class="artwork-artist" data-id="${artwork.artist.id}">
                    <img src="/api/placeholder/24/24" alt="${artwork.artist.username}">
                    <span>${artwork.artist.username}</span>
                </div>
            </div>
        `;
        
        // Add event listeners
        const likeBtn = artworkElement.querySelector('.like-btn');
        likeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            handleLikeArtwork(artwork.id, likeBtn);
        });
        
        const viewBtn = artworkElement.querySelector('.view-btn');
        viewBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            openArtworkDetail(artwork.id);
        });
        
        // Make the entire artwork clickable
        artworkElement.addEventListener('click', () => {
            openArtworkDetail(artwork.id);
        });
        
        // Artist preview on hover
        const artistElement = artworkElement.querySelector('.artwork-artist');
        artistElement.addEventListener('click', (e) => {
            e.stopPropagation();
            openArtistPreview(artwork.artist.id);
        });
        
        exploreGrid.appendChild(artworkElement);
    });
}

// Handle liking an artwork
async function handleLikeArtwork(artworkId, likeButton) {
    try {
        const response = await fetch('../controllers/explore/explore_process.php?action=like_artwork', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `artwork_id=${artworkId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const iconElement = likeButton.querySelector('i');
            const countElement = likeButton.querySelector('span');
            
            if (data.data.action === 'liked') {
                likeButton.classList.add('liked');
                iconElement.className = 'fas fa-heart';
            } else {
                likeButton.classList.remove('liked');
                iconElement.className = 'far fa-heart';
            }
            
            countElement.textContent = data.data.likes_count;
            
            // Also update the like button in the modal if it's open
            if (artworkDetailModal.style.display === 'block') {
                const modalLikeBtn = document.getElementById('modalLikeBtn');
                const modalLikeCount = document.getElementById('modalLikeCount');
                
                if (modalLikeBtn.getAttribute('data-id') === artworkId.toString()) {
                    if (data.data.action === 'liked') {
                        modalLikeBtn.classList.add('liked');
                        modalLikeBtn.querySelector('i').className = 'fas fa-heart';
                    } else {
                        modalLikeBtn.classList.remove('liked');
                        modalLikeBtn.querySelector('i').className = 'far fa-heart';
                    }
                    
                    modalLikeCount.textContent = data.data.likes_count;
                }
            }
        }
    } catch (error) {
        console.error('Error liking artwork:', error);
    }
}

// Open artwork detail modal
async function openArtworkDetail(artworkId) {
    try {
        const response = await fetch(`../controllers/explore/explore_process.php?action=get_artwork_detail&artwork_id=${artworkId}`);
        const data = await response.json();
        
        if (data.success) {
            const artwork = data.data;
            
            // Set modal content
            document.getElementById('modalArtworkImage').src = artwork.image_url;
            document.getElementById('modalArtworkTitle').textContent = artwork.title;
            document.getElementById('modalArtworkDescription').textContent = artwork.description || 'No description provided.';
            document.getElementById('modalArtworkCategory').innerHTML = `Category: <span>${artwork.category || 'Uncategorized'}</span>`;
            document.getElementById('modalArtworkDate').innerHTML = `Posted: <span>${artwork.created_at_formatted}</span>`;
            
            // Artist info
            document.getElementById('modalArtworkArtistAvatar').src = '/api/placeholder/40/40'; // Replace with actual avatar URL when available
            document.getElementById('modalArtworkArtistName').textContent = artwork.artist.username;
            document.getElementById('modalArtworkArtistName').href = `profile.html?id=${artwork.artist.id}`;
            
            // Likes and comments
            const modalLikeBtn = document.getElementById('modalLikeBtn');
            const modalLikeCount = document.getElementById('modalLikeCount');
            const modalCommentCount = document.getElementById('modalCommentCount');
            
            modalLikeBtn.setAttribute('data-id', artwork.id);
            if (artwork.user_liked) {
                modalLikeBtn.classList.add('liked');
                modalLikeBtn.querySelector('i').className = 'fas fa-heart';
            } else {
                modalLikeBtn.classList.remove('liked');
                modalLikeBtn.querySelector('i').className = 'far fa-heart';
            }
            
            modalLikeCount.textContent = artwork.likes_count;
            modalCommentCount.textContent = artwork.comments_count;
            
            // Save button
            const modalSaveBtn = document.getElementById('modalSaveBtn');
            modalSaveBtn.setAttribute('data-id', artwork.id);
            if (artwork.user_saved) {
                modalSaveBtn.classList.add('saved');
                modalSaveBtn.querySelector('i').className = 'fas fa-bookmark';
            } else {
                modalSaveBtn.classList.remove('saved');
                modalSaveBtn.querySelector('i').className = 'far fa-bookmark';
            }
            
            // Share button
            const modalShareBtn = document.getElementById('modalShareBtn');
            modalShareBtn.setAttribute('data-id', artwork.id);
            
            // Tags
            const tagsContainer = document.getElementById('modalArtworkTags');
            tagsContainer.innerHTML = '';
            
            if (artwork.tags) {
                const tagsList = artwork.tags.split(',');
                tagsList.forEach(tag => {
                    const tagElement = document.createElement('a');
                    tagElement.className = 'tag';
                    tagElement.href = '#';
                    tagElement.textContent = `#${tag.trim()}`;
                    tagElement.addEventListener('click', (e) => {
                        e.preventDefault();
                        currentTag = tag.trim();
                        resetExplore();
                        loadArtworks();
                        artworkDetailModal.style.display = 'none';
                        document.body.classList.remove('modal-open');
                    });
                    tagsContainer.appendChild(tagElement);
                });
            }
            
            // Comments
            const commentsContainer = document.getElementById('modalComments');
            commentsContainer.innerHTML = '';
            
            if (artwork.comments && artwork.comments.length > 0) {
                artwork.comments.forEach(comment => {
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment';
                    commentElement.innerHTML = `
                        <div class="comment-user">
                            <img src="/api/placeholder/32/32" alt="${comment.user.username}">
                            <a href="profile.html?id=${comment.user.id}">${comment.user.username}</a>
                        </div>
                        <div class="comment-content">
                            <p>${comment.content}</p>
                            <span class="comment-date">${comment.created_at_formatted}</span>
                        </div>
                    `;
                    commentsContainer.appendChild(commentElement);
                });
            } else {
                commentsContainer.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
            }
            
            // Set up comment form
            const commentForm = document.getElementById('commentForm');
            commentForm.setAttribute('data-artwork-id', artwork.id);
            
            // Show the modal
            artworkDetailModal.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Add event listeners
            modalLikeBtn.addEventListener('click', () => handleLikeArtwork(artwork.id, modalLikeBtn));
            modalSaveBtn.addEventListener('click', () => handleSaveArtwork(artwork.id, modalSaveBtn));
            modalShareBtn.addEventListener('click', () => handleShareArtwork(artwork.id));
        }
    } catch (error) {
        console.error('Error opening artwork detail:', error);
    }
}

// Open artist preview modal
async function openArtistPreview(artistId) {
    try {
        const response = await fetch(`../controllers/explore/explore_process.php?action=get_artist_preview&artist_id=${artistId}`);
        const data = await response.json();
        
        if (data.success) {
            const artist = data.data;
            
            // Set modal content
            document.getElementById('modalArtistAvatar').src = '/api/placeholder/80/80'; // Replace with actual avatar URL when available
            document.getElementById('modalArtistName').textContent = artist.username;
            document.getElementById('modalArtistBio').textContent = artist.bio || 'No bio yet.';
            document.getElementById('modalArtistFollowers').textContent = artist.follower_count;
            document.getElementById('modalArtistPosts').textContent = artist.post_count;
            
            // Set buttons
            const followBtn = document.getElementById('modalFollowBtn');
            followBtn.setAttribute('data-id', artist.id);
            
            if (artist.is_following) {
                followBtn.classList.add('following');
                followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
            } else {
                followBtn.classList.remove('following');
                followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
            }
            
            document.getElementById('modalProfileLink').href = `profile.html?id=${artist.id}`;
            
            const messageBtn = document.getElementById('modalMessageBtn');
            messageBtn.setAttribute('data-id', artist.id);
            messageBtn.addEventListener('click', () => {
                window.location.href = `messages.html?user_id=${artist.id}`;
            });
            
            // Artist gallery
            const galleryContainer = document.getElementById('modalArtistGallery');
            galleryContainer.innerHTML = '';
            
            if (artist.recent_works && artist.recent_works.length > 0) {
                artist.recent_works.forEach(artwork => {
                    const artworkElement = document.createElement('div');
                    artworkElement.className = 'preview-artwork';
                    artworkElement.innerHTML = `<img src="${artwork.image_url}" alt="${artwork.title}">`;
                    
                    artworkElement.addEventListener('click', () => {
                        artistPreviewModal.style.display = 'none';
                        openArtworkDetail(artwork.id);
                    });
                    
                    galleryContainer.appendChild(artworkElement);
                });
            } else {
                galleryContainer.innerHTML = '<p class="no-artworks">No artworks yet.</p>';
            }
            
            // Show the modal
            artistPreviewModal.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Add event listeners
            followBtn.addEventListener('click', () => handleFollowArtist(artist.id, followBtn));
        }
    } catch (error) {
        console.error('Error opening artist preview:', error);
    }
}

// Handle saving an artwork
async function handleSaveArtwork(artworkId, saveButton) {
    try {
        const response = await fetch('../controllers/explore/explore_process.php?action=save_artwork', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `artwork_id=${artworkId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const iconElement = saveButton.querySelector('i');
            
            if (data.data.action === 'saved') {
                saveButton.classList.add('saved');
                iconElement.className = 'fas fa-bookmark';
                showToast('Artwork saved to your collection', 'success');
            } else {
                saveButton.classList.remove('saved');
                iconElement.className = 'far fa-bookmark';
                showToast('Artwork removed from your collection', 'info');
            }
        }
    } catch (error) {
        console.error('Error saving artwork:', error);
    }
}

// Handle sharing an artwork
function handleShareArtwork(artworkId) {
    const shareURL = `${window.location.origin}/artwork.html?id=${artworkId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Check out this artwork on ArtSpace',
            url: shareURL
        }).catch(error => {
            console.error('Error sharing:', error);
            copyToClipboard(shareURL);
        });
    } else {
        copyToClipboard(shareURL);
    }
}

// Handle following an artist
async function handleFollowArtist(artistId, followButton) {
    try {
        const response = await fetch('../controllers/explore/explore_process.php?action=follow_artist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `artist_id=${artistId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const followerCountElement = document.getElementById('modalArtistFollowers');
            
            if (data.data.action === 'followed') {
                followButton.classList.add('following');
                followButton.innerHTML = '<i class="fas fa-user-check"></i> Following';
                showToast('You are now following this artist', 'success');
            } else {
                followButton.classList.remove('following');
                followButton.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                showToast('You unfollowed this artist', 'info');
            }
            
            followerCountElement.textContent = data.data.follower_count;
        }
    } catch (error) {
        console.error('Error following artist:', error);
    }
}

// Handle comment submission
async function handleCommentSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const artworkId = form.getAttribute('data-artwork-id');
    const commentInput = form.querySelector('textarea');
    const comment = commentInput.value.trim();
    
    if (!comment) {
        return;
    }
    
    try {
        const response = await fetch('../controllers/explore/explore_process.php?action=add_comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `artwork_id=${artworkId}&comment=${encodeURIComponent(comment)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear the input
            commentInput.value = '';
            
            // Create new comment element
            const commentsContainer = document.getElementById('modalComments');
            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            
            const newComment = data.data;
            
            commentElement.innerHTML = `
                <div class="comment-user">
                    <img src="/api/placeholder/32/32" alt="${newComment.user.username}">
                    <a href="profile.html?id=${newComment.user.id}">${newComment.user.username}</a>
                </div>
                <div class="comment-content">
                    <p>${newComment.content}</p>
                    <span class="comment-date">${newComment.created_at_formatted}</span>
                </div>
            `;
            
            // Remove "no comments" message if it exists
            const noCommentsMessage = commentsContainer.querySelector('.no-comments');
            if (noCommentsMessage) {
                commentsContainer.innerHTML = '';
            }
            
            // Add new comment to the top
            commentsContainer.prepend(commentElement);
            
            // Update comment count
            const modalCommentCount = document.getElementById('modalCommentCount');
            modalCommentCount.textContent = parseInt(modalCommentCount.textContent) + 1;
            
            showToast('Comment added successfully', 'success');
        }
    } catch (error) {
        console.error('Error adding comment:', error);
    }
}

// Copy text to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Link copied to clipboard', 'success');
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}

// Reset explore page
function resetExplore() {
    currentPage = 1;
    hasMoreArtworks = true;
    exploreGrid.innerHTML = getSkeletonLoaders();
}

// Update load more button
function updateLoadMoreButton(text) {
    loadMoreBtn.textContent = text;
    loadMoreBtn.disabled = isLoading;
}

// Show toast message
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Trigger reflow to get the animation working
    void toast.offsetWidth;
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Generate skeleton loaders
function getSkeletonLoaders() {
    let skeletons = '';
    
    for (let i = 0; i < 12; i++) {
        skeletons += `
            <div class="artwork-item skeleton">
                <div class="artwork-image skeleton-image"></div>
                <div class="artwork-details">
                    <div class="skeleton-title"></div>
                    <div class="skeleton-artist"></div>
                </div>
            </div>
        `;
    }
    
    return skeletons;
}