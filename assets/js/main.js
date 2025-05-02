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