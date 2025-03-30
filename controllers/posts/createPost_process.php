<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Include database connection
require_once '../../database.php';
$config = include_once '../../config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $comments_enabled = isset($_POST['comments_enabled']) ? 1 : 0;
    $used_ai = isset($_POST['used_ai']) ? 1 : 0;
    $ai_tools = $used_ai ? trim($_POST['ai_tools'] ?? '') : '';
    $nsfw = isset($_POST['nsfw']) ? 1 : 0;
    
    // Validate inputs
    if (empty($title)) {
        $response['message'] = 'Please enter a title for your artwork.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($category)) {
        $response['message'] = 'Please select a category for your artwork.';
        echo json_encode($response);
        exit;
    }
    
    // Process image upload
    if (!isset($_FILES['artwork_image']) || $_FILES['artwork_image']['error'] != UPLOAD_ERR_OK) {
        $response['message'] = 'Please upload an image of your artwork.';
        echo json_encode($response);
        exit;
    }
    
    $file = $_FILES['artwork_image'];
    
    // Validate file size
    if ($file['size'] > $config['MAX_FILE_SIZE']) {
        $response['message'] = 'File size exceeds the maximum limit (' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB).';
        echo json_encode($response);
        exit;
    }
    
    // Validate file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $config['ALLOWED_IMAGE_TYPES'])) {
        $response['message'] = 'Only ' . implode(', ', array_map(function($type) {
            return strtoupper(str_replace('image/', '', $type));
        }, $config['ALLOWED_IMAGE_TYPES'])) . ' files are allowed.';
        echo json_encode($response);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = $config['UPLOAD_DIR'] . '/artworks/' . date('Y/m');
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid('art_') . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_path = $upload_dir . '/' . $filename;
    $image_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        $response['message'] = 'Failed to upload file. Please try again.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Insert post into database
        $stmt = $conn->prepare("
            INSERT INTO posts (
                user_id, title, description, image_url, category, tags, 
                comments_enabled, used_ai, ai_tools, nsfw, created_at
            ) VALUES (
                :user_id, :title, :description, :image_url, :category, :tags, 
                :comments_enabled, :used_ai, :ai_tools, :nsfw, NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':comments_enabled', $comments_enabled);
        $stmt->bindParam(':used_ai', $used_ai);
        $stmt->bindParam(':ai_tools', $ai_tools);
        $stmt->bindParam(':nsfw', $nsfw);
        
        if ($stmt->execute()) {
            $post_id = $conn->lastInsertId();
            
            // Success
            $response['success'] = true;
            $response['message'] = 'Your artwork has been posted successfully!';
            $response['post_id'] = $post_id;
        } else {
            // Delete uploaded file if database insert fails
            @unlink($file_path);
            $response['message'] = 'Failed to save your post. Please try again.';
        }
    } catch (PDOException $e) {
        // Delete uploaded file if database error occurs
        @unlink($file_path);
        
        // Log error
        error_log("Create post error: " . $e->getMessage());
        $response['message'] = 'An error occurred while saving your post. Please try again.';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>