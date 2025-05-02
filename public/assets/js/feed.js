$(document).ready(function() {
    // Handle post creation
    $('#createPostForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '/posts/create',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $('#createPostForm')[0].reset();
                    $('#selectedImage').text('');
                    
                    // Add new post to feed
                    const post = response.data;
                    const postHtml = createPostHtml(post);
                    $('.card.mb-4').first().after(postHtml);
                    
                    // Show success message
                    showAlert('Post created successfully!', 'success');
                } else {
                    showAlert(response.message || 'Error creating post', 'danger');
                }
            },
            error: function() {
                showAlert('Error creating post', 'danger');
            }
        });
    });
    
    // Handle image selection
    $('#postImage').on('change', function() {
        const fileName = this.files[0]?.name;
        if (fileName) {
            $('#selectedImage').text(fileName);
        } else {
            $('#selectedImage').text('');
        }
    });
    
    // Handle post likes
    $('.like-button').on('click', function() {
        const button = $(this);
        const postId = button.data('post-id');
        const isLiked = button.data('liked') === 'true';
        
        $.ajax({
            url: '/posts/' + postId + '/like',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    const likeCount = button.find('.like-count');
                    const icon = button.find('i');
                    
                    if (isLiked) {
                        button.data('liked', 'false');
                        icon.removeClass('text-danger');
                        likeCount.text(parseInt(likeCount.text()) - 1);
                    } else {
                        button.data('liked', 'true');
                        icon.addClass('text-danger');
                        likeCount.text(parseInt(likeCount.text()) + 1);
                    }
                }
            }
        });
    });
    
    // Handle comment button clicks
    $('.comment-button').on('click', function() {
        const postId = $(this).data('post-id');
        const commentsSection = $(`#comments-${postId}`);
        
        if (commentsSection.is(':hidden')) {
            // Load comments if not already loaded
            if (commentsSection.find('.comments-list').is(':empty')) {
                loadComments(postId);
            }
        }
        
        commentsSection.slideToggle();
    });
    
    // Handle comment submission
    $('.comment-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const postId = form.data('post-id');
        const input = form.find('input');
        const content = input.val();
        
        $.ajax({
            url: '/posts/' + postId + '/comments',
            type: 'POST',
            data: { content: content },
            success: function(response) {
                if (response.success) {
                    // Add new comment to list
                    const commentHtml = createCommentHtml(response.data);
                    form.siblings('.comments-list').prepend(commentHtml);
                    
                    // Update comment count
                    const commentCount = $(`.comment-button[data-post-id="${postId}"] .comment-count`);
                    commentCount.text(parseInt(commentCount.text()) + 1);
                    
                    // Clear input
                    input.val('');
                }
            }
        });
    });
    
    // Handle post deletion
    $('.delete-post').on('click', function() {
        const postId = $(this).data('post-id');
        
        if (confirm('Are you sure you want to delete this post?')) {
            $.ajax({
                url: '/posts/' + postId,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        $(`#post-${postId}`).fadeOut(function() {
                            $(this).remove();
                        });
                        showAlert('Post deleted successfully', 'success');
                    }
                }
            });
        }
    });
    
    // Handle post editing
    $('.edit-post').on('click', function() {
        const postId = $(this).data('post-id');
        const post = $(`#post-${postId}`);
        const content = post.find('.card-text').text();
        
        // Replace content with edit form
        post.find('.card-text').replaceWith(`
            <form class="edit-post-form" data-post-id="${postId}">
                <textarea class="form-control mb-2">${content}</textarea>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm me-2 cancel-edit">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        `);
        
        // Handle edit form submission
        post.find('.edit-post-form').on('submit', function(e) {
            e.preventDefault();
            
            const newContent = $(this).find('textarea').val();
            
            $.ajax({
                url: '/posts/' + postId,
                type: 'PUT',
                data: { content: newContent },
                success: function(response) {
                    if (response.success) {
                        // Replace form with updated content
                        post.find('.edit-post-form').replaceWith(`
                            <p class="card-text">${newContent}</p>
                        `);
                        showAlert('Post updated successfully', 'success');
                    }
                }
            });
        });
        
        // Handle cancel button
        post.find('.cancel-edit').on('click', function() {
            post.find('.edit-post-form').replaceWith(`
                <p class="card-text">${content}</p>
            `);
        });
    });
    
    // Handle friend requests
    $('.add-friend').on('click', function() {
        const button = $(this);
        const userId = button.data('user-id');
        
        $.ajax({
            url: '/friends/request',
            type: 'POST',
            data: { user_id: userId },
            success: function(response) {
                if (response.success) {
                    button.prop('disabled', true).text('Request Sent');
                    showAlert('Friend request sent', 'success');
                }
            }
        });
    });
});

// Helper function to create post HTML
function createPostHtml(post) {
    return `
        <div class="card mb-4" id="post-${post.id}">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="${post.user_image || '/assets/images/default-avatar.png'}" 
                         alt="${post.username}" 
                         class="rounded-circle me-2"
                         width="40" 
                         height="40">
                    <div>
                        <h6 class="mb-0">
                            <a href="/profile/${post.user_id}" class="text-decoration-none">
                                ${post.username}
                            </a>
                        </h6>
                        <small class="text-muted">
                            ${new Date(post.created_at).toLocaleString()}
                        </small>
                    </div>
                </div>
                
                <p class="card-text">${post.content}</p>
                
                ${post.image_url ? `
                    <img src="${post.image_url}" 
                         alt="Post image" 
                         class="img-fluid rounded mb-3">
                ` : ''}
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-link text-dark like-button" 
                                data-post-id="${post.id}"
                                data-liked="false">
                            <i class="fas fa-heart"></i>
                            <span class="like-count">0</span>
                        </button>
                        <button class="btn btn-link text-dark comment-button" 
                                data-post-id="${post.id}">
                            <i class="fas fa-comment"></i>
                            <span class="comment-count">0</span>
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item edit-post" 
                                        data-post-id="${post.id}">
                                    Edit
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item delete-post" 
                                        data-post-id="${post.id}">
                                    Delete
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="comments-section mt-3" id="comments-${post.id}" style="display: none;">
                    <form class="comment-form mb-3" data-post-id="${post.id}">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Write a comment..."
                                   required>
                            <button class="btn btn-primary" type="submit">Post</button>
                        </div>
                    </form>
                    <div class="comments-list">
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Helper function to create comment HTML
function createCommentHtml(comment) {
    return `
        <div class="comment mb-2" id="comment-${comment.id}">
            <div class="d-flex">
                <img src="${comment.user_image || '/assets/images/default-avatar.png'}" 
                     alt="${comment.username}" 
                     class="rounded-circle me-2"
                     width="32" 
                     height="32">
                <div class="flex-grow-1">
                    <div class="bg-light rounded p-2">
                        <h6 class="mb-0">
                            <a href="/profile/${comment.user_id}" class="text-decoration-none">
                                ${comment.username}
                            </a>
                        </h6>
                        <p class="mb-0">${comment.content}</p>
                    </div>
                    <small class="text-muted">
                        ${new Date(comment.created_at).toLocaleString()}
                    </small>
                </div>
            </div>
        </div>
    `;
}

// Helper function to load comments
function loadComments(postId) {
    $.ajax({
        url: '/posts/' + postId + '/comments',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const commentsList = $(`#comments-${postId} .comments-list`);
                commentsList.empty();
                
                response.data.forEach(comment => {
                    commentsList.append(createCommentHtml(comment));
                });
            }
        }
    });
}

// Helper function to show alerts
function showAlert(message, type = 'info') {
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.container').prepend(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
} 