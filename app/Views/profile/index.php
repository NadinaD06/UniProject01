<?php
$title = "Profile - " . $user['username'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="profile-header">
        <img src="<?php echo $user['avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Profile Picture" class="profile-avatar">
        <h1><?php echo htmlspecialchars($user['username']); ?></h1>
        <p class="bio"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet'); ?></p>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <h3>About Me</h3>
                <ul class="list-unstyled">
                    <li><strong>Joined:</strong> <?php echo date('F Y', strtotime($user['created_at'])); ?></li>
                    <li><strong>Posts:</strong> <?php echo $postCount; ?></li>
                    <li><strong>Followers:</strong> <?php echo $followerCount; ?></li>
                    <li><strong>Following:</strong> <?php echo $followingCount; ?></li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <h3>Recent Posts</h3>
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <img src="<?php echo $user['avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Avatar" class="post-avatar">
                                <div>
                                    <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                    <small><?php echo date('F j, Y', strtotime($post['created_at'])); ?></small>
                                </div>
                            </div>
                            <p><?php echo htmlspecialchars($post['content']); ?></p>
                            <div class="post-actions">
                                <button class="btn btn-sm"><i class="fas fa-heart"></i> <?php echo $post['likes']; ?></button>
                                <button class="btn btn-sm"><i class="fas fa-comment"></i> <?php echo $post['comments']; ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 