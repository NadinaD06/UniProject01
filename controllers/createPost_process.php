<?php
// Start session
session_start();

// Include database connection
require_once('../../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not authenticated, redirect to login page
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_input($_POST['category']) : '';
        $tags = isset($_POST['tags']) ? sanitize_input($_POST['tags']) : '';
        $comments_enabled = isset($_POST['comments_enabled']) ? 1 : 0;
        $used_ai = isset($_POST['used_ai']) ? 1 : 0;
        $ai_tools = isset($_POST['ai_tools']) ? sanitize_input($_POST['ai_tools']) : '';
        $nsfw = isset($_POST['nsfw']) ? 1 : 0;
        
        // Validate input
        if (empty($title)) {
            throw new Exception('Please enter a title for your artwork.');
        }
        
        if (empty($category)) {
            throw new Exception('Please select a category for your artwork.');
        }
        
        // Handle file upload
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['artwork_image']['tmp_name'];
            $file_name = $_FILES['artwork_image']['name'];
            $file_size = $_FILES['artwork_image']['size'];
            $file_type = $_FILES['artwork_image']['type'];
            
            // Check file size (limit to 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB in bytes
            if ($file_size > $max_size) {
                throw new Exception('File size exceeds the maximum limit (10MB).');
            }
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Only JPG, PNG, and GIF files are allowed.');
            }
            
            // Generate unique filename to prevent overwriting
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('artwork_') . '.' . $file_extension;
            
            // Define upload directory
            $upload_dir = '../../uploads/artworks/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_file_name;
            
            // Move the file to the uploads directory
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // File uploaded successfully
                $image_url = '../uploads/artworks/' . $new_file_name; // URL for database storage
            } else {
                throw new Exception('Failed to upload the file. Please try again.');
            }
        } else {
            throw new Exception('Please upload an image of your artwork.');
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert post into database
        $stmt = $conn->prepare("
            INSERT INTO posts (
                user_id, 
                content, 
                image_url, 
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $title, 
            $image_url
        ]);
        
        // Get the post ID
        $post_id = $conn->lastInsertId();
        
        // Insert additional artwork details into artwork_details table
        // (assuming you have a separate table for artwork-specific details)
        $details_stmt = $conn->prepare("
            INSERT INTO artwork_details (
                post_id,
                description,
                category,
                tags,
                comments_enabled,
                used_ai,
                ai_tools,
                nsfw
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $details_stmt->execute([
            $post_id,
            $description,
            $category,
            $tags,
            $comments_enabled,
            $used_ai,
            $ai_tools,
            $nsfw
        ]);
        
        // Process tags
        if (!empty($tags)) {
            $tag_array = array_map('trim', explode(',', $tags));
            
            foreach ($tag_array as $tag) {
                // Skip empty tags
                if (empty($tag)) continue;
                
                // Check if tag exists
                $tag_check = $conn->prepare("SELECT id FROM tags WHERE name = ?");
                $tag_check->execute([$tag]);
                
                if ($tag_check->rowCount() > 0) {
                    // Tag exists, get its ID
                    $tag_row = $tag_check->fetch();
                    $tag_id = $tag_row['id'];
                } else {
                    // Tag doesn't exist, create it
                    $tag_insert = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
                    $tag_insert->execute([$tag]);
                    $tag_id = $conn->lastInsertId();
                }
                
                // Link tag to post
                $post_tag = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                $post_tag->execute([$post_id, $tag_id]);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Your artwork has been posted successfully!',
            'post_id' => $post_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Return error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Not a POST request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>