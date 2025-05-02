<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile</title>
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
                        <a class="nav-link" href="/feed">Feed</a>
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

    <!-- Profile Header -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>" 
                         class="rounded-circle img-thumbnail"
                         width="200" 
                         height="200">
                </div>
                <div class="col-md-9">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="h2 mb-1"><?php echo htmlspecialchars($user['username']); ?></h1>
                            <?php if (!empty($user['bio'])): ?>
                                <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($user['location'])): ?>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($user['location']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($_SESSION['user_id'] === $user['id']): ?>
                            <a href="/profile/edit" class="btn btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        <?php else: ?>
                            <div class="friend-actions">
                                <?php if ($friendStatus === null): ?>
                                    <button class="btn btn-primary add-friend" data-user-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-user-plus"></i> Add Friend
                                    </button>
                                <?php elseif ($friendStatus === 'pending'): ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-clock"></i> Friend Request Sent
                                    </button>
                                <?php elseif ($friendStatus === 'accepted'): ?>
                                    <button class="btn btn-success" disabled>
                                        <i class="fas fa-check"></i> Friends
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-4">
                <!-- Mutual Friends -->
                <?php if (!empty($mutualFriends)): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Mutual Friends</h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($mutualFriends as $friend): ?>
                                    <a href="/profile/<?php echo $friend['username']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($friend['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($friend['username']); ?>" 
                                                 class="rounded-circle me-2"
                                                 width="40" 
                                                 height="40">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($friend['username']); ?></h6>
                                                <small class="text-muted"><?php echo $friend['mutual_count']; ?> mutual friends</small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Posts -->
            <div class="col-md-8">
                <?php if (empty($posts)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-newspaper fa-3x mb-3"></i>
                        <p>No posts yet.</p>
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
                                            <a href="/profile/<?php echo $post['username']; ?>" class="text-decoration-none">
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
                                    <?php if ($post['user_id'] === $_SESSION['user_id']): ?>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/feed.js"></script>
    <script src="/assets/js/profile.js"></script>
</body>
</html> 