<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
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
                        <a class="nav-link active" href="/notifications">
                            Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Notifications</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-bell fa-3x mb-3"></i>
                                <p>No notifications yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item" id="notification-<?php echo $notification['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($notification['type'] === 'friend_request'): ?>
                                                <i class="fas fa-user-plus fa-2x text-primary"></i>
                                            <?php elseif ($notification['type'] === 'post_like'): ?>
                                                <i class="fas fa-heart fa-2x text-danger"></i>
                                            <?php elseif ($notification['type'] === 'post_comment'): ?>
                                                <i class="fas fa-comment fa-2x text-success"></i>
                                            <?php elseif ($notification['type'] === 'friend_accept'): ?>
                                                <i class="fas fa-user-check fa-2x text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-bell fa-2x text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="mb-0">
                                                    <?php
                                                    $data = $notification['data'];
                                                    switch ($notification['type']) {
                                                        case 'friend_request':
                                                            echo htmlspecialchars($data['username']) . ' sent you a friend request';
                                                            break;
                                                        case 'post_like':
                                                            echo htmlspecialchars($data['username']) . ' liked your post';
                                                            break;
                                                        case 'post_comment':
                                                            echo htmlspecialchars($data['username']) . ' commented on your post';
                                                            break;
                                                        case 'friend_accept':
                                                            echo htmlspecialchars($data['username']) . ' accepted your friend request';
                                                            break;
                                                        default:
                                                            echo htmlspecialchars($notification['type']);
                                                    }
                                                    ?>
                                                </p>
                                                <div class="d-flex align-items-center">
                                                    <small class="text-muted me-3">
                                                        <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?>
                                                    </small>
                                                    <button class="btn btn-link text-muted p-0 delete-notification" 
                                                            data-id="<?php echo $notification['id']; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php if (isset($data['content'])): ?>
                                                <p class="text-muted small mb-0">
                                                    <?php echo htmlspecialchars($data['content']); ?>
                                                </p>
                                            <?php endif; ?>
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
            // Delete notification
            $('.delete-notification').on('click', function() {
                const notificationId = $(this).data('id');
                const notification = $(`#notification-${notificationId}`);
                
                $.ajax({
                    url: `/notifications/delete/${notificationId}`,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            notification.fadeOut(300, function() {
                                $(this).remove();
                                
                                // If no notifications left, show empty state
                                if ($('.list-group-item').length === 0) {
                                    $('.list-group').html(`
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-bell fa-3x mb-3"></i>
                                            <p>No notifications yet.</p>
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