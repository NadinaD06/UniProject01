<!-- Navigation Links -->
<div class="flex items-center space-x-4">
    <a href="/" class="text-gray-300 hover:text-white">Home</a>
    <a href="/messages" class="text-gray-300 hover:text-white">Messages</a>
    
    <!-- Notifications -->
    <div class="relative">
        <a href="/notifications" class="text-gray-300 hover:text-white flex items-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span id="notification-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
        </a>
    </div>
    
    <a href="/profile" class="text-gray-300 hover:text-white">Profile</a>
    <a href="/logout" class="text-gray-300 hover:text-white">Logout</a>
</div>

<script>
// Update notification count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationCount();
});

function updateNotificationCount() {
    fetch('/api/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            const countElement = document.getElementById('notification-count');
            if (countElement) {
                countElement.textContent = data.count;
                if (data.count === 0) {
                    countElement.classList.add('hidden');
                } else {
                    countElement.classList.remove('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Poll for new notifications every 30 seconds
setInterval(updateNotificationCount, 30000);
</script> 