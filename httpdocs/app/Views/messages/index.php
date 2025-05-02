<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
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
                        <a class="nav-link active" href="/messages">Messages</a>
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
            <div class="col-md-4">
                <!-- Conversations List -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Messages</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>No conversations yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="/messages/<?php echo htmlspecialchars($conversation['username']); ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($conversation['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($conversation['username']); ?>" 
                                             class="rounded-circle me-3"
                                             width="48" 
                                             height="48">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($conversation['username']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo date('M j', strtotime($conversation['last_message_time'])); ?>
                                                </small>
                                            </div>
                                            <p class="text-muted mb-0 small text-truncate">
                                                <?php echo htmlspecialchars($conversation['last_message']); ?>
                                            </p>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill ms-2">
                                                <?php echo $conversation['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <!-- Welcome Message -->
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h4>Your Messages</h4>
                        <p class="text-muted">Select a conversation or start a new one</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html> 