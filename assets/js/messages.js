/**
 * Enhanced ArtSpace Messages JavaScript
 * Implements localStorage-based messaging functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements (using existing elements from your code)
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
    const toastContainer = document.getElementById('toastContainer') || document.createElement('div');
    
    // Get current user ID from hidden input or use a default for demo
    const currentUserId = (document.getElementById('currentUserId') && document.getElementById('currentUserId').value) || '1';
    const selectedContact = (document.getElementById('selectedContact') && document.getElementById('selectedContact').value) || null;
    const newMessageTo = (document.getElementById('newMessageTo') && document.getElementById('newMessageTo').value) || null;
    
    // Ensure toast container exists
    if (!document.getElementById('toastContainer')) {
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Current active chat
    let activeChat = {
        id: null,
        username: null,
        lastMessageDate: null
    };
    
    // Message polling intervals
    let contactsInterval = null;
    let messagesInterval = null;
    
    // Message expiration time (30 days in milliseconds)
    const MESSAGE_EXPIRATION_TIME = 30 * 24 * 60 * 60 * 1000;
    
    // Demo users for the app
    const demoUsers = {
        '1': { 
            id: '1',
            username: 'CurrentUser',
            profile_picture: '/api/placeholder/48/48',
            bio: 'Digital artist and creative professional'
        },
        '2': { 
            id: '2',
            username: 'ArtisticSoul',
            profile_picture: '/api/placeholder/48/48',
            bio: 'Painter and illustrator specializing in watercolors'
        },
        '3': { 
            id: '3',
            username: 'CreativeJourney',
            profile_picture: '/api/placeholder/48/48',
            bio: 'Photography and mixed media artist'
        },
        '4': { 
            id: '4',
            username: 'DigitalArtistry',
            profile_picture: '/api/placeholder/48/48',
            bio: 'Digital art and graphic design professional'
        },
        '5': { 
            id: '5',
            username: 'SketchMaster',
            profile_picture: '/api/placeholder/48/48',
            bio: 'Traditional sketching and drawing artist'
        }
    };
    
    // Sample responses for demo
    const sampleResponses = [
        "Hey, thanks for your message! How are you doing?",
        "That's interesting! What inspired you to work on that piece?",
        "I'd love to see your latest artwork when it's ready!",
        "The art exhibition last week was amazing, did you attend?",
        "I've been experimenting with a new technique lately. We should collaborate sometime!",
        "Your latest post was incredible, I really like your style evolution.",
        "Have you tried the new drawing tablet that just launched? Worth checking out!",
        "I'm setting up a virtual art workshop next weekend. Would you be interested?",
        "What's your opinion on AI-generated art? It's becoming quite a trend.",
        "Just saw a piece that reminded me of your style. It's fascinating how art connects us!"
    ];
    
    // Initialize the page
    init();
    
    /**
     * Initialize the page
     */
    function init() {
        // Initialize contacts in localStorage if they don't exist
        initializeLocalStorage();
        
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
     * Initialize localStorage with demo data if needed
     */
    function initializeLocalStorage() {
        // Check if contacts list exists
        if (!localStorage.getItem('contacts_' + currentUserId)) {
            // Create demo contacts
            const demoContacts = {
                '2': {
                    id: '2',
                    username: 'ArtisticSoul',
                    profile_picture: '/api/placeholder/48/48',
                    last_message: 'Hey, have you seen the latest exhibition?',
                    last_message_time: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 mins ago
                    unread_count: 2
                },
                '3': {
                    id: '3',
                    username: 'CreativeJourney',
                    profile_picture: '/api/placeholder/48/48',
                    last_message: 'Love your latest work!',
                    last_message_time: new Date(Date.now() - 1000 * 60 * 60 * 3).toISOString(), // 3 hours ago
                    unread_count: 0
                },
                '4': {
                    id: '4',
                    username: 'DigitalArtistry',
                    profile_picture: '/api/placeholder/48/48',
                    last_message: 'Let\'s collaborate on that project we discussed',
                    last_message_time: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(), // 1 day ago
                    unread_count: 0
                }
            };
            
            // Store contacts
            storeContactsWithExpiration(demoContacts);
            
            // Create demo messages
            for (const contactId in demoContacts) {
                const demoMessages = createDemoMessages(contactId);
                storeMessagesWithExpiration(contactId, demoMessages);
            }
        }
    }
    
    /**
     * Set up all event listeners
     */
    function setupEventListeners() {
        // Message form submit
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }
        
        // Contact search input
        if (contactSearch) {
            contactSearch.addEventListener('input', debounce(function() {
                filterContacts(this.value);
            }, 300));
        }
        
        // New message buttons
        if (newMessageBtn) {
            newMessageBtn.addEventListener('click', openNewMessageModal);
        }
        
        if (startMessageBtn) {
            startMessageBtn.addEventListener('click', openNewMessageModal);
        }
        
        // Close modal button
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeNewMessageModal);
        }
        
        // User search input
        if (userSearch) {
            userSearch.addEventListener('input', debounce(function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    searchUsers(query);
                } else {
                    clearSearchResults();
                }
            }, 500));
        }
        
        // Info button
        if (infoBtn) {
            infoBtn.addEventListener('click', toggleUserInfoPanel);
        }
        
        // Options button
        if (optionsBtn) {
            optionsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleChatOptionsMenu();
            });
        }
        
        // Chat options menu items
        if (clearChatBtn) {
            clearChatBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear this conversation?')) {
                    clearConversation();
                }
                chatOptionsMenu.style.display = 'none';
            });
        }
        
        if (reportUserBtn) {
            reportUserBtn.addEventListener('click', function(e) {
                e.preventDefault();
                reportUser();
                chatOptionsMenu.style.display = 'none';
            });
        }
        
        if (blockFromChatBtn) {
            blockFromChatBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to block this user? They will no longer be able to message you.')) {
                    blockUser();
                }
                chatOptionsMenu.style.display = 'none';
            });
        }
        
        // View profile button
        if (viewProfileBtn) {
            viewProfileBtn.addEventListener('click', function() {
                window.location.href = 'profile.html?username=' + activeChat.username;
            });
        }
        
        // Block user button
        if (blockUserBtn) {
            blockUserBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to block this user? They will no longer be able to message you.')) {
                    blockUser();
                }
            });
        }
        
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
            if (chatOptionsMenu && chatOptionsMenu.style.display === 'block' && !optionsBtn.contains(e.target) && !chatOptionsMenu.contains(e.target)) {
                chatOptionsMenu.style.display = 'none';
            }
        });
        
        // Emoji button
        if (emojiBtn) {
            emojiBtn.addEventListener('click', function() {
                // In a real app, this would open an emoji picker
                showToast('Emoji picker is not available in this demo', 'info');
            });
        }
        
        // Attach button
        if (attachBtn) {
            attachBtn.addEventListener('click', function() {
                // In a real app, this would open a file picker
                showToast('File attachment is not available in this demo', 'info');
            });
        }
        
        // Add typing indicator
        if (messageContent) {
            messageContent.addEventListener('input', function() {
                // In a real app with WebSockets, you would emit a typing event here
                // For now we'll just show a typing indicator in the UI for demo purposes
                if (activeChat.id) {
                    showTypingIndicator();
                }
            });
        }
    }
    
    /**
     * Store messages with expiration time in localStorage
     */
    function storeMessagesWithExpiration(contactId, messages) {
        const data = {
            messages: messages,
            expiration: Date.now() + MESSAGE_EXPIRATION_TIME
        };
        localStorage.setItem('messages_' + currentUserId + '_' + contactId, JSON.stringify(data));
    }
    
    /**
     * Load messages with expiration check from localStorage
     */
    function loadMessagesWithExpiration(contactId) {
        const stored = JSON.parse(localStorage.getItem('messages_' + currentUserId + '_' + contactId) || '{}');
        
        // Check if data exists and is not expired
        if (stored.messages && stored.expiration && stored.expiration > Date.now()) {
            return stored.messages;
        } else {
            // Either no data or expired data
            localStorage.removeItem('messages_' + currentUserId + '_' + contactId); // Clean up expired data
            return [];
        }
    }
    
    /**
     * Store contacts with expiration time in localStorage
     */
    function storeContactsWithExpiration(contacts) {
        const data = {
            contacts: contacts,
            expiration: Date.now() + MESSAGE_EXPIRATION_TIME
        };
        localStorage.setItem('contacts_' + currentUserId, JSON.stringify(data));
    }
    
    /**
     * Load contacts with expiration check from localStorage
     */
    function loadContactsWithExpiration() {
        const stored = JSON.parse(localStorage.getItem('contacts_' + currentUserId) || '{}');
        
        // Check if data exists and is not expired
        if (stored.contacts && stored.expiration && stored.expiration > Date.now()) {
            return stored.contacts;
        } else {
            // Either no data or expired data
            localStorage.removeItem('contacts_' + currentUserId); // Clean up expired data
            return {};
        }
    }
    
    /**
     * Create demo messages for a conversation
     */
    function createDemoMessages(contactId) {
        const now = Date.now();
        const yesterday = now - (24 * 60 * 60 * 1000);
        
        return [
            {
                id: now - 1000000,
                sender_id: contactId,
                receiver_id: currentUserId,
                content: "Hey there! How's your latest artwork coming along?",
                time: new Date(yesterday).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                date: new Date(yesterday).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            },
            {
                id: now - 900000,
                sender_id: currentUserId,
                receiver_id: contactId,
                content: "It's going well! Working on a new digital piece that I'm excited about.",
                time: new Date(yesterday + 60000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                date: new Date(yesterday + 60000).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            },
            {
                id: now - 800000,
                sender_id: contactId,
                receiver_id: currentUserId,
                content: "That sounds exciting! Can't wait to see it when it's finished.",
                time: new Date(yesterday + 120000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                date: new Date(yesterday + 120000).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            },
            {
                id: now - 700000,
                sender_id: currentUserId,
                receiver_id: contactId,
                content: "Thanks! I'll definitely share it with you. How about your projects?",
                time: new Date(yesterday + 180000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                date: new Date(yesterday + 180000).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            }
        ];
    }
    
    /**
     * Load contacts list
     */
    function loadContacts() {
        if (contactsList) {
            contactsList.innerHTML = '<div class="loading-contacts"><i class="fas fa-spinner fa-spin"></i><p>Loading conversations...</p></div>';
        }
        
        // In a real app, we would fetch from an API here
        // For now, we'll use localStorage
        setTimeout(() => {
            const contacts = loadContactsWithExpiration();
            renderContacts(contacts);
        }, 500); // Simulate network delay
    }
    
    /**
     * Render contacts in the sidebar
     */
    function renderContacts(contacts) {
        if (!contactsList) return;
        
        if (Object.keys(contacts).length === 0) {
            contactsList.innerHTML = `
                <div class="empty-contacts">
                    <p>No conversations yet</p>
                    <p>Start a new message to connect with other artists</p>
                </div>
            `;
            return;
        }
        
        const contactsArray = Object.values(contacts);
        
        // Sort by last message time (most recent first)
        contactsArray.sort((a, b) => {
            const timeA = new Date(a.last_message_time).getTime();
            const timeB = new Date(b.last_message_time).getTime();
            return timeB - timeA;
        });
        
        contactsList.innerHTML = contactsArray.map(contact => {
            // Format time
            const lastMessageTime = new Date(contact.last_message_time);
            const now = new Date();
            let timeDisplay;
            
            const diffMs = now - lastMessageTime;
            const diffMins = Math.round(diffMs / (1000 * 60));
            const diffHours = Math.round(diffMs / (1000 * 60 * 60));
            const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffMins < 60) {
                timeDisplay = diffMins + 'm';
            } else if (diffHours < 24) {
                timeDisplay = diffHours + 'h';
            } else if (diffDays < 7) {
                timeDisplay = diffDays + 'd';
            } else {
                timeDisplay = lastMessageTime.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
            }
            
            return `
                <div class="contact ${activeChat.id === contact.id ? 'active' : ''}" data-id="${contact.id}" data-username="${contact.username}">
                    <img src="${contact.profile_picture}" alt="${contact.username}" class="contact-avatar">
                    <div class="contact-info">
                        <div class="contact-name">${contact.username}</div>
                        <div class="contact-preview">${contact.last_message || 'No messages yet'}</div>
                    </div>
                    <div class="contact-meta">
                        <div class="contact-time">${timeDisplay}</div>
                        ${contact.unread_count > 0 ? `<div class="unread-count">${contact.unread_count}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
        
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
        // Mark messages as read
        markMessagesAsRead(contactId);
        
        // Update active chat
        activeChat.id = contactId;
        activeChat.username = username;
        
        // Update UI
        if (chatContent) chatContent.style.display = 'flex';
        if (emptyChatState) emptyChatState.style.display = 'none';
        
        // Update chat header
        if (chatUsername) chatUsername.textContent = username;
        
        // Show loading indicator
        if (messagesArea) messagesArea.innerHTML = '';
        if (loadingMessages) loadingMessages.style.display = 'flex';
        
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
        if (userInfoPanel && window.innerWidth > 1100) {
            userInfoPanel.style.display = 'flex';
        }
        
        // Focus message input
        if (messageContent) messageContent.focus();
    }
    
    /**
     * Mark messages as read for a contact
     */
    function markMessagesAsRead(contactId) {
        const contacts = loadContactsWithExpiration();
        if (contacts[contactId]) {
            contacts[contactId].unread_count = 0;
            storeContactsWithExpiration(contacts);
            
            // Update the UI
            const contactElement = document.querySelector(`.contact[data-id="${contactId}"]`);
            if (contactElement) {
                const unreadElement = contactElement.querySelector('.unread-count');
                if (unreadElement) {
                    unreadElement.remove();
                }
            }
        }
    }
    
    /**
     * Load messages for a conversation
     */
    function loadMessages(contactId) {
        // In a real app, this would fetch from an API
        // For now, we'll use localStorage
        setTimeout(() => {
            if (loadingMessages) loadingMessages.style.display = 'none';
            
            // Get messages from localStorage
            const messages = loadMessagesWithExpiration(contactId);
            
            // Get contact info
            const contacts = loadContactsWithExpiration();
            const contact = contacts[contactId] || demoUsers[contactId] || {
                id: contactId,
                username: activeChat.username || 'User',
                profile_picture: '/api/placeholder/48/48',
                status: 'Online'
            };
            
            renderMessages({
                contact: {
                    id: contact.id,
                    username: contact.username,
                    profile_picture: contact.profile_picture,
                    status: 'Online' // For demo, let's say everyone is online
                },
                messages: messages
            });
            
        }, 800); // Simulate network delay
    }
    
    /**
     * Render messages in the chat
     */
    function renderMessages(data) {
        if (!messagesArea) return;
        
        const { contact, messages } = data;
        
        // Update chat header
        if (chatUsername) chatUsername.textContent = contact.username;
        if (chatUserAvatar) chatUserAvatar.src = contact.profile_picture;
        if (userStatus) userStatus.textContent = contact.status;
        
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
                // Check if message is from current user
                const isOwn = message.sender_id === currentUserId;
                
                html += `
                    <div class="message ${isOwn ? 'sent' : 'received'}" data-id="${message.id}">
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
            // Extract date from message - either use date field or convert from timestamp
            const date = message.date || new Date(message.timestamp).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            });
            
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
        const sendButton = messageForm.querySelector('button[type="submit"]');
        if (sendButton) sendButton.disabled = true;
        
        // Create new message
        const newMessage = {
            id: Date.now(),
            sender_id: currentUserId,
            receiver_id: activeChat.id,
            content: content,
            time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
            date: new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
        };
        
        // Add message to storage
        const messages = loadMessagesWithExpiration(activeChat.id);
        messages.push(newMessage);
        storeMessagesWithExpiration(activeChat.id, messages);
        
        // Add to UI
        addMessageToUI(newMessage);
        
        // Update contact with last message
        updateContactWithMessage(activeChat.id, newMessage);
        
        // Clear input
        messageContent.value = '';
        
        // Re-enable form
        messageContent.disabled = false;
        if (sendButton) sendButton.disabled = false;
        
        // Simulate response after random delay
        setTimeout(() => {
            simulateResponse(activeChat.id);
        }, 1000 + Math.random() * 2000);
    }
    
    /**
     * Add a message to the UI
     */
    function addMessageToUI(message) {
        if (!messagesArea) return;
        
        // Check if we need to add a new date header
        const messageDate = message.date;
        let dateHeader = messagesArea.querySelector(`.message-date:last-child span`);
        
        if (!dateHeader || dateHeader.textContent !== messageDate) {
            messagesArea.insertAdjacentHTML('beforeend', `
                <div class="message-date"><span>${messageDate}</span></div>
            `);
        }
        
        // Add the message
        const isOwn = message.sender_id === currentUserId;
        const messageHTML = `
            <div class="message ${isOwn ? 'sent' : 'received'}" data-id="${message.id}">
                <p>${message.content}</p>
                <span class="timestamp">${message.time}</span>
            </div>
        `;
        
        messagesArea.insertAdjacentHTML('beforeend', messageHTML);
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    /**
     * Update contact with last message
     */
    function updateContactWithMessage(contactId, message) {
        const contacts = loadContactsWithExpiration();
        
        // Check if contact exists, if not create it
        if (!contacts[contactId]) {
            // Get user info from demo users
            const user = demoUsers[contactId] || {
                id: contactId,
                username: activeChat.username || 'User ' + contactId,
                profile_picture: '/api/placeholder/48/48'
            };
            
            contacts[contactId] = {
                id: contactId,
                username: user.username,
                profile_picture: user.profile_picture,
                last_message: '',
                last_message_time: new Date().toISOString(),
                unread_count: 0
            };
        }
        
        // Update contact with last message
        contacts[contactId].last_message = message.content;
        contacts[contactId].last_message_time = new Date().toISOString();
        
        storeContactsWithExpiration(contacts);
        
        // Refresh contacts list
        loadContacts();
    }
    
    /**
     * Simulate a response from the other user
     */
    function simulateResponse(contactId) {
        // Show typing indicator
        showTypingIndicator();
        
        // Simulate typing delay
        setTimeout(() => {
            // Hide typing indicator
            hideTypingIndicator();
            
            // Get random response
            const responseText = sampleResponses[Math.floor(Math.random() * sampleResponses.length)];
            
            // Create response message
            const responseMessage = {
                id: Date.now(),
                sender_id: contactId,
                receiver_id: currentUserId,
                content: responseText,
                time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
                date: new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})
            };
            
            // Add message to storage
            const messages = loadMessagesWithExpiration(contactId);
            messages.push(responseMessage);
            storeMessagesWithExpiration(contactId, messages);
            
            // Add to UI
            addMessageToUI(responseMessage);
            
            // Update contact with last message
            updateContactWithMessage(contactId, responseMessage);
            
            // Add unread count if chat is not active
            if (!activeChat.id || activeChat.id !== contactId) {
                incrementUnreadCount(contactId);
            }
        }, 1500 + Math.random() * 1500);
    }
    
    /**
     * Show typing indicator in the chat
     */
    function showTypingIndicator() {
        if (!messagesArea) return;
        
        // Remove existing typing indicator
        hideTypingIndicator();
        
        // Add typing indicator
        const typingHTML = `
            <div class="typing-indicator" id="typingIndicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        
        messagesArea.insertAdjacentHTML('beforeend', typingHTML);
        
        // Scroll to bottom
        scrollToBottom();
    }
    
    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    /**
     * Increment unread count for a contact
     */
    function incrementUnreadCount(contactId) {
        const contacts = loadContactsWithExpiration();
        if (contacts[contactId]) {
            contacts[contactId].unread_count = (contacts[contactId].unread_count || 0) + 1;
            storeContactsWithExpiration(contacts);
            
            // Update the UI if contact is visible
            const contactElement = document.querySelector(`.contact[data-id="${contactId}"]`);
            if (contactElement) {
                let unreadElement = contactElement.querySelector('.unread-count');
                
                if (unreadElement) {
                    unreadElement.textContent = contacts[contactId].unread_count;
                } else {
                    const metaElement = contactElement.querySelector('.contact-meta');
                    if (metaElement) {
                        metaElement.insertAdjacentHTML('beforeend', `<div class="unread-count">1</div>`);
                    }
                }
            }
        }
    }
    
    /**
     * Open new message modal
     */
    function openNewMessageModal() {
        if (!newMessageModal) return;
        
        newMessageModal.classList.add('active');
        
        if (userSearch) {
            userSearch.value = '';
            userSearch.focus();
        }
        
        if (searchResults) {
            searchResults.innerHTML = '';
        }
        
        if (loadingResults) {
            loadingResults.style.display = 'none';
        }
        
        if (noResults) {
            noResults.style.display = 'none';
        }
    }
    
    /**
     * Close new message modal
     */
    function closeNewMessageModal() {
        if (!newMessageModal) return;
        newMessageModal.classList.remove('active');
    }
    
    /**
     * Search for users
     */
    function searchUsers(query) {
        if (!searchResults || !loadingResults || !noResults) return;
        
        // Show loading state
        searchResults.innerHTML = '';
        loadingResults.style.display = 'flex';
        noResults.style.display = 'none';
        
        // Simulate search delay
        setTimeout(() => {
            // Hide loading
            loadingResults.style.display = 'none';
            
            // Filter demo users based on query
            const filteredUsers = Object.values(demoUsers).filter(user => {
                return user.id !== currentUserId && 
                       (user.username.toLowerCase().includes(query.toLowerCase()) || 
                        (user.bio && user.bio.toLowerCase().includes(query.toLowerCase())));
            });
            
            if (filteredUsers.length === 0) {
                noResults.style.display = 'block';
                return;
            }
            
            // Render results
            searchResults.innerHTML = filteredUsers.map(user => `
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
                    
                    // Open chat with user or create a new conversation
                    openOrCreateChat(userId, username);
                });
            });
        }, 700);
    }
    
    /**
     * Clear search results
     */
    function clearSearchResults() {
        if (!searchResults || !loadingResults || !noResults) return;
        
        searchResults.innerHTML = '';
        loadingResults.style.display = 'none';
        noResults.style.display = 'none';
    }
    
    /**
     * Open existing chat or create a new one
     */
    function openOrCreateChat(userId, username) {
        // Check if conversation already exists
        const contacts = loadContactsWithExpiration();
        
        if (contacts[userId]) {
            // Open existing conversation
            openChat(userId, username);
        } else {
            // Create new contact
            const newContact = {
                id: userId,
                username: username,
                profile_picture: demoUsers[userId] ? demoUsers[userId].profile_picture : '/api/placeholder/48/48',
                last_message: 'No messages yet',
                last_message_time: new Date().toISOString(),
                unread_count: 0
            };
            
            contacts[userId] = newContact;
            storeContactsWithExpiration(contacts);
            
            // Create empty message array
            storeMessagesWithExpiration(userId, []);
            
            // Refresh contacts and open chat
            loadContacts();
            openChat(userId, username);
        }
    }
    
    /**
     * Load user info for the sidebar
     */
    function loadUserInfo(userId) {
        if (!userInfoPanel) return;
        
        // Get user info
        const user = demoUsers[userId] || {
            id: userId,
            username: activeChat.username,
            profile_picture: '/api/placeholder/100/100',
            bio: 'Artist'
        };
        
        // Update UI
        if (userProfileImage) userProfileImage.src = user.profile_picture;
        if (userProfileName) userProfileName.textContent = user.username;
        if (userBio) userBio.textContent = user.bio;
        
        // Load shared media
        loadMediaContent('images');
    }
    
    /**
     * Load media content based on tab
     */
    function loadMediaContent(tab) {
        if (!mediaGrid) return;
        
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
        if (!userInfoPanel) return;
        
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
        if (!chatOptionsMenu || !optionsBtn) return;
        
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
        if (!activeChat.id || !messagesArea) return;
        
        // Remove messages from storage
        storeMessagesWithExpiration(activeChat.id, []);
        
        // Update UI
        messagesArea.innerHTML = `
            <div class="no-messages">
                <p>No messages</p>
                <p>Start the conversation with ${activeChat.username}</p>
            </div>
        `;
        
        // Update contact
        const contacts = loadContactsWithExpiration();
        if (contacts[activeChat.id]) {
            contacts[activeChat.id].last_message = 'No messages yet';
            storeContactsWithExpiration(contacts);
        }
        
        // Refresh contacts list
        loadContacts();
        
        // Show toast
        showToast('Conversation cleared successfully', 'success');
    }
    
    /**
     * Report user
     */
    function reportUser() {
        // This would call an API in a real app
        showToast(`${activeChat.username} has been reported`, 'success');
    }
    
    /**
     * Block user
     */
    function blockUser() {
        if (!activeChat.id) return;
        
        // Remove from contacts
        const contacts = loadContactsWithExpiration();
        if (contacts[activeChat.id]) {
            delete contacts[activeChat.id];
            storeContactsWithExpiration(contacts);
        }
        
        // Remove messages
        localStorage.removeItem('messages_' + currentUserId + '_' + activeChat.id);
        
        // Close chat
        closeChat();
        
        // Refresh contacts list
        loadContacts();
        
        // Show toast
        showToast(`${activeChat.username} has been blocked`, 'success');
    }
    
    /**
     * Close the active chat
     */
    function closeChat() {
        // Reset active chat
        activeChat = {
            id: null,
            username: null,
            lastMessageDate: null
        };
        
        // Update UI
        if (chatContent) chatContent.style.display = 'none';
        if (emptyChatState) emptyChatState.style.display = 'flex';
        if (userInfoPanel) userInfoPanel.style.display = 'none';
        
        // Remove active class from contacts
        document.querySelectorAll('.contact').forEach(contact => {
            contact.classList.remove('active');
        });
        
        // Stop polling
        stopMessagesPolling();
    }
    
    /**
     * Find contact by username
     */
    function findContactByUsername(username) {
        const contacts = loadContactsWithExpiration();
        
        // Find contact by username
        const contactId = Object.keys(contacts).find(id => 
            contacts[id].username.toLowerCase() === username.toLowerCase()
        );
        
        if (contactId) {
            // Open chat with this contact
            openChat(contactId, contacts[contactId].username);
        } else {
            // Check demo users
            const demoUser = Object.values(demoUsers).find(user => 
                user.username.toLowerCase() === username.toLowerCase() && user.id !== currentUserId
            );
            
            if (demoUser) {
                // Create new conversation with this user
                openOrCreateChat(demoUser.id, demoUser.username);
            } else {
                showToast(`User ${username} not found`, 'error');
            }
        }
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
                // In a real app with WebSockets, this would be unnecessary
                // For our localStorage demo, this makes sure we display any messages
                // that might have been added from another tab
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
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
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
                if (toast.parentNode === toastContainer) {
                    toastContainer.removeChild(toast);
                }
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