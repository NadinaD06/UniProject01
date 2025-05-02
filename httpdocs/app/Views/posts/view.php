<?php
/**
 * View Post
 */
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Post by <?php echo htmlspecialchars($post['username']); ?></h4>
                    <small class="text-muted"><?php echo date('F j, Y g:i a', strtotime($post['created_at'])); ?></small>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="img-fluid mb-3" alt="Post image">
                    <?php endif; ?>
                    
                    <?php if (!empty($post['latitude']) && !empty($post['longitude'])): ?>
                        <div class="mt-3">
                            <h5>Location</h5>
                            <div id="map" style="height: 300px;"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary like-button" data-post-id="<?php echo $post['id']; ?>">
                            <i class="fas fa-heart"></i> Like (<span class="like-count"><?php echo $post['like_count']; ?></span>)
                        </button>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="mt-4">
                        <h5>Comments</h5>
                        <form action="/posts/comment" method="POST" class="mb-3">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <div class="form-group">
                                <textarea class="form-control" name="content" rows="2" required placeholder="Write a comment..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Comment</button>
                        </form>
                        
                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment mb-2">
                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                    <small class="text-muted"><?php echo date('F j, Y g:i a', strtotime($comment['created_at'])); ?></small>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($post['latitude']) && !empty($post['longitude'])): ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('map').setView([<?php echo $post['latitude']; ?>, <?php echo $post['longitude']; ?>], 13);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add marker at post location
    L.marker([<?php echo $post['latitude']; ?>, <?php echo $post['longitude']; ?>]).addTo(map);
});
</script>
<?php endif; ?> 