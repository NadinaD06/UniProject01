<?php
/**
 * Notifications View
 * Displays user notifications
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
                <p class="text-gray-600 mt-1">Stay updated with your latest activities</p>
            </div>

            <div class="divide-y divide-gray-200">
                <?php if (empty($notifications)): ?>
                    <div class="p-6 text-center text-gray-500">
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors duration-200 <?php echo $notification['is_read'] ? '' : 'bg-blue-50'; ?>"
                             data-notification-id="<?php echo $notification['id']; ?>">
                            <div class="flex items-start space-x-4">
                                <!-- Notification Icon -->
                                <div class="flex-shrink-0">
                                    <?php
                                    $iconClass = 'text-gray-400';
                                    switch ($notification['type']) {
                                        case 'message':
                                            $iconClass = 'text-blue-500';
                                            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>';
                                            break;
                                        case 'like':
                                            $iconClass = 'text-red-500';
                                            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>';
                                            break;
                                        case 'comment':
                                            $iconClass = 'text-green-500';
                                            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>';
                                            break;
                                        case 'report':
                                            $iconClass = 'text-yellow-500';
                                            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
                                            break;
                                        default:
                                            $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                                    }
                                    ?>
                                    <div class="<?php echo $iconClass; ?>">
                                        <?php echo $icon; ?>
                                    </div>
                                </div>

                                <!-- Notification Content -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($notification['content']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </p>
                                </div>

                                <!-- Mark as Read Button -->
                                <?php if (!$notification['is_read']): ?>
                                    <button class="text-sm text-blue-600 hover:text-blue-800"
                                            onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        Mark as read
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['last_page'] > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex justify-between items-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="?page=<?php echo $pagination['current_page'] - 1; ?>"
                               class="text-blue-600 hover:text-blue-800">
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Previous</span>
                        <?php endif; ?>

                        <span class="text-gray-600">
                            Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['last_page']; ?>
                        </span>

                        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                            <a href="?page=<?php echo $pagination['current_page'] + 1; ?>"
                               class="text-blue-600 hover:text-blue-800">
                                Next
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Next</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('/api/notifications/mark-as-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_ids: [notificationId]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            // Update UI
            const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notification) {
                notification.classList.remove('bg-blue-50');
                const markAsReadButton = notification.querySelector('button');
                if (markAsReadButton) {
                    markAsReadButton.remove();
                }
            }
            
            // Update notification count in header
            updateNotificationCount();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

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
</script> 