<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Testing File Upload Functionality</h2>";

// Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/assets/images/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    try {
        $file = $_FILES['image'];
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File is too large. Maximum size is 5MB.');
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Get the test user
            $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id DESC LIMIT 1");
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('No test user found. Please run test_registration.php first.');
            }
            
            // Create post with uploaded image
            $imageUrl = '/assets/images/uploads/' . $fileName;
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, content, image_url, location, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user['id'],
                'Test post with uploaded image at ' . date('Y-m-d H:i:s'),
                $imageUrl,
                'Upload Test Location'
            ]);
            
            echo "<p style='color: green;'>File uploaded and post created successfully!</p>";
            echo "<p>Image URL: " . htmlspecialchars($imageUrl) . "</p>";
            
            // Display the uploaded image
            echo "<div style='margin: 20px 0;'>";
            echo "<h3>Uploaded Image:</h3>";
            echo "<img src='" . htmlspecialchars($imageUrl) . "' style='max-width: 500px;'>";
            echo "</div>";
        } else {
            throw new Exception('Failed to move uploaded file.');
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Display upload form
?>
<form method="POST" enctype="multipart/form-data" style="margin: 20px 0; padding: 20px; border: 1px solid #ccc;">
    <h3>Upload Test Image</h3>
    <p>Select an image to upload (JPG, PNG, or GIF, max 5MB):</p>
    <input type="file" name="image" accept="image/*" required>
    <br><br>
    <input type="submit" value="Upload Image">
</form>

<?php
// Display recent uploaded images
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.image_url LIKE '/assets/images/uploads/%'
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    if (!empty($posts)) {
        echo "<h3>Recent Uploaded Images:</h3>";
        foreach ($posts as $post) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Post by " . htmlspecialchars($post['username']) . "</strong></p>";
            echo "<p>" . htmlspecialchars($post['content']) . "</p>";
            if ($post['image_url']) {
                echo "<img src='" . htmlspecialchars($post['image_url']) . "' style='max-width: 300px; margin: 10px 0;'>";
                echo "<p>Image URL: " . htmlspecialchars($post['image_url']) . "</p>";
            }
            if ($post['location']) {
                echo "<p>Location: " . htmlspecialchars($post['location']) . "</p>";
            }
            echo "<p>Created: " . htmlspecialchars($post['created_at']) . "</p>";
            echo "<p>Likes: " . htmlspecialchars($post['like_count']) . "</p>";
            echo "<p>Comments: " . htmlspecialchars($post['comment_count']) . "</p>";
            echo "</div>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 