<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Profile Information</h4>
                        <form action="/profile/update" method="POST" enctype="multipart/form-data">
                            <div class="mb-4 text-center">
                                <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/assets/images/default-avatar.png'); ?>" 
                                     alt="Profile" 
                                     class="rounded-circle img-thumbnail mb-3"
                                     width="150" 
                                     height="150"
                                     id="profilePreview">
                                <div>
                                    <label class="btn btn-outline-primary">
                                        <i class="fas fa-camera"></i> Change Photo
                                        <input type="file" 
                                               name="profile_image" 
                                               accept="image/*" 
                                               class="d-none" 
                                               id="profileImage">
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" 
                                          id="bio" 
                                          name="bio" 
                                          rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="location" 
                                       name="location" 
                                       value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Change Password</h4>
                        <form action="/profile/update-password" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="card border-danger">
                    <div class="card-body">
                        <h4 class="card-title text-danger mb-4">Delete Account</h4>
                        <p class="text-muted mb-4">
                            Once you delete your account, there is no going back. Please be certain.
                        </p>
                        <form action="/profile/delete" method="POST" class="delete-account-form">
                            <div class="mb-3">
                                <label for="delete_password" class="form-label">Enter your password to confirm</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="delete_password" 
                                       name="password" 
                                       required>
                            </div>
                            <button type="submit" class="btn btn-danger">Delete Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Profile image preview
        $('#profileImage').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#profilePreview').attr('src', e.target.result);
                }
                reader.readAsDataURL(file);
            }
        });

        // Delete account confirmation
        $('.delete-account-form').on('submit', function(e) {
            if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 