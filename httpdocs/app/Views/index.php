<?php
/**
 * Main layout template for ArtSpace
 * All views will extend this template
 */

// Define default values if not set
$page_title = $page_title ?? 'ArtSpace - Connect, Create, Inspire';
$page_description = $page_description ?? 'Join ArtSpace, the social media platform for artists to share, connect, and grow their creative skills.';
$page_css = $page_css ?? 'main';
$page_js = $page_js ?? null;
$body_class = $body_class ?? '';

// Start output buffering to capture the content
if (!isset($content)) {
    ob_start();
}

// Start session
session_start();

// Load configuration
$config = require_once 'config/config.php';

// Load database connection
require_once 'get_db_connection.php';

// Load router
require_once 'app/router.php';

// Handle routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = substr($request_uri, strlen($base_path));

// Remove query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Initialize controllers
$authController = new AuthController($pdo);
$postController = new PostController($pdo);
$profileController = new ProfileController($pdo);
$settingsController = new SettingsController($pdo);

// Route handling
switch ($path) {
    // Auth routes
    case '/':
    case '/login':
        $authController->login();
        break;
    case '/register':
        $authController->register();
        break;
    case '/logout':
        $authController->logout();
        break;

    // Post routes
    case '/feed':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->index();
        break;
    case '/post/create':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->create();
        break;
    case '/post/like':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->like();
        break;
    case '/post/comment':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->comment();
        break;

    // Profile routes
    case (preg_match('/^\/profile\/([^\/]+)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $profileController->show($matches[1]);
        break;

    // Settings routes
    case '/settings':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->index();
        break;
    case '/settings/update-profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->updateProfile();
        break;
    case '/settings/update-account':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->updateAccount();
        break;

    // 404 - Not Found
    default:
        header("HTTP/1.0 404 Not Found");
        require_once 'app/Views/404.php';
        break;
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require login
requireLogin();

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_post') {
        $content = trim($_POST['content'] ?? '');
        $location_name = trim($_POST['location_name'] ?? '');
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                }
            }
        }
        
        if (!empty($content) || $image_path) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO posts (user_id, content, image_path, location_name, latitude, longitude)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    getCurrentUserId(),
                    $content,
                    $image_path,
                    $location_name,
                    $latitude,
                    $longitude
                ]);
                
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to create post";
            }
        }
    }
}

// Get posts for feed
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.username,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
               EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ? OR p.user_id IN (
            SELECT following_id FROM follows WHERE follower_id = ?
        )
        ORDER BY p.created_at DESC
    ");
    
    $stmt->execute([getCurrentUserId(), getCurrentUserId(), getCurrentUserId()]);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    $error = "Failed to load posts";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Base CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if ($page_css && $page_css !== 'main'): ?>
    <link href="/assets/css/<?php echo $page_css; ?>.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- CSRF Token for AJAX calls -->
    <script>
        const CSRF_TOKEN = "<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>";
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .post-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .post-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            resize: vertical;
        }
        .post-form input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .post-form button {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .post-form button:hover {
            background-color: #1557b0;
        }
        .post {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .post-content {
            margin-bottom: 15px;
        }
        .post-image {
            max-width: 100%;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .post-location {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .post-actions {
            display: flex;
            gap: 15px;
        }
        .post-actions button {
            background: none;
            border: none;
            color: #1a73e8;
            cursor: pointer;
            padding: 5px 10px;
        }
        .post-actions button:hover {
            text-decoration: underline;
        }
        .post-actions button.liked {
            color: #dc3545;
        }
        .comments {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .comment {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .comment-form {
            margin-top: 10px;
        }
        .comment-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        #map {
            height: 200px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
        }
        .nav-links a {
            color: #1a73e8;
            text-decoration: none;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="<?php echo $body_class ?>">
    <div class="container">
        <div class="header">
            <h1>Social Media Site</h1>
            <div class="nav-links">
                <a href="profile.php">Profile</a>
                <a href="messages.php">Messages</a>
                <a href="notifications.php">Notifications</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="post-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_post">
                <textarea name="content" placeholder="What's on your mind?" rows="3"></textarea>
                <input type="file" name="image" accept="image/*">
                <input type="text" name="location_name" id="location_name" placeholder="Location" readonly>
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <div id="map"></div>
                <button type="submit">Post</button>
            </form>
        </div>
        
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    <span><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
                </div>
                
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                
                <?php if ($post['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="post-image" alt="Post image">
                <?php endif; ?>
                
                <?php if ($post['location_name']): ?>
                    <div class="post-location">
                        📍 <?php echo htmlspecialchars($post['location_name']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="post-actions">
                    <button onclick="likePost(<?php echo $post['id']; ?>)" class="<?php echo $post['is_liked'] ? 'liked' : ''; ?>">
                        ❤️ <?php echo $post['like_count']; ?> Likes
                    </button>
                    <button onclick="toggleComments(<?php echo $post['id']; ?>)">
                        💬 <?php echo $post['comment_count']; ?> Comments
                    </button>
                </div>
                
                <div id="comments-<?php echo $post['id']; ?>" class="comments" style="display: none;">
                    <div class="comment-form">
                        <input type="text" placeholder="Write a comment..." onkeypress="submitComment(event, <?php echo $post['id']; ?>)">
                    </div>
                    <div id="comment-list-<?php echo $post['id']; ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Initialize map
        const map = L.map('map').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        let marker = null;
        
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            
            marker = L.marker(e.latlng).addTo(map);
            
            // Reverse geocode to get location name
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('location_name').value = data.display_name;
                    document.getElementById('latitude').value = e.latlng.lat;
                    document.getElementById('longitude').value = e.latlng.lng;
                });
        });
        
        // Like post
        function likePost(postId) {
            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        // Toggle comments
        function toggleComments(postId) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
            
            if (commentsDiv.style.display === 'block') {
                loadComments(postId);
            }
        }
        
        // Load comments
        function loadComments(postId) {
            fetch(`get_comments.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    const commentList = document.getElementById(`comment-list-${postId}`);
                    commentList.innerHTML = '';
                    
                    data.forEach(comment => {
                        const div = document.createElement('div');
                        div.className = 'comment';
                        div.innerHTML = `
                            <strong>${comment.username}</strong>
                            <p>${comment.content}</p>
                            <small>${comment.created_at}</small>
                        `;
                        commentList.appendChild(div);
                    });
                });
        }
        
        // Submit comment
        function submitComment(event, postId) {
            if (event.key === 'Enter') {
                const input = event.target;
                const content = input.value.trim();
                
                if (content) {
                    fetch('add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&content=${encodeURIComponent(content)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            input.value = '';
                            loadComments(postId);
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>