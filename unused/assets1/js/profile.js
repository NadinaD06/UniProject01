document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show the selected tab content
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).style.display = 'block';
        });
    });
    
    // Gallery view switching (grid/list)
    const viewButtons = document.querySelectorAll('.view-btn');
    const galleryGrid = document.querySelector('.gallery-grid');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all view buttons
            viewButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Change the gallery view
            const viewType = button.getAttribute('data-view');
            if (viewType === 'grid') {
                galleryGrid.classList.remove('list-view');
                galleryGrid.classList.add('grid-view');
            } else {
                galleryGrid.classList.remove('grid-view');
                galleryGrid.classList.add('list-view');
            }
        });
    });
    
    // Load more functionality
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreArtworks);
    }
    
    function loadMoreArtworks() {
        const userId = loadMoreBtn.getAttribute('data-user');
        const offset = loadMoreBtn.getAttribute('data-offset');
        
        // Show loading state
        loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        loadMoreBtn.disabled = true;
        
        // Fetch more artworks
        fetch(`../controllers/artworks/load_more.php?user_id=${userId}&offset=${offset}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append new artworks to gallery
                    const galleryGrid = document.querySelector('.gallery-grid');
                    
                    data.artworks.forEach(artwork => {
                        const artItem = document.createElement('div');
                        artItem.className = 'art-item';
                        artItem.innerHTML = `
                            <a href="artwork.php?id=${artwork.id}">
                                <img src="${artwork.image_path}" alt="${artwork.title}">
                                <div class="art-item-overlay">
                                    <h4>${artwork.title}</h4>
                                    <div class="art-item-stats">
                                        <span><i class="fas fa-heart"></i> ${artwork.likes_count}</span>
                                        <span><i class="fas fa-comment"></i> ${artwork.comments_count}</span>
                                    </div>
                                </div>
                            </a>
                        `;
                        galleryGrid.appendChild(artItem);
                    });
                    
                    // Update the offset for next load
                    const newOffset = parseInt(offset) + data.artworks.length;
                    loadMoreBtn.setAttribute('data-offset', newOffset);
                    
                    // Hide load more button if no more artworks
                    if (!data.has_more) {
                        loadMoreBtn.parentElement.style.display = 'none';
                    }
                } else {
                    // Show error
                    loadMoreBtn.innerHTML = 'Error loading more artworks';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadMoreBtn.innerHTML = 'Error loading more artworks';
            })
            .finally(() => {
                // Reset button state
                loadMoreBtn.innerHTML = 'Load More';
                loadMoreBtn.disabled = false;
            });
    }
    
    // Profile picture and cover image upload
    const editProfilePicBtn = document.querySelector('.edit-profile-pic');
    const editCoverBtn = document.querySelector('.edit-cover');
    
    if (editProfilePicBtn) {
        editProfilePicBtn.addEventListener('click', () => {
            // Create a file input element
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            
            // Trigger file selection
            fileInput.click();
            
            // Handle file selection
            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    const formData = new FormData();
                    formData.append('profile_picture', fileInput.files[0]);
                    
                    // Upload the file
                    fetch('../controllers/profile/update_profile_picture.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the profile picture
                            document.querySelector('.profile-picture img').src = data.image_path;
                        } else {
                            alert('Failed to update profile picture: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating your profile picture.');
                    });
                }
            });
        });
    }
    
    if (editCoverBtn) {
        editCoverBtn.addEventListener('click', () => {
            // Create a file input element
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            
            // Trigger file selection
            fileInput.click();
            
            // Handle file selection
            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    const formData = new FormData();
                    formData.append('cover_image', fileInput.files[0]);
                    
                    // Upload the file
                    fetch('../controllers/profile/update_cover_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the cover image
                            document.querySelector('.profile-cover img').src = data.image_path;
                        } else {
                            alert('Failed to update cover image: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating your cover image.');
                    });
                }
            });
        });
    }
    
    // Follow/Unfollow functionality
    const followBtn = document.querySelector('.follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', () => {
            const userId = followBtn.getAttribute('data-user-id');
            const isFollowing = followBtn.classList.contains('following');
            
            // Update UI immediately for responsive feel
            if (isFollowing) {
                followBtn.classList.remove('following');
                followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
            } else {
                followBtn.classList.add('following');
                followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
            }
            
            // Send follow/unfollow request
            fetch('../controllers/profile/toggle_follow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: isFollowing ? 'unfollow' : 'follow'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert UI changes if the request failed
                    if (isFollowing) {
                        followBtn.classList.add('following');
                        followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
                    } else {
                        followBtn.classList.remove('following');
                        followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                    }
                    
                    // Show error message
                    alert('Failed to ' + (isFollowing ? 'unfollow' : 'follow') + ' user: ' + data.message);
                } else {
                    // Update follower count
                    const followersCountElem = document.querySelector('.profile-stats .stat-item:nth-child(2) .stat-count');
                    let count = parseInt(followersCountElem.textContent.replace(/,/g, ''));
                    
                    if (isFollowing) {
                        count -= 1;
                    } else {
                        count += 1;
                    }
                    
                    followersCountElem.textContent = count.toLocaleString();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Revert UI changes
                if (isFollowing) {
                    followBtn.classList.add('following');
                    followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
                } else {
                    followBtn.classList.remove('following');
                    followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                }
                
                alert('An error occurred. Please try again later.');
            });
        });
    }
    
    // Edit Profile functionality
    const editProfileBtn = document.querySelector('.edit-profile-btn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', () => {
            window.location.href = 'edit_profile.php';
        });
    }

    // Create Post functionality
    const createPostBtn = document.querySelector('.create-post-btn');
    if (createPostBtn) {
        createPostBtn.addEventListener('click', () => {
            window.location.href = 'createPost.html';
        });
    }
    
    // Message functionality
    const messageBtn = document.querySelector('.message-btn');
    if (messageBtn) {
        messageBtn.addEventListener('click', () => {
            const userId = document.querySelector('.follow-btn').getAttribute('data-user-id');
            window.location.href = `messages.php?user_id=${userId}`;
        });
    }
    
    // Handle sort select change
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const userId = document.querySelector('.load-more-btn')?.getAttribute('data-user') || 
                          window.location.pathname.split('profile.php?id=')[1];
            
            // Reload artworks with new sort
            fetch(`../controllers/artworks/get_artworks.php?user_id=${userId}&sort=${sortSelect.value}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Replace gallery content
                        const galleryGrid = document.querySelector('.gallery-grid');
                        galleryGrid.innerHTML = '';
                        
                        if (data.artworks.length === 0) {
                            galleryGrid.innerHTML = `
                                <div class="no-content">
                                    <p>No artworks posted yet.</p>
                                    ${userId === undefined ? '<a href="upload.php" class="upload-btn"><i class="fas fa-upload"></i> Upload Your First Artwork</a>' : ''}
                                </div>
                            `;
                        } else {
                            data.artworks.forEach(artwork => {
                                const artItem = document.createElement('div');
                                artItem.className = 'art-item';
                                artItem.innerHTML = `
                                    <a href="artwork.php?id=${artwork.id}">
                                        <img src="${artwork.image_path}" alt="${artwork.title}">
                                        <div class="art-item-overlay">
                                            <h4>${artwork.title}</h4>
                                            <div class="art-item-stats">
                                                <span><i class="fas fa-heart"></i> ${artwork.likes_count}</span>
                                                <span><i class="fas fa-comment"></i> ${artwork.comments_count}</span>
                                            </div>
                                        </div>
                                    </a>
                                `;
                                galleryGrid.appendChild(artItem);
                            });
                        }
                        
                        // Update load more button
                        const loadMoreBtn = document.querySelector('.load-more-btn');
                        if (loadMoreBtn) {
                            loadMoreBtn.setAttribute('data-offset', data.artworks.length.toString());
                            loadMoreBtn.parentElement.style.display = data.has_more ? 'block' : 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }
});