<?php
/**
 * Post View
 * Displays a single post with likes and comments
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Post Header -->
            <div class="p-4 border-b">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo $post['profile_image'] ?: '/assets/images/default-avatar.png'; ?>" 
                         alt="<?php echo htmlspecialchars($post['username']); ?>" 
                         class="w-10 h-10 rounded-full">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            <?php echo htmlspecialchars($post['username']); ?>
                        </h2>
                        <p class="text-sm text-gray-500">
                            <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Post Content -->
            <div class="p-4">
                <p class="text-gray-800 mb-4"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                
                <?php if ($post['image']): ?>
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                         alt="Post image" 
                         class="w-full rounded-lg mb-4">
                <?php endif; ?>

                <?php if ($post['location_name']): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?php echo htmlspecialchars($post['location_name']); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Post Actions -->
            <div class="px-4 py-2 border-t border-b">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Like Button -->
                        <button onclick="toggleLike(<?php echo $post['id']; ?>)"
                                class="flex items-center space-x-1 text-gray-600 hover:text-red-500 transition-colors duration-200">
                            <svg class="w-5 h-5 <?php echo $hasLiked ? 'text-red-500' : ''; ?>" fill="<?php echo $hasLiked ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span id="like-count-<?php echo $post['id']; ?>"><?php echo $likeCount; ?></span>
                        </button>

                        <!-- Comment Button -->
                        <button onclick="focusComment(<?php echo $post['id']; ?>)"
                                class="flex items-center space-x-1 text-gray-600 hover:text-blue-500 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <span id="comment-count-<?php echo $post['id']; ?>"><?php echo $commentCount; ?></span>
                        </button>
                    </div>

                    <?php if ($post['user_id'] === $_SESSION['user_id']): ?>
                        <!-- Delete Button -->
                        <button onclick="deletePost(<?php echo $post['id']; ?>)"
                                class="text-gray-600 hover:text-red-500 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="p-4">
                <!-- Comment Form -->
                <form onsubmit="submitComment(event, <?php echo $post['id']; ?>)" class="mb-4">
                    <div class="flex items-center space-x-2">
                        <img src="<?php echo $_SESSION['profile_image'] ?: '/assets/images/default-avatar.png'; ?>" 
                             alt="Your profile" 
                             class="w-8 h-8 rounded-full">
                        <input type="text" 
                               id="comment-input-<?php echo $post['id']; ?>"
                               placeholder="Write a comment..." 
                               class="flex-1 border rounded-full px-4 py-2 focus:outline-none focus:border-blue-500">
                        <button type="submit" 
                                class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600 transition-colors duration-200">
                            Post
                        </button>
                    </div>
                </form>

                <!-- Comments List -->
                <div id="comments-<?php echo $post['id']; ?>" class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="flex items-start space-x-2">
                            <img src="<?php echo $comment['profile_image'] ?: '/assets/images/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($comment['username']); ?>" 
                                 class="w-8 h-8 rounded-full">
                            <div class="flex-1 bg-gray-100 rounded-lg px-4 py-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($comment['username']); ?>
                                    </h4>
                                    <?php if ($comment['user_id'] === $_SESSION['user_id']): ?>
                                        <button onclick="deleteComment(<?php echo $comment['id']; ?>)"
                                                class="text-gray-500 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($commentCount > count($comments)): ?>
                    <button onclick="loadMoreComments(<?php echo $post['id']; ?>)"
                            class="text-blue-500 hover:text-blue-600 mt-4">
                        Load more comments
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

function toggleLike(postId) {
    fetch('/api/posts/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.like_count !== undefined) {
            document.getElementById(`like-count-${postId}`).textContent = data.like_count;
            const likeButton = document.querySelector(`[onclick="toggleLike(${postId})"] svg`);
            if (likeButton.classList.contains('text-red-500')) {
                likeButton.classList.remove('text-red-500');
                likeButton.setAttribute('fill', 'none');
            } else {
                likeButton.classList.add('text-red-500');
                likeButton.setAttribute('fill', 'currentColor');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function focusComment(postId) {
    document.getElementById(`comment-input-${postId}`).focus();
}

function submitComment(event, postId) {
    event.preventDefault();
    const input = document.getElementById(`comment-input-${postId}`);
    const content = input.value.trim();

    if (!content) return;

    fetch('/api/posts/comment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.comment_count !== undefined) {
            document.getElementById(`comment-count-${postId}`).textContent = data.comment_count;
            input.value = '';
            loadComments(postId, 1, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function loadComments(postId, page = 1, replace = false) {
    fetch(`/api/posts/${postId}/comments?page=${page}`)
        .then(response => response.json())
        .then(data => {
            const commentsContainer = document.getElementById(`comments-${postId}`);
            if (replace) {
                commentsContainer.innerHTML = '';
            }

            data.data.forEach(comment => {
                const commentHtml = `
                    <div class="flex items-start space-x-2">
                        <img src="${comment.profile_image || '/assets/images/default-avatar.png'}" 
                             alt="${comment.username}" 
                             class="w-8 h-8 rounded-full">
                        <div class="flex-1 bg-gray-100 rounded-lg px-4 py-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-800">${comment.username}</h4>
                                ${comment.user_id === <?php echo $_SESSION['user_id']; ?> ? `
                                    <button onclick="deleteComment(${comment.id})"
                                            class="text-gray-500 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                ` : ''}
                            </div>
                            <p class="text-gray-800">${comment.content}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                ${new Date(comment.created_at).toLocaleString()}
                            </p>
                        </div>
                    </div>
                `;
                commentsContainer.insertAdjacentHTML('beforeend', commentHtml);
            });

            currentPage = page;
            if (data.current_page < data.last_page) {
                const loadMoreButton = document.createElement('button');
                loadMoreButton.textContent = 'Load more comments';
                loadMoreButton.className = 'text-blue-500 hover:text-blue-600 mt-4';
                loadMoreButton.onclick = () => loadMoreComments(postId);
                commentsContainer.appendChild(loadMoreButton);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadMoreComments(postId) {
    loadComments(postId, currentPage + 1);
}

function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    fetch('/api/posts/comment/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ comment_id: commentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const comment = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (comment) {
                comment.remove();
                const commentCount = document.getElementById(`comment-count-${postId}`);
                commentCount.textContent = parseInt(commentCount.textContent) - 1;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) return;

    fetch('/api/posts/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script> 