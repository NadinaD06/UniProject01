<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Social Media Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="/feed">Social Media</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/feed">Feed</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/messages">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/notifications">
                            Notifications
                            <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-link text-dark dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                 alt="Profile" 
                                 class="rounded-circle"
                                 width="32" 
                                 height="32">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/profile">Profile</a></li>
                            <li><a class="dropdown-item" href="/settings">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="/profile" class="text-decoration-none">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="/friends" class="text-decoration-none">
                                    <i class="fas fa-users me-2"></i>Friends
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="/groups" class="text-decoration-none">
                                    <i class="fas fa-users-cog me-2"></i>Groups
                                </a>
                            </li>
                            <li>
                                <a href="/events" class="text-decoration-none">
                                    <i class="fas fa-calendar-alt me-2"></i>Events
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Feed -->
            <div class="col-lg-6">
                <!-- Create Post -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="/posts/create" method="POST" enctype="multipart/form-data" id="createPostForm">
                            <div class="mb-3">
                                <textarea class="form-control" 
                                          name="content" 
                                          rows="3" 
                                          placeholder="What's on your mind?"
                                          required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-image"></i> Add Photo
                                        <input type="file" 
                                               name="image" 
                                               accept="image/*" 
                                               class="d-none" 
                                               id="postImage">
                                    </label>
                                    <span id="selectedImage" class="ms-2"></span>
                                </div>
                                <button type="submit" class="btn btn-primary">Post</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Posts -->
                <?php if (empty($posts)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-newspaper fa-3x mb-3"></i>
                        <p>No posts yet. Be the first to share something!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-4" id="post-<?php echo $post['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($post['user_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($post['username']); ?>" 
                                         class="rounded-circle me-2"
                                         width="40" 
                                         height="40">
                                    <div>
                                        <h6 class="mb-0">
                                            <a href="/profile/<?php echo $post['user_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($post['username']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php if (!empty($post['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                         alt="Post image" 
                                         class="img-fluid rounded mb-3">
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button class="btn btn-link text-dark like-button" 
                                                data-post-id="<?php echo $post['id']; ?>"
                                                data-liked="<?php echo $post['is_liked'] ? 'true' : 'false'; ?>">
                                            <i class="fas fa-heart<?php echo $post['is_liked'] ? ' text-danger' : ''; ?>"></i>
                                            <span class="like-count"><?php echo $post['like_count']; ?></span>
                                        </button>
                                        <button class="btn btn-link text-dark comment-button" 
                                                data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-comment"></i>
                                            <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                        </button>
                                    </div>
                                    <?php if ($post['user_id'] === $user['id']): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button class="dropdown-item edit-post" 
                                                            data-post-id="<?php echo $post['id']; ?>">
                                                        Edit
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item delete-post" 
                                                            data-post-id="<?php echo $post['id']; ?>">
                                                        Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Comments Section -->
                                <div class="comments-section mt-3" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                    <form class="comment-form mb-3" data-post-id="<?php echo $post['id']; ?>">
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   placeholder="Write a comment..."
                                                   required>
                                            <button class="btn btn-primary" type="submit">Post</button>
                                        </div>
                                    </form>
                                    <div class="comments-list">
                                        <!-- Comments will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-3 d-none d-lg-block">
                <!-- Friend Suggestions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Suggested Friends</h5>
                        <?php if (empty($suggestedFriends)): ?>
                            <p class="text-muted">No suggestions available</p>
                        <?php else: ?>
                            <?php foreach ($suggestedFriends as $friend): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($friend['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($friend['username']); ?>" 
                                         class="rounded-circle me-2"
                                         width="40" 
                                         height="40">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <a href="/profile/<?php echo $friend['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($friend['username']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo $friend['mutual_friends']; ?> mutual friends
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-primary add-friend" 
                                            data-user-id="<?php echo $friend['id']; ?>">
                                        Add
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Trending Topics -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Trending Topics</h5>
                        <?php if (empty($trendingTopics)): ?>
                            <p class="text-muted">No trending topics</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($trendingTopics as $topic): ?>
                                    <li class="mb-2">
                                        <a href="/topics/<?php echo $topic['id']; ?>" class="text-decoration-none">
                                            #<?php echo htmlspecialchars($topic['name']); ?>
                                        </a>
                                        <small class="text-muted d-block">
                                            <?php echo $topic['post_count']; ?> posts
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/feed.js"></script>
</body>
</html> 