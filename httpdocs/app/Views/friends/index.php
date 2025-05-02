<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends</title>
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

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row">
            <!-- Friend Requests -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Friend Requests</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($requests)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-user-plus fa-3x mb-3"></i>
                                <p>No friend requests</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <div class="list-group-item" id="request-<?php echo $request['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($request['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($request['username']); ?>" 
                                             class="rounded-circle me-3"
                                             width="48" 
                                             height="48">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <a href="/profile/<?php echo htmlspecialchars($request['username']); ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($request['username']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('M j', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <button class="btn btn-sm btn-primary accept-request" 
                                                    data-id="<?php echo $request['id']; ?>">
                                                Accept
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger reject-request" 
                                                    data-id="<?php echo $request['id']; ?>">
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Friends List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Friends</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($friends)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>No friends yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($friends as $friend): ?>
                                <div class="list-group-item" id="friend-<?php echo $friend['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($friend['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($friend['username']); ?>" 
                                             class="rounded-circle me-3"
                                             width="48" 
                                             height="48">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <a href="/profile/<?php echo htmlspecialchars($friend['username']); ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($friend['username']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo $friend['mutual_friends']; ?> mutual friends
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <a href="/messages/<?php echo htmlspecialchars($friend['username']); ?>" 
                                               class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-comment"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger remove-friend" 
                                                    data-id="<?php echo $friend['id']; ?>">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Accept friend request
            $('.accept-request').on('click', function() {
                const requestId = $(this).data('id');
                const request = $(`#request-${requestId}`);
                
                $.ajax({
                    url: `/friends/accept/${requestId}`,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            request.fadeOut(300, function() {
                                $(this).remove();
                                
                                // If no requests left, show empty state
                                if ($('.list-group-item').length === 0) {
                                    $('.list-group').html(`
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-user-plus fa-3x mb-3"></i>
                                            <p>No friend requests</p>
                                        </div>
                                    `);
                                }
                            });
                        }
                    }
                });
            });

            // Reject friend request
            $('.reject-request').on('click', function() {
                const requestId = $(this).data('id');
                const request = $(`#request-${requestId}`);
                
                $.ajax({
                    url: `/friends/reject/${requestId}`,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            request.fadeOut(300, function() {
                                $(this).remove();
                                
                                // If no requests left, show empty state
                                if ($('.list-group-item').length === 0) {
                                    $('.list-group').html(`
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-user-plus fa-3x mb-3"></i>
                                            <p>No friend requests</p>
                                        </div>
                                    `);
                                }
                            });
                        }
                    }
                });
            });

            // Remove friend
            $('.remove-friend').on('click', function() {
                if (!confirm('Are you sure you want to remove this friend?')) {
                    return;
                }
                
                const friendId = $(this).data('id');
                const friend = $(`#friend-${friendId}`);
                
                $.ajax({
                    url: `/friends/remove/${friendId}`,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            friend.fadeOut(300, function() {
                                $(this).remove();
                                
                                // If no friends left, show empty state
                                if ($('.list-group-item').length === 0) {
                                    $('.list-group').html(`
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <p>No friends yet</p>
                                        </div>
                                    `);
                                }
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 