<?php
// Set page-specific variables
$page_title = 'Feed';
$page_css = 'feed';
$page_js = 'feed';
?>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <!-- Create Post -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="/posts/create" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <textarea class="form-control" name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                            </div>
                            <div class="mb-3">
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <div id="map" style="height: 200px;"></div>
                                <input type="hidden" name="location_lat" id="location_lat">
                                <input type="hidden" name="location_lng" id="location_lng">
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Post</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Posts -->
                <div id="posts">
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-4 post" data-post-id="<?php echo $post['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($post['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                         alt="Profile" 
                                         class="rounded-circle me-2"
                                         width="40" 
                                         height="40">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                                    </div>
                                    <?php if ($post['user_id'] === $user['id']): ?>
                                        <div class="ms-auto">
                                            <button class="btn btn-link text-danger delete-post" data-post-id="<?php echo $post['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php if ($post['image']): ?>
                                    <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                                         alt="Post image" 
                                         class="img-fluid rounded mb-3">
                                <?php endif; ?>
                                
                                <?php if ($post['location_lat'] && $post['location_lng']): ?>
                                    <div class="post-map mb-3" 
                                         data-lat="<?php echo $post['location_lat']; ?>" 
                                         data-lng="<?php echo $post['location_lng']; ?>"
                                         style="height: 200px;">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <button class="btn btn-link text-dark like-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>"
                                            data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span class="like-count"><?php echo $post['like_count']; ?></span>
                                    </button>
                                    <button class="btn btn-link text-dark comment-button" 
                                            data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fas fa-comment"></i>
                                        <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                    </button>
                                </div>
                                
                                <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                                    <!-- Comments will be loaded here -->
                                </div>
                                
                                <form class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Write a comment..." required>
                                        <button class="btn btn-primary" type="submit">Post</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($hasMore): ?>
                    <div class="text-center">
                        <button class="btn btn-outline-primary load-more" data-page="<?php echo $page + 1; ?>">
                            Load More
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize map for new post
        const map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = null;
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker(e.latlng).addTo(map);
            document.getElementById('location_lat').value = e.latlng.lat;
            document.getElementById('location_lng').value = e.latlng.lng;
        });
        
        // Initialize maps for existing posts
        document.querySelectorAll('.post-map').forEach(function(element) {
            const lat = parseFloat(element.dataset.lat);
            const lng = parseFloat(element.dataset.lng);
            const postMap = L.map(element).setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(postMap);
            L.marker([lat, lng]).addTo(postMap);
        });
        
        // Like/Unlike post
        $('.like-button').click(function() {
            const button = $(this);
            const postId = button.data('post-id');
            
            $.post('/posts/like/' + postId, function(response) {
                if (response.success) {
                    const count = parseInt(button.find('.like-count').text());
                    button.toggleClass('liked');
                    button.find('.like-count').text(button.hasClass('liked') ? count + 1 : count - 1);
                }
            });
        });
        
        // Load comments
        function loadComments(postId) {
            const container = $('#comments-' + postId);
            if (container.children().length === 0) {
                $.get('/posts/comments/' + postId, function(response) {
                    if (response.success) {
                        response.comments.forEach(function(comment) {
                            container.append(`
                                <div class="comment mb-2">
                                    <div class="d-flex">
                                        <img src="${comment.profile_image || '/assets/images/default-avatar.png'}" 
                                             alt="Profile" 
                                             class="rounded-circle me-2"
                                             width="32" 
                                             height="32">
                                        <div class="flex-grow-1">
                                            <div class="bg-light rounded p-2">
                                                <strong>${comment.username}</strong>
                                                <p class="mb-0">${comment.content}</p>
                                            </div>
                                            <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                                        </div>
                                    </div>
                                </div>
                            `);
                        });
                    }
                });
            }
            container.toggle();
        }
        
        // Toggle comments
        $('.comment-button').click(function() {
            const postId = $(this).data('post-id');
            loadComments(postId);
        });
        
        // Add comment
        $('.comment-form').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const postId = form.data('post-id');
            const input = form.find('input');
            const content = input.val();
            
            $.post('/posts/comment/' + postId, { content: content }, function(response) {
                if (response.success) {
                    const container = $('#comments-' + postId);
                    container.append(`
                        <div class="comment mb-2">
                            <div class="d-flex">
                                <img src="${response.comment.profile_image || '/assets/images/default-avatar.png'}" 
                                     alt="Profile" 
                                     class="rounded-circle me-2"
                                     width="32" 
                                     height="32">
                                <div class="flex-grow-1">
                                    <div class="bg-light rounded p-2">
                                        <strong>${response.comment.username}</strong>
                                        <p class="mb-0">${response.comment.content}</p>
                                    </div>
                                    <small class="text-muted">${new Date(response.comment.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                        </div>
                    `);
                    input.val('');
                const count = parseInt(form.closest('.post').find('.comment-count').text());
                form.closest('.post').find('.comment-count').text(count + 1);
                }
            });
        });
        
        // Delete post
        $('.delete-post').click(function() {
                const button = $(this);
                const postId = button.data('post-id');
                
        if (confirm('Are you sure you want to delete this post?')) {
                $.post('/posts/delete/' + postId, function(response) {
                    if (response.success) {
                        button.closest('.post').fadeOut();
                    }
                });
            }
        });
        
        // Load more posts
        $('.load-more').click(function() {
            const button = $(this);
            const page = button.data('page');
            
            $.get('/posts/load-more', { page: page }, function(response) {
                if (response.success) {
                    response.posts.forEach(function(post) {
                        // Add new posts to the feed
                    // This is a simplified version - you'll need to implement the full post HTML structure
                    $('#posts').append(`
                        <div class="card mb-4 post" data-post-id="${post.id}">
                            <!-- Post content -->
                        </div>
                    `);
                    });
                    
                    if (response.hasMore) {
                        button.data('page', page + 1);
                    } else {
                        button.remove();
                    }
                }
            });
        });
    </script>