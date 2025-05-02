<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
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
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                 alt="Profile" 
                                 class="rounded-circle mb-3"
                                 width="120" 
                                 height="120">
                            <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                            <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-lock me-2"></i> Password
                            </a>
                            <a href="#account" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-trash-alt me-2"></i> Delete Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show">
                        <?php echo $_SESSION['flash']['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <div class="tab-content">
                    <!-- Profile Settings -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Profile Settings</h5>
                            </div>
                            <div class="card-body">
                                <form action="/settings/update-profile" method="POST" enctype="multipart/form-data">
                                    <div class="mb-4">
                                        <label class="form-label">Profile Image</label>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                                 alt="Profile" 
                                                 class="rounded-circle me-3"
                                                 width="64" 
                                                 height="64">
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" name="profile_image" accept="image/*">
                                                <small class="text-muted">Max file size: 5MB. Allowed types: JPG, PNG, GIF</small>
                                            </div>
                                        </div>
                                        <?php if (isset($errors['profile_image'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['profile_image']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                        <?php if (isset($errors['username'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['username']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea class="form-control" name="bio" rows="3" 
                                                  maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        <?php if (isset($errors['bio'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['bio']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Password Settings -->
                    <div class="tab-pane fade" id="password">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="/settings/update-password" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                        <?php if (isset($errors['current_password'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['current_password']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <?php if (isset($errors['new_password'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['new_password']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['confirm_password']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Account -->
                    <div class="tab-pane fade" id="account">
                        <div class="card border-danger">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0 text-danger">Delete Account</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Once you delete your account, there is no going back. Please be certain.
                                </p>
                                <form action="/settings/delete-account" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                                    <div class="mb-3">
                                        <label class="form-label">Enter your password to confirm</label>
                                        <input type="password" class="form-control" name="password" required>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="text-danger mt-1"><?php echo $errors['password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Delete Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 