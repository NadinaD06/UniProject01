<?php
/**
 * User profile view
 * Displays user information and their posts
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Profile Header -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="relative">
                <!-- Cover Image -->
                <div class="h-48 bg-gray-200 rounded-t-lg">
                    <?php if ($user['cover_image']): ?>
                        <img
                            src="/uploads/<?= $user['cover_image'] ?>"
                            alt="Cover image"
                            class="w-full h-full object-cover rounded-t-lg"
                        >
                    <?php endif; ?>
                </div>

                <!-- Profile Image -->
                <div class="absolute -bottom-16 left-8">
                    <img
                        src="<?= $user['profile_image'] ?: '/assets/images/default-avatar.png' ?>"
                        alt="<?= htmlspecialchars($user['username']) ?>"
                        class="w-32 h-32 rounded-full border-4 border-white"
                    >
                </div>

                <!-- Action Buttons -->
                <div class="absolute bottom-4 right-8 flex space-x-4">
                    <?php if ($user['id'] === $currentUser['id']): ?>
                        <button
                            onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Edit Profile
                        </button>
                    <?php else: ?>
                        <?php if ($isBlocked): ?>
                            <button
                                onclick="unblockUser(<?= $user['id'] ?>)"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Unblock
                            </button>
                        <?php else: ?>
                            <button
                                onclick="blockUser(<?= $user['id'] ?>)"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Block
                            </button>
                            <button
                                onclick="reportUser(<?= $user['id'] ?>)"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Report
                            </button>
                            <a
                                href="/messages/<?= $user['id'] ?>"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Message
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="pt-20 px-8 pb-8">
                <h1 class="text-2xl font-bold mb-2">
                    <?= htmlspecialchars($user['username']) ?>
                </h1>
                <?php if ($user['bio']): ?>
                    <p class="text-gray-600 mb-4">
                        <?= nl2br(htmlspecialchars($user['bio'])) ?>
                    </p>
                <?php endif; ?>
                <div class="flex items-center text-gray-500 text-sm">
                    <span class="mr-4">
                        <strong><?= $stats['posts'] ?></strong> posts
                    </span>
                    <span class="mr-4">
                        <strong><?= $stats['followers'] ?></strong> followers
                    </span>
                    <span>
                        <strong><?= $stats['following'] ?></strong> following
                    </span>
                </div>
            </div>
        </div>

        <!-- User's Posts -->
        <div class="space-y-8">
            <?php if (empty($posts['data'])): ?>
                <div class="text-center text-gray-500 py-8 bg-white rounded-lg shadow-md">
                    No posts yet.
                </div>
            <?php else: ?>
                <?php foreach ($posts['data'] as $post): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <!-- Post Header -->
                        <div class="flex items-center mb-4">
                            <img
                                src="<?= $post['profile_image'] ?: '/assets/images/default-avatar.png' ?>"
                                alt="<?= htmlspecialchars($post['username']) ?>"
                                class="w-10 h-10 rounded-full mr-3"
                            >
                            <div>
                                <h3 class="font-semibold"><?= htmlspecialchars($post['username']) ?></h3>
                                <p class="text-sm text-gray-500">
                                    <?= date('F j, Y g:i a', strtotime($post['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Post Content -->
                        <div class="mb-4">
                            <p class="text-gray-800"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img
                                    src="/uploads/<?= $post['image_path'] ?>"
                                    alt="Post image"
                                    class="mt-4 rounded-lg max-h-96 w-full object-cover"
                                >
                            <?php endif; ?>

                            <?php if ($post['location_name']): ?>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500">
                                        <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <?= htmlspecialchars($post['location_name']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Post Actions -->
                        <div class="flex items-center space-x-6 border-t border-b py-3">
                            <button
                                class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 focus:outline-none like-button <?= $post['is_liked'] ? 'text-blue-500' : '' ?>"
                                data-post-id="<?= $post['id'] ?>"
                            >
                                <svg class="w-5 h-5" fill="<?= $post['is_liked'] ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span class="like-count"><?= $post['like_count'] ?></span>
                            </button>

                            <button
                                class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 focus:outline-none comment-button"
                                data-post-id="<?= $post['id'] ?>"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span class="comment-count"><?= $post['comment_count'] ?></span>
                            </button>

                            <?php if ($post['user_id'] === $currentUser['id']): ?>
                                <button
                                    class="flex items-center space-x-2 text-gray-500 hover:text-red-500 focus:outline-none delete-button"
                                    data-post-id="<?= $post['id'] ?>"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Comments Section -->
                        <div class="comments-section mt-4 hidden">
                            <div class="comments-list space-y-4"></div>
                            
                            <form class="comment-form mt-4">
                                <div class="flex space-x-2">
                                    <input
                                        type="text"
                                        name="content"
                                        class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Write a comment..."
                                        required
                                    >
                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        Comment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($posts['last_page'] > 1): ?>
                    <div class="flex justify-center mt-8">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($posts['current_page'] > 1): ?>
                                <a
                                    href="?page=<?= $posts['current_page'] - 1 ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $posts['last_page']; $i++): ?>
                                <a
                                    href="?page=<?= $i ?>"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i === $posts['current_page'] ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>"
                                >
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($posts['current_page'] < $posts['last_page']): ?>
                                <a
                                    href="?page=<?= $posts['current_page'] + 1 ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Next
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Edit Profile</h3>
            </div>
            
            <form id="editProfileForm" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bio</label>
                        <textarea
                            name="bio"
                            rows="3"
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Profile Image</label>
                        <input
                            type="file"
                            name="profile_image"
                            accept="image/*"
                            class="mt-1 block w-full"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                        <input
                            type="file"
                            name="cover_image"
                            accept="image/*"
                            class="mt-1 block w-full"
                        >
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        onclick="document.getElementById('editProfileModal').classList.add('hidden')"
                        class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle profile edit form submission
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api/users/profile', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating profile');
            }
        });
    }

    // Handle likes
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const isLiked = this.classList.contains('text-blue-500');
            
            try {
                const response = await fetch('/api/posts/' + (isLiked ? 'unlike' : 'like'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ post_id: postId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.classList.toggle('text-blue-500');
                    this.querySelector('.like-count').textContent = data.like_count;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Handle comments
    document.querySelectorAll('.comment-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const commentsSection = this.closest('.post').querySelector('.comments-section');
            commentsSection.classList.toggle('hidden');
            
            if (!commentsSection.classList.contains('hidden')) {
                loadComments(postId);
            }
        });
    });

    // Handle comment submission
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const postId = this.closest('.post').dataset.postId;
            const content = this.querySelector('input[name="content"]').value;
            
            try {
                const response = await fetch('/api/posts/comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: content
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.reset();
                    loadComments(postId);
                    
                    const countElement = this.closest('.post').querySelector('.comment-count');
                    countElement.textContent = parseInt(countElement.textContent) + 1;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Handle post deletion
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to delete this post?')) {
                return;
            }
            
            const postId = this.dataset.postId;
            
            try {
                const response = await fetch('/api/posts/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ post_id: postId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.closest('.post').remove();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Function to load comments
    async function loadComments(postId) {
        try {
            const response = await fetch(`/api/posts/${postId}/comments`);
            const data = await response.json();
            
            const commentsList = document.querySelector(`[data-post-id="${postId}"] .comments-list`);
            commentsList.innerHTML = '';
            
            data.data.forEach(comment => {
                const commentElement = document.createElement('div');
                commentElement.className = 'flex items-start space-x-3';
                commentElement.innerHTML = `
                    <img
                        src="${comment.profile_image || '/assets/images/default-avatar.png'}"
                        alt="${comment.username}"
                        class="w-8 h-8 rounded-full"
                    >
                    <div class="flex-1 bg-gray-100 rounded-lg px-4 py-2">
                        <div class="font-semibold">${comment.username}</div>
                        <p>${comment.content}</p>
                        <div class="text-xs text-gray-500 mt-1">
                            ${new Date(comment.created_at).toLocaleString()}
                        </div>
                    </div>
                `;
                commentsList.appendChild(commentElement);
            });
        } catch (error) {
            console.error('Error:', error);
        }
    }
});

// Block user
async function blockUser(userId) {
    if (!confirm('Are you sure you want to block this user?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/users/block', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to block user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while blocking user');
    }
}

// Unblock user
async function unblockUser(userId) {
    if (!confirm('Are you sure you want to unblock this user?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/users/unblock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to unblock user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while unblocking user');
    }
}

// Report user
async function reportUser(userId) {
    const reason = prompt('Please enter the reason for reporting this user:');
    if (!reason) {
        return;
    }
    
    try {
        const response = await fetch('/api/users/report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('User has been reported successfully');
        } else {
            alert(data.error || 'Failed to report user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while reporting user');
    }
}
</script> 