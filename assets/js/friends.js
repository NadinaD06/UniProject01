document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    const tabContents = document.querySelectorAll('.tab-content');
    const viewButtons = document.querySelectorAll('.view-toggle .view-btn');
    const userGrid = document.querySelector('.user-grid');
    const sortDropdowns = document.querySelectorAll('select[id^="sort"]');
    const artistSearch = document.getElementById('artistSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const loadMoreBtns = document.querySelectorAll('.load-more-btn');
    
    // Dropdown elements for "More" actions
    const moreButtons = document.querySelectorAll('.more-btn');
    
    // Modal elements
    const addToListModal = document.getElementById('addToListModal');
    const closeModalBtn = document.querySelector('.modal .close');
    const cancelBtn = document.querySelector('.modal .cancel-btn');
    
    // Following/Unfollowing functionality
    const followingButtons = document.querySelectorAll('.following-btn');
    const followButtons = document.querySelectorAll('.follow-btn');
    
    // Friend request buttons
    const acceptButtons = document.querySelectorAll('.accept-btn');
    const declineButtons = document.querySelectorAll('.decline-btn');
    
    // Tab navigation
    if (sidebarLinks) {
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the target tab ID from the href attribute
                const targetId = this.getAttribute('href').substring(1);
                
                // Remove active class from all sidebar links
                sidebarLinks.forEach(link => link.parentElement.classList.remove('active'));
                
                // Add active class to clicked link
                this.parentElement.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(tab => tab.classList.remove('active'));
                
                // Show the target tab content
                const targetTab = document.getElementById(targetId);
                if (targetTab) {
                    targetTab.classList.add('active');
                }
                
                // Update URL hash for bookmarking
                window.location.hash = targetId;
            });
        });
        
        // Check for hash in URL to open the correct tab on page load
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            const targetLink = document.querySelector(`.sidebar-menu a[href="#${hash}"]`);
            if (targetLink) {
                targetLink.click();
            }
        }
    }
    
    // View toggle (grid vs list)
    if (viewButtons) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all view buttons
                viewButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get the view type
                const viewType = this.getAttribute('data-view');
                
                // Update grid/list view
                if (userGrid) {
                    if (viewType === 'grid') {
                        userGrid.classList.remove('list-view');
                        userGrid.classList.add('grid-view');
                    } else {
                        userGrid.classList.remove('grid-view');
                        userGrid.classList.add('list-view');
                    }
                }
            });
        });
    }
    
    // Sort dropdown change handlers
    if (sortDropdowns) {
        sortDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                const sortBy = this.value;
                const tabId = this.id.replace('sort', '').toLowerCase();
                
                // In a real app, this would trigger a fetch to sort the data
                console.log(`Sorting ${tabId} by ${sortBy}`);
                
                // Simulate loading state
                const parentTab = this.closest('.tab-content');
                if (parentTab) {
                    const userContainer = parentTab.querySelector('.user-grid');
                    if (userContainer) {
                        userContainer.classList.add('loading');
                        
                        setTimeout(() => {
                            // Simulate sort by rearranging the DOM elements
                            simulateSort(userContainer, sortBy);
                            userContainer.classList.remove('loading');
                        }, 800);
                    }
                }
            });
        });
    }
    
    // Artist search functionality
    if (artistSearch) {
        artistSearch.addEventListener('input', debounce(function() {
            const searchTerm = this.value.trim().toLowerCase();
            
            // In a real app, this would trigger an API search
            console.log(`Searching for artists: ${searchTerm}`);
            
            if (searchTerm.length > 0) {
                // Simulate search results
                simulateSearch(searchTerm);
            }
        }, 300));
    }
    
    // Category filter functionality
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const category = this.value;
            
            // In a real app, this would filter the results by category
            console.log(`Filtering by category: ${category}`);
            
            // Simulate filtering results
            simulateCategoryFilter(category);
        });
    }
    
    // Load more functionality
    if (loadMoreBtns) {
        loadMoreBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Get the parent tab
                const parentTab = this.closest('.tab-content');
                const userContainer = parentTab ? parentTab.querySelector('.user-grid') : null;
                
                if (userContainer) {
                    // Show loading state
                    this.textContent = 'Loading...';
                    this.disabled = true;
                    
                    // In a real app, this would fetch more users from an API
                    setTimeout(() => {
                        // Simulate loading more users
                        loadMoreUsers(userContainer);
                        
                        // Reset button
                        this.textContent = 'Load More';
                        this.disabled = false;
                    }, 1500);
                }
            });
        });
    }
    
    // Following/Unfollowing buttons
    if (followingButtons) {
        followingButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Toggle following state
                const isFollowing = this.textContent.includes('Following');
                
                if (isFollowing) {
                    // Show confirmation dialog
                    if (confirm('Are you sure you want to unfollow this user?')) {
                        this.innerHTML = 'Follow <i class="fas fa-user-plus"></i>';
                        this.classList.remove('following-btn');
                        this.classList.add('follow-btn');
                        
                        // Show toast notification
                        showToast('You unfollowed this user');
                        
                        // In a real app, this would call an API to unfollow
                        console.log('API call: Unfollow user');
                    }
                } else {
                    this.innerHTML = 'Following <i class="fas fa-check"></i>';
                    this.classList.remove('follow-btn');
                    this.classList.add('following-btn');
                    
                    // Show toast notification
                    showToast('You are now following this user');
                    
                    // In a real app, this would call an API to follow
                    console.log('API call: Follow user');
                }
            });
        });
    }
    
    // Follow buttons in suggestions
    if (followButtons) {
        followButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.innerHTML = 'Following <i class="fas fa-check"></i>';
                this.classList.remove('follow-btn');
                this.classList.add('following-btn');
                
                // Show toast notification
                showToast('You are now following this user');
                
                // In a real app, this would call an API to follow
                console.log('API call: Follow user');
            });
        });
    }
    
    // Friend request actions
    if (acceptButtons) {
        acceptButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const requestCard = this.closest('.request-card');
                const username = requestCard.querySelector('.user-name a').textContent;
                
                // Show confirmation message
                showToast(`You accepted ${username}'s follow request`);
                
                // In a real app, this would call an API to accept the request
                console.log(`API call: Accept follow request from ${username}`);
                
                // Animate and remove the request card
                requestCard.style.opacity = '0.5';
                requestCard.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    requestCard.remove();
                    
                    // Update request count
                    updateRequestCount(-1);
                    
                    // Check if we need to show "no requests" message
                    checkEmptyRequests();
                }, 500);
            });
        });
    }
    
    if (declineButtons) {
        declineButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const requestCard = this.closest('.request-card');
                const username = requestCard.querySelector('.user-name a').textContent;
                
                // Show confirmation message
                showToast(`You declined ${username}'s follow request`);
                
                // In a real app, this would call an API to decline the request
                console.log(`API call: Decline follow request from ${username}`);
                
                // Animate and remove the request card
                requestCard.style.opacity = '0.5';
                requestCard.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    requestCard.remove();
                    
                    // Update request count
                    updateRequestCount(-1);
                    
                    // Check if we need to show "no requests" message
                    checkEmptyRequests();
                }, 500);
            });
        });
    }
    
    // More button dropdowns
    if (moreButtons) {
        moreButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Toggle dropdown visibility
                const dropdown = this.nextElementSibling;
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                
                // Close dropdown when clicking elsewhere
                document.addEventListener('click', function closeDropdown(e) {
                    if (!dropdown.contains(e.target) && e.target !== btn) {
                        dropdown.style.display = 'none';
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            });
        });
        
        // Handle "Add to List" action
        document.addEventListener('click', function(e) {
            if (e.target.textContent === 'Add to List' && addToListModal) {
                const userCard = e.target.closest('.user-card');
                const username = userCard ? userCard.querySelector('.user-name a').textContent : 'User';
                
                // Set modal title to include username
                const modalTitle = addToListModal.querySelector('h2');
                if (modalTitle) {
                    modalTitle.textContent = `Add ${username} to List`;
                }
                
                // Show modal
                addToListModal.style.display = 'block';
            }
        });
    }
    
    // Modal close buttons
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            addToListModal.style.display = 'none';
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            addToListModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === addToListModal) {
            addToListModal.style.display = 'none';
        }
    });
    
    // Helper function: Simulate sorting users
    function simulateSort(container, sortBy) {
        const userCards = Array.from(container.querySelectorAll('.user-card'));
        
        userCards.sort((a, b) => {
            const nameA = a.querySelector('.user-name').textContent.trim();
            const nameB = b.querySelector('.user-name').textContent.trim();
            
            if (sortBy === 'name') {
                return nameA.localeCompare(nameB);
            } else if (sortBy === 'recent') {
                // In a real app, this would use actual timestamps
                // For demo, we'll just use reverse of current order
                return -1;
            } else if (sortBy === 'active') {
                // For demo purposes, use random sort for "recently active"
                return Math.random() - 0.5;
            }
            
            return 0;
        });
        
        // Remove all cards
        userCards.forEach(card => container.removeChild(card));
        
        // Add sorted cards back
        userCards.forEach(card => container.appendChild(card));
    }
    
    // Helper function: Simulate user search
    function simulateSearch(searchTerm) {
        // This would typically do an API call
        console.log(`Searching for "${searchTerm}"...`);
        
        // In a real app, you'd update the UI with search results
    }
    
    // Helper function: Simulate category filter
    function simulateCategoryFilter(category) {
        // This would typically apply a filter
        console.log(`Filtering by category: ${category}`);
        
        // In a real app, you'd update the UI with filtered results
    }
    
    // Helper function: Load more users
    function loadMoreUsers(container) {
        // In a real app, this would fetch data from an API
        // For demo, we'll add some static cards
        
        // Sample users
        const users = [
            {
                name: 'PixelPerfect',
                bio: 'Digital artist specializing in pixel art and retro aesthetics',
                mutuals: 2
            },
            {
                name: 'CanvasCreator',
                bio: 'Traditional painter exploring landscapes and cityscapes',
                mutuals: 8
            },
            {
                name: 'SketchMaster',
                bio: 'Character artist and illustrator, comic book enthusiast',
                mutuals: 0
            },
            {
                name: 'ArtisticVision',
                bio: 'Photography and mixed media projects',
                mutuals: 3
            }
        ];
        
        // Create and append user cards
        users.forEach(user => {
            const userCard = document.createElement('div');
            userCard.className = 'user-card';
            userCard.innerHTML = `
                <div class="user-card-header">
                    <img src="/api/placeholder/80/80" alt="User Avatar" class="user-avatar">
                    <button class="following-btn">Following <i class="fas fa-check"></i></button>
                </div>
                <div class="user-info">
                    <h3 class="user-name"><a href="profile.html">${user.name}</a></h3>
                    <p class="user-bio">${user.bio}</p>
                </div>
                <div class="mutual-info">
                    <span>${user.mutuals} mutual followers</span>
                </div>
                <div class="user-actions">
                    <button class="message-btn"><i class="fas fa-envelope"></i> Message</button>
                    <div class="dropdown">
                        <button class="more-btn"><i class="fas fa-ellipsis-h"></i></button>
                        <div class="dropdown-content">
                            <a href="#">View Profile</a>
                            <a href="#">Add to List</a>
                            <a href="#">Unfollow</a>
                            <a href="#">Mute</a>
                            <a href="#">Block</a>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(userCard);
        });
    }
    
    // Helper function: Update request count
    function updateRequestCount(change) {
        // Update the count in sidebar
        const requestCountElement = document.querySelector('.sidebar-menu li a[href="#requests"] .count');
        if (requestCountElement) {
            const currentCount = parseInt(requestCountElement.textContent);
            const newCount = currentCount + change;
            requestCountElement.textContent = newCount;
            
            // Hide count if zero
            if (newCount <= 0) {
                requestCountElement.style.display = 'none';
            }
        }
        
        // Update the count in tab header
        const tabCountElement = document.querySelector('#requests .tab-header .count');
        if (tabCountElement) {
            const countStr = tabCountElement.textContent;
            const countMatch = countStr.match(/\((\d+)\)/);
            if (countMatch) {
                const currentCount = parseInt(countMatch[1]);
                const newCount = currentCount + change;
                tabCountElement.textContent = `(${newCount})`;
            }
        }
    }
    
    // Helper function: Check if requests list is empty
    function checkEmptyRequests() {
        const requestsList = document.querySelector('.requests-list');
        const requestCards = requestsList ? requestsList.querySelectorAll('.request-card') : [];
        
        if (requestCards.length === 0) {
            // Create and show "no requests" message
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-user-check empty-icon"></i>
                <h3>No Pending Requests</h3>
                <p>You have no pending follow requests at this time.</p>
            `;
            
            requestsList.appendChild(emptyState);
        }
    }
    
    // Helper function: Debounce for search input
    function debounce(func, delay) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
    
    // Toast notification system
    function showToast(message) {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Add show class after a small delay (for animation)
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            
            // Remove from DOM after fade out
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Handle save button in Add to List modal
    const saveListBtn = document.querySelector('.modal .save-btn');
    if (saveListBtn) {
        saveListBtn.addEventListener('click', function() {
            // Get selected lists
            const selectedLists = Array.from(
                document.querySelectorAll('.lists input[type="checkbox"]:checked')
            ).map(input => input.nextElementSibling.textContent);
            
            // Get new list name if provided
            const newListName = document.getElementById('newListName').value.trim();
            const listPrivacy = document.getElementById('listPrivacy').value;
            
            if (newListName) {
                selectedLists.push(newListName);
            }
            
            if (selectedLists.length > 0) {
                // In a real app, this would call an API to add user to lists
                console.log('Adding user to lists:', selectedLists);
                
                // Show success message
                showToast('User added to selected lists');
            } else {
                // Show error if no lists selected
                showToast('Please select at least one list or create a new one');
                return;
            }
            
            // Close the modal
            addToListModal.style.display = 'none';
            
            // Reset form
            document.getElementById('newListName').value = '';
            document.querySelectorAll('.lists input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }
    
    // Handle message buttons
    const messageButtons = document.querySelectorAll('.message-btn');
    if (messageButtons) {
        messageButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const userCard = this.closest('.user-card');
                const username = userCard ? userCard.querySelector('.user-name a').textContent : 'User';
                
                // In a real app, this would open the messaging interface or redirect to messages
                console.log(`Opening message compose to ${username}`);
                
                // Simulate redirect to messages page
                showToast(`Opening conversation with ${username}`);
                setTimeout(() => {
                    window.location.href = `messages.html?user=${encodeURIComponent(username)}`;
                }, 500);
            });
        });
    }
    
    // Initialize view
    // Default to grid view on page load if not already set
    if (userGrid && !userGrid.classList.contains('list-view')) {
        userGrid.classList.add('grid-view');
    }
});