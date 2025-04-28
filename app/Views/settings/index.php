<?php
$title = "Settings";
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="settings-nav">
                    <h3>Settings</h3>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#profile">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#account">Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#privacy">Privacy</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#notifications">Notifications</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- Profile Settings -->
            <div id="profile" class="settings-section">
                <div class="card">
                    <h3>Profile Settings</h3>
                    <form action="/settings/update-profile" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <div class="profile-picture-preview">
                                <img src="<?php echo $user['avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Profile Picture" class="profile-avatar">
                                <input type="file" name="avatar" accept="image/*" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Account Settings -->
            <div id="account" class="settings-section" style="display: none;">
                <div class="card">
                    <h3>Account Settings</h3>
                    <form action="/settings/update-account" method="POST">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>

                        <button type="submit" class="btn">Update Account</button>
                    </form>
                </div>
            </div>

            <!-- Privacy Settings -->
            <div id="privacy" class="settings-section" style="display: none;">
                <div class="card">
                    <h3>Privacy Settings</h3>
                    <form action="/settings/update-privacy" method="POST">
                        <div class="form-group">
                            <label>Profile Visibility</label>
                            <select name="profile_visibility" class="form-control">
                                <option value="public" <?php echo $user['profile_visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="friends" <?php echo $user['profile_visibility'] === 'friends' ? 'selected' : ''; ?>>Friends Only</option>
                                <option value="private" <?php echo $user['profile_visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Post Visibility</label>
                            <select name="post_visibility" class="form-control">
                                <option value="public" <?php echo $user['post_visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="friends" <?php echo $user['post_visibility'] === 'friends' ? 'selected' : ''; ?>>Friends Only</option>
                                <option value="private" <?php echo $user['post_visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Allow Tagging</label>
                            <div class="form-check">
                                <input type="checkbox" name="allow_tagging" class="form-check-input" <?php echo $user['allow_tagging'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Allow others to tag me in posts</label>
                            </div>
                        </div>

                        <button type="submit" class="btn">Save Privacy Settings</button>
                    </form>
                </div>
            </div>

            <!-- Notification Settings -->
            <div id="notifications" class="settings-section" style="display: none;">
                <div class="card">
                    <h3>Notification Settings</h3>
                    <form action="/settings/update-notifications" method="POST">
                        <div class="form-group">
                            <label>Email Notifications</label>
                            <div class="form-check">
                                <input type="checkbox" name="email_notifications" class="form-check-input" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Receive email notifications</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Push Notifications</label>
                            <div class="form-check">
                                <input type="checkbox" name="push_notifications" class="form-check-input" <?php echo $user['push_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Receive push notifications</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notification Types</label>
                            <div class="form-check">
                                <input type="checkbox" name="notify_likes" class="form-check-input" <?php echo $user['notify_likes'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Likes on your posts</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="notify_comments" class="form-check-input" <?php echo $user['notify_comments'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Comments on your posts</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="notify_follows" class="form-check-input" <?php echo $user['notify_follows'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">New followers</label>
                            </div>
                        </div>

                        <button type="submit" class="btn">Save Notification Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Settings navigation
    const navLinks = document.querySelectorAll('.settings-nav .nav-link');
    const sections = document.querySelectorAll('.settings-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);

            // Update active link
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Show target section
            sections.forEach(section => {
                section.style.display = section.id === targetId ? 'block' : 'none';
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 