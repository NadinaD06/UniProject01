// State management
let currentPage = 1;
let isLoading = false;
let hasMorePosts = true;

// DOM Elements
const postsContainer = document.getElementById('postsContainer');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const searchInput = document.getElementById('searchInput');
const logoutBtn = document.getElementById('logoutBtn');

// Mock data for stories
const stories = [
    { id: 1, username: 'sarah_artist', avatar: '/api/placeholder/60/60' },
    { id: 2, username: 'digital_dreams', avatar: '/api/placeholder/60/60' },
    { id: 3, username: 'sketch_master', avatar: '/api/placeholder/60/60' },
    { id: 4, username: 'color_theory', avatar: '/api/placeholder/60/60' }
];

// Mock data for suggested artists
const suggestedArtists = [
    { id: 1, name: 'Emma Creative', followers: '12.5k', avatar: '/api/placeholder/40/40' },
    { id: 2, name: 'Art Studio Pro', followers: '8.2k', avatar: '/api/placeholder/40/40' },
    { id: 3, name: 'Digital Nomad', followers: '15.7k', avatar: '/api/placeholder/40/40' }
];

// Initialize the feed
document.addEventListener('DOMContentLoaded', () => {
    loadStories();
    loadSuggestedArtists();
    loadPosts();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    loadMoreBtn.addEventListener('click', loadPosts);
    searchInput.addEventListener('input', debounce(handleSearch, 500));
    logoutBtn.addEventListener('click', handleLogout);

    // Infinite scroll
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1000) {
            if (!isLoading && hasMorePosts) {
                loadPosts();
            }
        }
    });
}

// Load stories into the stories container
function loadStories() {
    const storiesContainer = document.querySelector('.stories-container');
    
    stories.forEach(story => {
        const storyElement = document.createElement('div');
        storyElement.className = 'story';
        storyElement.innerHTML = `
            <div class="story-avatar">
                <img src="${story.avatar}" alt="${story.username}'s story">
            </div>
            <span>${story.username}</span>
        `;
        storiesContainer.appendChild(storyElement);
    });
}

// Load suggested artists
function loadSuggestedArtists() {
    const artistsContainer = document.getElementById('artistSuggestions');
    
    suggestedArtists.forEach(artist => {
        const artistElement = document.createElement('div');
        artistElement.className = 'artist-card';
        artistElement.innerHTML = `
            <img src="${artist.avatar}" alt="${artist.name}" class="artist-img">
            <div class="artist-info">
                <a href="#" class="artist-name">${artist.name}</a>
                <div class="artist-followers">${artist.followers} followers</div>
            </div>
            <button class="follow-button">Follow</button>
        `;
        artistsContainer.appendChild(artistElement);
    });
}

// Load posts
async function loadPosts() {
    if (isLoading || !hasMorePosts) return;
    
    isLoading = true;
    loadMoreBtn.textContent = 'Loading...';

    try {
        // Simulate API call
        const posts = await fetchPosts(currentPage);
        renderPosts(posts);
        
        currentPage++;
        hasMorePosts = posts.length === 10; // Assuming 10 posts per page
    } catch (error) {
        console.error('Error loading posts:', error);
        showError('Failed to load posts. Please try again later.');
    } finally {
        isLoading = false;
        loadMoreBtn.textContent = 'Load More';
        loadMoreBtn.style.display = hasMorePosts ? 'block' : 'none';
    }
}

// Simulate fetching posts from an API
async function fetchPosts(page) {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Mock posts data
    return Array(10).fill(null).map((_, index) => ({
        id: page * 10 + index,
        title: `Artwork #${page * 10 + index + 1}`,
        description: 'A beautiful piece exploring the intersection of digital and traditional art techniques.',
        image: `/api/placeholder/400/400`,
        author: {
            name: `Artist ${index + 1}`,
            avatar: `/api/placeholder/32/32`
        },
        likes: Math.floor(Math.random() * 1000),
        comments: Math.floor(Math.random() * 100)
    }));
}

// Render posts to the DOM
function renderPosts(posts) {
    posts.forEach(post => {
        const postElement = document.createElement('div');
        postElement.className = 'post-card';
        postElement.innerHTML = `
            <img src="${post.image}" alt="${post.title}" class="post-image">
            <div class="post-content">
                <div class="post-header">
                    <img src="${post.author.avatar}" alt="${post.author.name}" class="post-author-img">
                    <a href="#" class="post-author-name">${post.author.name}</a>
                </div>
                <h3 class="post-title">${post.title}</h3>
                <p class="post-description">${post.description}</p>
                <div class="post-actions">
                    <div class="action-buttons">
                        <span class="action-button like-button" onclick="handleLike(${post.id})">
                            <i class="far fa-heart"></i>
                            <span>${post.likes}</span>
                        </span>
                        <span class="action-button comment-button" onclick="handleComment(${post.id})">
                            <i class="far fa-comment"></i>
                            <span>${post.comments}</span>
                        </span>
                        <span class="action-button share-button" onclick="handleShare(${post.id})">
                            <i class="far fa-share-square"></i>
                        </span>
                    </div>
                    <span class="action-button save-button" onclick="handleSave(${post.id})">
                        <i class="far fa-bookmark"></i>
                    </span>
                </div>
            </div>
        `;
        postsContainer.appendChild(postElement);
    });
}

// Handle post interactions
function handleLike(postId) {
    const likeButton = event.currentTarget;
    const icon = likeButton.querySelector('i');
    const likesCount = likeButton.querySelector('span');
    
    if (icon.classList.contains('far')) {
        icon.classList.replace('far', 'fas');
        icon.style.color = '#FF6B6B';
        likesCount.textContent = parseInt(likesCount.textContent) + 1;
    } else {
        icon.classList.replace('fas', 'far');
        icon.style.color = '';
        likesCount.textContent = parseInt(likesCount.textContent) - 1;
    }
}

function handleComment(postId) {
    // Implement comment functionality
    console.log('Comment clicked for post:', postId);
}

function handleShare(postId) {
    // Implement share functionality
    console.log('Share clicked for post:', postId);
}

function handleSave(postId) {
    const saveButton = event.currentTarget;
    const icon = saveButton.querySelector('i');
    
    if (icon.classList.contains('far')) {
        icon.classList.replace('far', 'fas');
        icon.style.color = '#4ECDC4';
    } else {
        icon.classList.replace('fas', 'far');
        icon.style.color = '';
    }
}

// Search functionality
function handleSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    // Implement search functionality
    console.log('Searching for:', searchTerm);
}

// Logout functionality
async function handleLogout() {
    try {
        const response = await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            window.location.href = '/login.html';
        } else {
            throw new Error('Logout failed');
        }
    } catch (error) {
        console.error('Error during logout:', error);
        showError('Failed to logout. Please try again.');
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showError(message) {
    // Implement error notification
    alert(message);
}

// Initialize tooltips and other UI enhancements
document.querySelectorAll('[title]').forEach(element => {
    element.addEventListener('mouseover', e => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = e.target.getAttribute('title');
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.top = rect.bottom + 5 + 'px';
        tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        
        e.target.addEventListener('mouseout', () => tooltip.remove());
    });
});