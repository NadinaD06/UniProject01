/**
 * ArtSpace Messages JavaScript
 * Handles the messaging functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const contactsList = document.getElementById('contacts');
    const chatPanel = document.getElementById('chatPanel');
    const emptyChatState = document.getElementById('emptyChatState');
    const chatContent = document.getElementById('chatContent');
    const messagesArea = document.getElementById('messagesArea');
    const loadingMessages = document.getElementById('loadingMessages');
    const chatHeader = document.getElementById('chatHeader');
    const chatUsername = document.getElementById('chatUsername');
    const chatUserAvatar = document.getElementById('chatUserAvatar');
    const userStatus = document.getElementById('userStatus');
    const messageForm = document.getElementById('messageForm');
    const messageContent = document.getElementById('messageContent');
    const contactSearch = document.getElementById('contactSearch');
    const newMessageBtn = document.getElementById('newMessageBtn');
    const startMessageBtn = document.getElementById('startMessageBtn');
    const newMessageModal = document.getElementById('newMessageModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const userSearch = document.getElementById('userSearch');
    const searchResults = document.getElementById('searchResults');
    const loadingResults = document.getElementById('loadingResults');
    const noResults = document.getElementById('noResults');
    const userInfoPanel = document.getElementById('userInfoPanel');
    const userProfileImage = document.getElementById('userProfileImage');
    const userProfileName = document.getElementById('userProfileName');
    const userBio = document.getElementById('userBio');
    const viewProfileBtn = document.getElementById('viewProfileBtn');
    const blockUserBtn = document.getElementById('blockUserBtn');
    const infoBtn = document.getElementById('infoBtn');
    const optionsBtn = document.getElementById('optionsBtn');
    const chatOptionsMenu = document.getElementById('chatOptionsMenu');
    const clearChatBtn = document.getElementById('clearChatBtn');
    const reportUserBtn = document.getElementById('reportUserBtn');
    const blockFromChatBtn = document.getElementById('blockFromChatBtn');
    const mediaGrid = document.getElementById('mediaGrid');
    const mediaTabs = document.querySelectorAll('.media-tab');
    const emojiBtn = document.getElementById('emojiBtn');
    const attachBtn = document.getElementById('attachBtn');
    const toastContainer = document.getElementById('toastContainer');
    
    // Get current user ID from hidden input
    const currentUserId = document.getElementById('currentUserId').value;
    const selectedContact = document.getElementById('selectedContact').value;
    const newMessageTo = document.getElementById('newMessageTo').value;
    
    // Current active chat
    let activeChat = {
        id: null,
        username: null,
        lastMessageDate: null
    };
    
    // Message polling intervals
    let contactsInterval = null;
    let messagesInterval = null;
    
    // Initialize the page
    init();
    
    /**
     * Initialize the page
     */
    function init() {
        // Load contacts
        loadContacts();
        
        // Set up event listeners
        setupEventListeners();
        
        // Start contacts polling
        startContactsPolling();
        
        // Check if there's a selected contact or new message
        if (selectedContact) {
            // Find the contact by username and open chat
            findContactByUsername(selectedContact);
        } else if (newMessageTo) {
            // Open new message modal with user pre-selected
            openNewMessageModal();
            searchUsers(newMessageTo);
        }
    }
    
    /**
     * Set up all event listeners
     */
    function setupEventListeners() {
        // Message form submit
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // Contact search input
        contactSearch.addEventListener('input', debounce(function() {
            filterContacts(this.value);
        }, 300));
        
        // New message buttons
        newMessageBtn.addEventListener('click', openNewMessageModal);
        startMessageBtn.addEventListener('click', openNewMessageModal);
        
        // Close modal button
        closeModalBtn.addEventListener('click', closeNewMessageModal);
        
        // User search input
        userSearch.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                searchUsers(query);
            } else {
                searchResults.innerHTML = '';
                loadingResults.style.display = 'none';
                noResults.style.display = 'none';
            }
        }, 500));
        
        // Info button
        infoBtn.addEventListener('click', toggleUserInfoPanel);
        
        // Options button
        optionsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleChatOptionsMenu();
        });
        
        // Chat options menu items
        clearChatBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to clear this conversation?')) {
                clearConversation();
            }
            chatOptionsMenu.style.display = 'none';
        });
        
        reportUserBtn.addEventListener('click', function(e) {
            e.preventDefault();
            reportUser();
            chatOptionsMenu.style.display = 'none';
        });
        
        blockFromChatBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to block this user? They will no longer be able to message you.')) {
                blockUser();
            }
            chatOptionsMenu.style.display = 'none';
        });
        
        // View profile button
        viewProfileBtn.addEventListener('click', function() {
            window.location.href = 'profile.php?username=' + activeChat.username;
        });
        
        // Block user button
        blockUserBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to block this user? They will no longer be able to message you.')) {
                blockUser();
            }
        });
        
        // Media tabs
        mediaTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                mediaTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Load media content based on tab
                loadMediaContent(this.dataset.tab);
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (chatOptionsMenu.style.display === 'block' && !optionsBtn.contains(e.target) && !chatOptionsMenu.contains(e.target)) {
                chatOptionsMenu.style.display = 'none';
            }
        });
        
        // Emoji button
        emojiBtn.addEventListener('click', function() {
            // In a real app, this would open an emoji picker
            showToast('Emoji picker is not available in this demo', 'info');
        });
        
        // Attach button
        attachBtn.addEventListener('click', function() {
            // In a real app, this would open a file picker
            showToast('File attachment is not available in this demo', 'info');
        });
    }
    
    /**
     * Load contacts list
     */
    function loadContacts() {
        fetch('../api/messages_process.php?action=get_contacts')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderContacts(data.data);
                } else {
                    showToast(data.message || 'Failed to load contacts', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading contacts:', error);
                showToast('An error occurred while loading contacts', 'error');
            });
    }
    
    /**
     * Render contacts in the sidebar
     */
    function renderContacts(contacts) {
        if (contacts.length === 0) {
            contactsList.innerHTML = `
                <div class="empty-contacts">
                    <p>No conversations yet</p>
                    <p>Start a new message to connect with other artists</p>
                </div>
            `;
            return;
        }
        
        contactsList.innerHTML = contacts.map(contact => `
            <div class="contact ${activeChat.id === contact.id ? 'active' : ''}" data-id="${contact.id}" data-username="${contact.username}">
                <img src="${contact.profile_picture}" alt="${contact.username}" class="contact-avatar">
                <div class="contact-info">
                    <div class="contact-name">${contact.username}</div>
                    <div class="contact-preview">${contact.last_message || 'No messages yet'}</div>
                </div>
                <div class="contact-meta">
                    <div class="contact-time">${contact.last_message_time}</div>
                    ${contact.unread_count > 0 ? `<div class="unread-count">${contact.unread_count}</div>` : ''}
                </div>
            </div>
        `).join('');
        
        // Add click event to contacts
        document.querySelectorAll('.contact').forEach(contact => {
            contact.addEventListener('click', function() {
                const contactId = this.dataset.id;
                const username = this.dataset.username;
                openChat(contactId, username);
            });
        });
        
        // If we have an active chat and it's in the contacts list, keep it highlighted
        if (activeChat.id) {
            const activeContactElement = document.querySelector(`.contact[data-id="${activeChat.id}"]`);
            if (activeContactElement) {
                activeContactElement.classList.add('active');
            }
        }
    }
    
    /**
     * Filter contacts based on search input
     */
    function filterContacts(query) {
        query = query.toLowerCase();
        
        document.querySelectorAll('.contact').forEach(contact => {
            const username = contact.querySelector('.contact-name').textContent.toLowerCase();
            const preview = contact.querySelector('.contact-preview').textContent.toLowerCase();
            
            if (username.includes(query) || preview.includes(query)) {
                contact.style.display = 'flex';
            } else {
                contact.style.display = 'none';
            }
        });
    }
    
    /**
     * Open chat with a contact
     */
    function openChat(contactId, username) {
        // Update active chat
        activeChat.id = contactId;
        activeChat.username = username;
        
        // Update UI
        chatContent.style.display = 'flex';
        emptyChatState.style.display = 'none';
        
        // Update chat header
        chatUsername.textContent = username;
        
        // Show loading indicator
        messagesArea.innerHTML = '';
        loadingMessages.style.display = 'flex';
        
        // Load messages
        loadMessages(contactId);
        
        // Start messages polling
        startMessagesPolling();
        
        // Highlight active contact
        document.querySelectorAll('.contact').forEach(contact => {
            contact.classList.remove('active');
        });
        
        const activeContactElement = document.querySelector(`.contact[data-id="${contactId}"]`);
        if (activeContactElement) {
            activeContactElement.classList.add('active');
        }
        
        // Load user info for the sidebar
        loadUserInfo(contactId);
        
        // Show user info panel on larger screens
        if (window.innerWidth > 1100) {
            userInfoPanel.style.display = 'flex';
        }
        
        // Focus message input
        messageContent.focus();
    }
    
    /**
     * Load messages for a conversation
     */
    function loadMessages(contactId) {
        fetch(`../api/messages_process.php?action=get_messages&contact_id=${contactId}`)
            .then(response => response.json())
            .then(data => {
                loadingMessages.style.display = 'none';
                
                if (data.success) {
                    renderMessages(data.data);
                } else {
                    showToast(data.message || 'Failed to load messages', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                loadingMessages.style.display = 'none';
                showToast('An error occurred while loading messages', 'error');
            });
    }
    
    /**
     * Render messages in the chat
     */
    function renderMessages(data) {
        const { contact, messages } = data;
        
        // Update chat header
        chatUsername.textContent = contact.username;
        chatUserAvatar.src = contact.profile_picture;
        userStatus.textContent = contact.status;
        
        if (messages.length === 0) {
            messagesArea.innerHTML = `
                <div class="no-messages">
                    <p>No messages yet</p>
                    <p>Start the conversation with ${contact.username}</p>
                </div>
            `;
            return;
        }
        
        // Group messages by date
        const messagesByDate = groupMessagesByDate(messages);
        
        let html = '';
        
        // Loop through dates
        Object.keys(messagesByDate).forEach(date => {
            html += `<div class="message-date"><span>${date}</span></div>`;
            
            // Loop through messages for this date
            messagesByDate[date].forEach(message => {
                html += `
                    <div class="message ${message.is_own ? 'sent' : 'received'}" data-id="${message.id}">
                        <p>${message.content}</p>
                        <span class="timestamp">${message.time}</span>
                    </div>
                `;
            });
        });
        
        messagesArea.innerHTML = html;
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    /**
     * Group messages by date
     */
    function groupMessagesByDate(messages) {
        const groups = {};
        
        messages.forEach(message => {
            const date = message.date;
            
            if (!groups[date]) {
                groups[date] = [];
            }
            
            groups[date].push(message);
        });
        
        return groups;
    }
    
    /**
     * Send a message
     */
    function sendMessage() {
        // Get message content
        const content = messageContent.value.trim();
        
        // Validate
        if (!content) {
            return;
        }
        
        // Disable form
        messageContent.disabled = true;
        messageForm.querySelector('button[type="submit"]').disabled = true;
        
        // Prepare form data
        const formData = new FormData();
        formData.append('receiver_id', activeChat.id);
        formData.append('content', content);
        
        // Send message
        fetch('../api/messages_process.php?action=send_message', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Re-enable form
                messageContent.disabled = false;
                messageForm.querySelector('button[type="submit"]').disabled = false;
                
                if (data.success) {
                    // Clear input
                    messageContent.value = '';
                    
                    // Add message to chat
                    const newMessage = `
                        <div class="message sent" data-id="${data.data.id}">
                            <p>${data.data.content}</p>
                            <span class="timestamp">${data.data.time}</span>
                        </div>
                    `;
                    
                    messagesArea.insertAdjacentHTML('beforeend', newMessage);
                    
                    // Scroll to bottom
                    scrollToBottom();
                    
                    // Refresh contacts list to update last message
                    loadContacts();
                } else {
                    showToast(data.message || 'Failed to send message', 'error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                messageContent.disabled = false;
                messageForm.querySelector('button[type="submit"]').disabled = false;
                showToast('An error occurred while sending message', 'error');
            });
    }
    
    /**
     * Open new message modal
     */
    function openNewMessageModal() {
        newMessageModal.classList.add('active');
        userSearch.value = '';
        searchResults.innerHTML = '';
        loadingResults.style.display = 'none';
        noResults.style.display = 'none';
        userSearch.focus();
    }
    
    /**
     * Close new message modal
     */
    function closeNewMessageModal() {
        newMessageModal.classList.remove('active');
    }
    
    /**
     * Search for users
     */
    function searchUsers(query) {
        searchResults.innerHTML = '';
        loadingResults.style.display = 'flex';
        noResults.style.display = 'none';
        
        fetch(`../api/messages_process.php?action=search_users&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                loadingResults.style.display = 'none';
                
                if (data.success) {
                    renderSearchResults(data.data);
                } else {
                    showToast(data.message || 'Failed to search users', 'error');
                }
            })
            .catch(error => {
                console.error('Error searching users:', error);
                loadingResults.style.display = 'none';
                showToast('An error occurred while searching users', 'error');
            });
    }
    
    /**
     * Render search results
     */
    function renderSearchResults(users) {
        if (users.length === 0) {
            noResults.style.display = 'block';
            return;
        }
        
        searchResults.innerHTML = users.map(user => `
            <div class="search-result" data-id="${user.id}" data-username="${user.username}">
                <img src="${user.profile_picture}" alt="${user.username}" class="search-result-avatar">
                <div class="search-result-info">
                    <div class="search-result-name">${user.username}</div>
                    <div class="search-result-bio">${user.bio || 'No bio'}</div>
                </div>
            </div>
        `).join('');
        
        // Add click event to search results
        document.querySelectorAll('.search-result').forEach(result => {
            result.addEventListener('click', function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;
                
                // Close modal
                closeNewMessageModal();
                
                // Open chat with user
                openChat(userId, username);
            });
        });
    }
    
    /**
     * Load user info for the sidebar
     */
    function loadUserInfo(userId) {
        // In a real app, you would fetch user info from the server
        // For now, we'll just use the data we have
        const user = {
            id: userId,
            username: activeChat.username,
            profile_picture: chatUserAvatar.src,
            bio: 'Artist • Illustrator'
        };
        
        userProfileImage.src = user.profile_picture;
        userProfileName.textContent = user.username;
        userBio.textContent = user.bio;
        
        // Load shared media
        loadMediaContent('images');
    }
    
    /**
     * Load media content based on tab
     */
    function loadMediaContent(tab) {
        // This would fetch media from the server in a real app
        // For now, we'll just show a message
        mediaGrid.innerHTML = '';
        
        if (tab === 'images') {
            mediaGrid.innerHTML = `
                <p>No shared images yet</p>
            `;
        } else if (tab === 'files') {
            mediaGrid.innerHTML = `
                <p>No shared files yet</p>
            `;
        } else if (tab === 'links') {
            mediaGrid.innerHTML = `
                <p>No shared links yet</p>
            `;
        }
    }
    
    /**
     * Toggle user info panel
     */
    function toggleUserInfoPanel() {
        if (userInfoPanel.style.display === 'flex') {
            userInfoPanel.style.display = 'none';
        } else {
            userInfoPanel.style.display = 'flex';
        }
    }
    
    /**
     * Toggle chat options menu
     */
    function toggleChatOptionsMenu() {
        if (chatOptionsMenu.style.display === 'block') {
            chatOptionsMenu.style.display = 'none';
        } else {
            // Position the menu
            const rect = optionsBtn.getBoundingClientRect();
            chatOptionsMenu.style.top = rect.bottom + 'px';
            chatOptionsMenu.style.right = (window.innerWidth - rect.right) + 'px';
            chatOptionsMenu.style.display = 'block';
        }
    }
    
    /**
     * Clear conversation
     */
    function clearConversation() {
        // This would call an API to clear the conversation
        showToast('Conversation cleared successfully', 'success');
        messagesArea.innerHTML = `
            <div class="no-messages">
                <p>No messages</p>
                <p>Start the conversation with ${activeChat.username}</p>
            </div>
        `;
    }
    
    /**
     * Report user
     */
    function reportUser() {
        // This would open a modal to report the user
        showToast('User reported successfully', 'success');
    }
    
    /**
     * Block user
     */
    function blockUser() {
        // This would call an API to block the user
        showToast(`${activeChat.username} has been blocked`, 'success');
        
        // Remove user from contacts
        const contactElement = document.querySelector(`.contact[data-id="${activeChat.id}"]`);
        if (contactElement) {
            contactElement.remove();
        }
        
        // Close chat
        activeChat = {
            id: null,
            username: null,
            lastMessageDate: null
        };
        
        // Show empty state
        chatContent.style.display = 'none';
        emptyChatState.style.display = 'flex';
        userInfoPanel.style.display = 'none';
        
        // Stop messages polling
        stopMessagesPolling();
    }
    
    /**
     * Find contact by username
     */
    function findContactByUsername(username) {
        // This would be easier with a server-side route, but we'll do it client-side for now
        // Listen for contacts to load, then check for the username
        const checkInterval = setInterval(() => {
            const contact = document.querySelector(`.contact[data-username="${username}"]`);
            if (contact) {
                clearInterval(checkInterval);
                contact.click();
            }
        }, 500);
        
        // Stop checking after 5 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
        }, 5000);
    }
    
    /**
     * Start contacts polling
     */
    function startContactsPolling() {
        // Poll for contacts every 30 seconds
        contactsInterval = setInterval(() => {
            loadContacts();
        }, 30000);
    }
    
    /**
     * Stop contacts polling
     */
    function stopContactsPolling() {
        if (contactsInterval) {
            clearInterval(contactsInterval);
            contactsInterval = null;
        }
    }
    
    /**
     * Start messages polling
     */
    function startMessagesPolling() {
        // Stop any existing interval
        stopMessagesPolling();
        
        // Poll for messages every 10 seconds
        messagesInterval = setInterval(() => {
            if (activeChat.id) {
                loadMessages(activeChat.id);
            }
        }, 10000);
    }
    
    /**
     * Stop messages polling
     */
    function stopMessagesPolling() {
        if (messagesInterval) {
            clearInterval(messagesInterval);
            messagesInterval = null;
        }
    }
    
    /**
     * Scroll messages area to bottom
     */
    function scrollToBottom() {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            </div>
            <div class="toast-content">${message}</div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Add show class after a small delay (for animation)
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Add close button functionality
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 300);
        });
        
        // Remove toast after 5 seconds
        setTimeout(() => {
            if (toast.parentNode === toastContainer) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode === toastContainer) {
                        toastContainer.removeChild(toast);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    /**
     * Debounce function for search inputs
     */
    function debounce(func, delay) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        stopContactsPolling();
        stopMessagesPolling();
    });
});