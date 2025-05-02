// Initialize map
function initMap(elementId) {
    const map = L.map(elementId).setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    let marker = null;
    
    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        
        marker = L.marker(e.latlng).addTo(map);
        
        // Reverse geocode to get location name
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('location_name').value = data.display_name;
                document.getElementById('latitude').value = e.latlng.lat;
                document.getElementById('longitude').value = e.latlng.lng;
            });
    });
    
    return map;
}

// Like post
function likePost(postId) {
    fetch('api/like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Toggle comments
function toggleComments(postId) {
    const commentsDiv = document.getElementById(`comments-${postId}`);
    commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
    
    if (commentsDiv.style.display === 'block') {
        loadComments(postId);
    }
}

// Load comments
function loadComments(postId) {
    fetch(`api/get_comments.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            const commentList = document.getElementById(`comment-list-${postId}`);
            commentList.innerHTML = '';
            
            data.forEach(comment => {
                const div = document.createElement('div');
                div.className = 'comment';
                div.innerHTML = `
                    <strong>${comment.username}</strong>
                    <p>${comment.content}</p>
                    <small>${comment.created_at}</small>
                `;
                commentList.appendChild(div);
            });
        });
}

// Submit comment
function submitComment(event, postId) {
    if (event.key === 'Enter') {
        const input = event.target;
        const content = input.value.trim();
        
        if (content) {
            fetch('api/add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&content=${encodeURIComponent(content)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(postId);
                }
            });
        }
    }
}

// Mark notifications as read
function markNotificationsAsRead() {
    fetch('api/mark_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification.unread').forEach(notification => {
                notification.classList.remove('unread');
            });
        }
    });
}

// Send message
function sendMessage(receiverId) {
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (content) {
        fetch('api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `receiver_id=${receiverId}&content=${encodeURIComponent(content)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadMessages(receiverId);
            }
        });
    }
}

// Load messages
function loadMessages(userId) {
    fetch(`api/get_messages.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            const messageList = document.getElementById('message-list');
            messageList.innerHTML = '';
            
            data.forEach(message => {
                const div = document.createElement('div');
                div.className = `message ${message.sender_id === currentUserId ? 'sent' : 'received'}`;
                div.innerHTML = `
                    <strong>${message.username}</strong>
                    <p>${message.content}</p>
                    <small>${message.sent_at}</small>
                `;
                messageList.appendChild(div);
            });
            
            messageList.scrollTop = messageList.scrollHeight;
        });
}

// Main JavaScript file

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Flash message handling
function showFlashMessage(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
}

// CSRF token handling for AJAX requests
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

// Add CSRF token to all AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': getCsrfToken()
    }
});

// Handle AJAX errors
$(document).ajaxError(function(event, jqXHR, settings, error) {
    console.error('AJAX Error:', error);
    showFlashMessage('An error occurred. Please try again.', 'danger');
});

// Form validation
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Add error message if it doesn't exist
            if (!field.nextElementSibling?.classList.contains('invalid-feedback')) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'This field is required';
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Add form validation to all forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
        }
    });
});

// Clear form validation on input
document.querySelectorAll('input, textarea').forEach(field => {
    field.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
});

// Handle file input preview
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = input.parentNode.querySelector('.image-preview');
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        }
    });
});

// Handle infinite scroll
let isLoading = false;
window.addEventListener('scroll', function() {
    if (isLoading) return;
    
    const loadMoreButton = document.querySelector('.load-more');
    if (!loadMoreButton) return;
    
    const rect = loadMoreButton.getBoundingClientRect();
    if (rect.top <= window.innerHeight + 100) {
        isLoading = true;
        loadMoreButton.click();
    }
}); 