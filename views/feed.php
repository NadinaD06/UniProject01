<?php
// feed.php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSpace - Feed</title>
    <link rel="stylesheet" href="/assets/css/feed.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/nav.php'; ?>

    <main class="main-container">
        <?php include '../includes/left_sidebar.php'; ?>

        <section class="feed">
            <div class="stories-container">
                <!-- Stories content -->
            </div>

            <div class="posts-grid" id="postsContainer">
                <?php
                // Fetch posts
                $stmt = $pdo->prepare("
                    SELECT p.*, u.username, u.profile_image 
                    FROM posts p 
                    JOIN users u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute();
                $posts = $stmt->fetchAll();

                foreach ($posts as $post) {
                    include '../includes/post_card.php';
                }
                ?>
            </div>

            <button class="load-more" id="loadMoreBtn">Load More</button>
        </section>

        <?php include '../includes/right_sidebar.php'; ?>
    </main>

    <script>
        // Pass PHP user data to JavaScript
        const currentUser = {
            id: <?php echo json_encode($_SESSION['user_id']); ?>,
            username: <?php echo json_encode($user['username']); ?>
        };
    </script>
    <script src="/assets/js/feed.js"></script>
</body>
</html>