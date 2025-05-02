<?php
/**
 * Feed view
 * Displays user's feed with posts
 */
?>

<div class="container mx-auto px-4 py-8">
    <!-- Create Post Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form id="createPostForm" class="space-y-4">
            <div>
                <textarea
                    name="content"
                    rows="3"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="What's on your mind?"
                    required
                ></textarea>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        class="hidden"
                        id="postImage"
                    >
                    <label
                        for="postImage"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Add Image
                    </label>
                </div>
                
                <div class="flex-1">
                    <input
                        type="text"
                        name="location_name"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Add location"
                        id="locationInput"
                    >
                    <input type="hidden" name="location_lat" id="locationLat">
                    <input type="hidden" name="location_lng" id="locationLng">
                </div>
                
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Post
                </button>
            </div>
        </form>
    </div>

    <!-- Posts Feed -->
    <div id="postsFeed" class="space-y-8">
        <?php if (empty($feed['data'])): ?>
            <div class="text-center text-gray-500 py-8">
                No posts to show. Follow some users to see their posts here!
            </div>
        <?php else: ?>
            <?php foreach ($feed['data'] as $post): ?>
                <div class="bg-white rounded-lg shadow-md p-6" data-post-id="<?= $post['id'] ?>">
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

                        <?php if ($post['user_id'] === $_SESSION['user_id']): ?>
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
            <?php if ($feed['last_page'] > 1): ?>
                <div class="flex justify-center mt-8">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($feed['current_page'] > 1): ?>
                            <a
                                href="?page=<?= $feed['current_page'] - 1 ?>"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                            >
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $feed['last_page']; $i++): ?>
                            <a
                                href="?page=<?= $i ?>"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i === $feed['current_page'] ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>"
                            >
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($feed['current_page'] < $feed['last_page']): ?>
                            <a
                                href="?page=<?= $feed['current_page'] + 1 ?>"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Google Places Autocomplete
    const locationInput = document.getElementById('locationInput');
    const locationLat = document.getElementById('locationLat');
    const locationLng = document.getElementById('locationLng');

    if (locationInput) {
        const autocomplete = new google.maps.places.Autocomplete(locationInput);
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (place.geometry) {
                locationLat.value = place.geometry.location.lat();
                locationLng.value = place.geometry.location.lng();
            }
        });
    }

    // Handle post creation
    const createPostForm = document.getElementById('createPostForm');
    if (createPostForm) {
        createPostForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api/posts/create', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload page to show new post
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the post');
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
                    // Clear input and reload comments
                    this.reset();
                    loadComments(postId);
                    
                    // Update comment count
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
</script> 