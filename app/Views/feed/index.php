<?php
$title = "Feed";
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <!-- Create Post Form -->
            <div class="card">
                <form action="/post/create" method="POST">
                    <div class="form-group">
                        <input type="text" name="title" class="form-control" placeholder="What's on your mind?" required>
                    </div>
                    <div class="form-group">
                        <textarea name="content" class="form-control" rows="3" placeholder="Share your thoughts..." required></textarea>
                    </div>
                    <button type="submit" class="btn">Post</button>
                </form>
            </div>

            <!-- Posts Feed -->
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-header">
                            <img src="<?php echo $post['user_avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Avatar" class="post-avatar">
                            <div>
                                <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                <small><?php echo date('F j, Y', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                        <div class="post-actions">
                            <button class="btn btn-sm" onclick="likePost(<?php echo $post['id']; ?>)">
                                <i class="fas fa-heart"></i> <?php echo $post['likes']; ?>
                            </button>
                            <button class="btn btn-sm" onclick="showComments(<?php echo $post['id']; ?>)">
                                <i class="fas fa-comment"></i> <?php echo $post['comments']; ?>
                            </button>
                            <button class="btn btn-sm" onclick="sharePost(<?php echo $post['id']; ?>)">
                                <i class="fas fa-share"></i> Share
                            </button>
                        </div>
                        
                        <!-- Comments Section -->
                        <div id="comments-<?php echo $post['id']; ?>" class="comments-section" style="display: none;">
                            <div class="form-group">
                                <textarea class="form-control" placeholder="Write a comment..."></textarea>
                                <button class="btn btn-sm mt-2">Comment</button>
                            </div>
                            <div class="comments-list">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p>No posts yet. Be the first to post!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Trending Topics -->
            <div class="card">
                <h3>Trending Topics</h3>
                <ul class="list-unstyled">
                    <?php foreach ($trendingTopics as $topic): ?>
                        <li>
                            <a href="/topic/<?php echo $topic['id']; ?>" class="trending-topic">
                                #<?php echo htmlspecialchars($topic['name']); ?>
                                <span class="badge"><?php echo $topic['count']; ?> posts</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Suggested Users -->
            <div class="card">
                <h3>Suggested Users</h3>
                <?php foreach ($suggestedUsers as $user): ?>
                    <div class="suggested-user">
                        <img src="<?php echo $user['avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Avatar" class="post-avatar">
                        <div>
                            <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                            <button class="btn btn-sm">Follow</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function likePost(postId) {
    // Implement like functionality
}

function showComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
}

function sharePost(postId) {
    // Implement share functionality
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 