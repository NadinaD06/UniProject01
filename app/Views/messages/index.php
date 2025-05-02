<?php
/**
 * Messages index view
 * Displays list of conversations
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md">
            <!-- Header -->
            <div class="border-b px-6 py-4">
                <h2 class="text-xl font-semibold">Messages</h2>
            </div>

            <!-- Conversations List -->
            <div class="divide-y">
                <?php if (empty($conversations['data'])): ?>
                    <div class="text-center text-gray-500 py-8">
                        No conversations yet. Start a conversation with someone!
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations['data'] as $conversation): ?>
                        <a
                            href="/messages/<?= $conversation['other_user_id'] ?>"
                            class="block hover:bg-gray-50 transition-colors duration-150"
                        >
                            <div class="px-6 py-4 flex items-center">
                                <!-- User Avatar -->
                                <img
                                    src="<?= $conversation['other_image'] ?: '/assets/images/default-avatar.png' ?>"
                                    alt="<?= htmlspecialchars($conversation['other_username']) ?>"
                                    class="w-12 h-12 rounded-full mr-4"
                                >

                                <!-- Conversation Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($conversation['other_username']) ?>
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            <?= date('M j, g:i a', strtotime($conversation['last_message_time'])) ?>
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-500 truncate">
                                        <?= htmlspecialchars($conversation['last_message']) ?>
                                    </p>
                                </div>

                                <!-- Unread Count -->
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <div class="ml-4">
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full">
                                            <?= $conversation['unread_count'] ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($conversations['last_page'] > 1): ?>
                <div class="border-t px-6 py-4">
                    <nav class="flex justify-center">
                        <div class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($conversations['current_page'] > 1): ?>
                                <a
                                    href="?page=<?= $conversations['current_page'] - 1 ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $conversations['last_page']; $i++): ?>
                                <a
                                    href="?page=<?= $i ?>"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i === $conversations['current_page'] ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>"
                                >
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($conversations['current_page'] < $conversations['last_page']): ?>
                                <a
                                    href="?page=<?= $conversations['current_page'] + 1 ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update unread count in navbar
    function updateUnreadCount() {
        fetch('/api/messages/unread-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('unreadMessagesBadge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.classList.toggle('hidden', data.count === 0);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Update unread count every 30 seconds
    updateUnreadCount();
    setInterval(updateUnreadCount, 30000);
});
</script> 