/**
 * ArtSpace New Message JavaScript
 * Handles the new message creation functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const newMessageForm = document.getElementById('newMessageForm');
    const recipientInput = document.getElementById('recipient');
    const recipientId = document.getElementById('recipientId');
    const recipientSuggestions = document.getElementById('recipientSuggestions');
    const messageContent = document.getElementById('messageContent');
    const sendBtn = document.getElementById('sendBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const attachmentBtn = document.querySelector('.attachment-btn');
    const emojiBtn = document.querySelector('.emoji-btn');
    const toastContainer = document.getElementById('toastContainer');
    const userSuggestions = document.querySelectorAll('.user-suggestion');
    const selectedRecipientContainer = document.querySelector('.selected-recipient');
    const removeRecipientBtn = document.querySelector('.remove-recipient');
    
    // Initialize the page
    init();
    
    /**
     * Initialize the page
     */
    function init() {
        // Set up event listeners
        setupEventListeners();
        
        // Focus on the recipient input if not already selected
        if (recipientInput && !selectedRecipientContainer) {
            recipientInput.focus();
        } else if (messageContent) {
            messageContent.focus();
        }
    }
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Form submission
        if (newMessageForm) {
            newMessageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }
        
        // Recipient input
        if (recipientInput) {
            recipientInput.addEventListener('input', debounce(function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    searchUsers(query);
                } else {
                    clearSuggestions();
                }
            }, 300));
            
            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!recipientInput.contains(e.target) && !recipientSuggestions.contains(e.target)) {
                    clearSuggestions();
                }
            });
        }
        
        // Cancel button
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Confirm if there's content in the form
                if (messageContent.value.trim() !== '') {
                    if (confirm('Are you sure you want to discard this message?')) {
                        goToMessages();
                    }
                } else {
                    goToMessages();
                }
            });
        }
        
        // Attachment button
        if (attachmentBtn) {
            attachmentBtn.addEventListener('click', function() {
                // In a real app, this would open a file picker
                showToast('File attachment is not available in this demo', 'info');
            });
        }
        
        // Emoji button
        if (emojiBtn) {
            emojiBtn.addEventListener('click', function() {
                // In a real app, this would open an emoji picker
                showToast('Emoji picker is not available in this demo', 'info');
            });
        }
        
        // User suggestion select buttons
        userSuggestions.forEach(suggestion => {
            const selectBtn = suggestion.querySelector('.select-user-btn');
            if (selectBtn) {
                selectBtn.addEventListener('click', function() {
                    const userId = suggestion.dataset.id;
                    const username = suggestion.dataset.username;
                    selectRecipient(userId, username, suggestion.querySelector('.user-avatar').src);
                });
            }
            
            // Also make the entire suggestion clickable
            suggestion.addEventListener('click', function(e) {
                if (!e.target.classList.contains('select-user-btn') && !e.target.closest('.select-user-btn')) {
                    const userId = this.dataset.id;
                    const username = this.dataset.username;
                    selectRecipient(userId, username, this.querySelector('.user-avatar').src);
                }
            });
        });
        
        // Remove recipient button
        if (removeRecipientBtn) {
            removeRecipientBtn.addEventListener('click', function() {
                removeRecipient();
            });
        }
    }
    
    /**
     * Search for users based on input query
     */
    function searchUsers(query) {
        // Show loading state
        recipientSuggestions.innerHTML = '<div class="loading-suggestions"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        recipientSuggestions.style.display = 'block';
        
        // Fetch users from API
        fetch(`../api/messages_process.php?action=search_users&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    renderUserSuggestions(data.data);
                } else {
                    recipientSuggestions.innerHTML = '<div class="no-suggestions">No users found</div>';
                }
            })
            .catch(error => {
                console.error('Error searching users:', error);
                recipientSuggestions.innerHTML = '<div class="no-suggestions">An error occurred</div>';
            });
    }
    
    /**
     * Render user search suggestions
     */
    function renderUserSuggestions(users) {
        const suggestions = users.map(user => `
            <div class="user-suggestion-item" data-id="${user.id}" data-username="${user.username}">
                <img src="${user.profile_picture}" alt="${user.username}" class="user-avatar">
                <div class="user-info">
                    <span class="user-name">${user.username}</span>
                    <span class="user-bio">${user.bio || ''}</span>
                </div>
            </div>
        `).join('');
        
        recipientSuggestions.innerHTML = suggestions;
        
        // Add click event to suggestions
        document.querySelectorAll('.user-suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;
                selectRecipient(userId, username, this.querySelector('.user-avatar').src);
            });
        });
    }
    
    /**
     * Clear user suggestions
     */
    function clearSuggestions() {
        recipientSuggestions.innerHTML = '';
        recipientSuggestions.style.display = 'none';
    }
    
    /**
     * Select a recipient for the message
     */
    function selectRecipient(userId, username, avatarSrc) {
        // Set hidden input value
        recipientId.value = userId;
        
        // Create selected recipient element if it doesn't exist
        if (!selectedRecipientContainer) {
            const recipientContainer = document.querySelector('.recipient-input-container');
            
            // Remove the input field
            if (recipientInput) {
                recipientInput.style.display = 'none';
            }
            
            // Create selected recipient element
            const selectedRecipient = document.createElement('div');
            selectedRecipient.className = 'selected-recipient';
            selectedRecipient.dataset.id = userId;
            selectedRecipient.innerHTML = `
                <img src="${avatarSrc}" alt="${username}" class="recipient-avatar">
                <span class="recipient-username">${username}</span>
                <button type="button" class="remove-recipient"><i class="fas fa-times"></i></button>
            `;
            
            // Add to container
            recipientContainer.prepend(selectedRecipient);
            
            // Add remove button click event
            selectedRecipient.querySelector('.remove-recipient').addEventListener('click', function() {
                removeRecipient();
            });
            
            // Clear suggestions
            clearSuggestions();
            
            // Focus on message content
            messageContent.focus();
        }
    }
    
    /**
     * Remove the selected recipient
     */
    function removeRecipient() {
        // Clear hidden input value
        recipientId.value = '';
        
        // Get the container
        const recipientContainer = document.querySelector('.recipient-input-container');
        const selectedRecipient = document.querySelector('.selected-recipient');
        
        if (selectedRecipient) {
            // Remove the selected recipient element
            recipientContainer.removeChild(selectedRecipient);
            
            // Show the input field again
            if (recipientInput) {
                recipientInput.style.display = 'block';
                recipientInput.value = '';
                recipientInput.focus();
            } else {
                // Create the input field if it doesn't exist
                const input = document.createElement('input');
                input.type = 'text';
                input.id = 'recipient';
                input.name = 'recipient';
                input.placeholder = 'Search for a user...';
                input.autocomplete = 'off';
                
                // Add to container
                recipientContainer.appendChild(input);
                
                // Add event listener
                input.addEventListener('input', debounce(function() {
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        searchUsers(query);
                    } else {
                        clearSuggestions();
                    }
                }, 300));
                
                input.focus();
            }
        }
    }
    
    /**
     * Send the message
     */
    function sendMessage() {
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Get form values
        const recipient = recipientId.value;
        const content = messageContent.value.trim();
        
        // Disable form
        toggleFormDisabled(true);
        
        // Create form data
        const formData = new FormData();
        formData.append('receiver_id', recipient);
        formData.append('content', content);
        
        // Send message
        fetch('../api/messages_process.php?action=send_message', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                toggleFormDisabled(false);
                
                if (data.success) {
                    showToast('Message sent successfully', 'success');
                    
                    // Redirect to messages page
                    setTimeout(() => {
                        window.location.href = 'messages.php?user=' + recipientId.value;
                    }, 1000);
                } else {
                    showToast(data.message || 'Failed to send message', 'error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                toggleFormDisabled(false);
                showToast('An error occurred while sending message', 'error');
            });
    }
    
    /**
     * Validate the form before submission
     */
    function validateForm() {
        // Check if recipient is selected
        if (!recipientId.value) {
            showToast('Please select a recipient', 'error');
            return false;
        }
        
        // Check if message content is entered
        if (!messageContent.value.trim()) {
            showToast('Please enter a message', 'error');
            messageContent.focus();
            return false;
        }
        
        return true;
    }
    
    /**
     * Toggle form disabled state
     */
    function toggleFormDisabled(disabled) {
        messageContent.disabled = disabled;
        sendBtn.disabled = disabled;
        
        if (disabled) {
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        } else {
            sendBtn.innerHTML = 'Send Message';
        }
    }
    
    /**
     * Go to messages page
     */
    function goToMessages() {
        window.location.href = 'messages.php';
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
});