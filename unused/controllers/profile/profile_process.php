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

// Get current user ID
$user_id = $_SESSION['user_id'];

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Handle different actions based on request method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET request - fetch user information
    
    // Determine which user to fetch (current user or specified user)
    $profile_id = isset($_GET['id']) ? intval($_GET['id']) : $user_id;
    
    try {
        // Fetch user details
        $stmt = $conn->prepare("
            SELECT 
                id, username, email, full_name, profile_picture, cover_image, 
                bio, website, is_verified, created_at
            FROM users 
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $profile_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $response['message'] = 'User not found';
            echo json_encode($response);
            exit;
        }
        
        // Get follower and following counts
        $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = :user_id");
        $stmt->bindParam(':user_id', $profile_id);
        $stmt->execute();
        $followers_count = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = :user_id");
        $stmt->bindParam(':user_id', $profile_id);
        $stmt->execute();
        $following_count = $stmt->fetchColumn();
        
        // Check if current user is following this profile
        $is_following = false;
        if ($profile_id !== $user_id) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
            $stmt->bindParam(':follower_id', $user_id);
            $stmt->bindParam(':followed_id', $profile_id);
            $stmt->execute();
            $is_following = ($stmt->fetchColumn() > 0);
        }
        
        // Build the user data response
        $response = [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'profile_picture' => $user['profile_picture'] ? $user['profile_picture'] : '/api/placeholder/200/200', 
                'cover_image' => $user['cover_image'] ? $user['cover_image'] : '/api/placeholder/1200/300',
                'bio' => $user['bio'],
                'website' => $user['website'],
                'is_verified' => (bool)$user['is_verified'],
                'is_following' => $is_following,
                'followers_count' => $followers_count,
                'following_count' => $following_count,
                'joined_at' => date('F Y', strtotime($user['created_at'])),
                'is_current_user' => ($profile_id === $user_id)
            ]
        ];
        
        // If this is the current user, include email
        if ($profile_id === $user_id) {
            $response['user']['email'] = $user['email'];
        }
    } catch (PDOException $e) {
        // Log error
        error_log("Profile fetch error: " . $e->getMessage());
        $response['message'] = 'An error occurred. Please try again.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST request - update user information or follow/unfollow
    
    // Check what action to perform
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            // Update user profile information
            $full_name = trim($_POST['full_name'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $website = trim($_POST['website'] ?? '');
            
            try {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET full_name = :full_name, bio = :bio, website = :website 
                    WHERE id = :user_id
                ");
                
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':bio', $bio);
                $stmt->bindParam(':website', $website);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Profile updated successfully';
                } else {
                    $response['message'] = 'Failed to update profile';
                }
            } catch (PDOException $e) {
                // Log error
                error_log("Profile update error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        case 'update_email':
            // Update user email
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email address';
                break;
            }
            
            try {
                // Verify password
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $password_hash = $stmt->fetchColumn();
                
                if (!password_verify($password, $password_hash)) {
                    $response['message'] = 'Incorrect password';
                    break;
                }
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $response['message'] = 'Email already in use by another account';
                    break;
                }
                
                // Update email
                $stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :user_id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Email updated successfully';
                } else {
                    $response['message'] = 'Failed to update email';
                }
            } catch (PDOException $e) {
                // Log error
                error_log("Email update error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        case 'change_password':
            // Change user password
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate passwords
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $response['message'] = 'All fields are required';
                break;
            }
            
            if ($new_password !== $confirm_password) {
                $response['message'] = 'New passwords do not match';
                break;
            }
            
            if (strlen($new_password) < 8) {
                $response['message'] = 'Password must be at least 8 characters';
                break;
            }
            
            try {
                // Verify current password
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $current_hash = $stmt->fetchColumn();
                
                if (!password_verify($current_password, $current_hash)) {
                    $response['message'] = 'Current password is incorrect';
                    break;
                }
                
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
                $stmt->bindParam(':password_hash', $new_hash);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Password changed successfully';
                } else {
                    $response['message'] = 'Failed to change password';
                }
            } catch (PDOException $e) {
                // Log error
                error_log("Password change error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        case 'toggle_follow':
            // Follow or unfollow a user
            $target_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            if ($target_id <= 0 || $target_id === $user_id) {
                $response['message'] = 'Invalid user ID';
                break;
            }
            
            try {
                // Check if already following
                $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
                $stmt->bindParam(':follower_id', $user_id);
                $stmt->bindParam(':followed_id', $target_id);
                $stmt->execute();
                $follow_id = $stmt->fetchColumn();
                
                if ($follow_id) {
                    // Unfollow
                    $stmt = $conn->prepare("DELETE FROM follows WHERE id = :id");
                    $stmt->bindParam(':id', $follow_id);
                    $stmt->execute();
                    
                    $is_following = false;
                    $action_text = 'unfollowed';
                } else {
                    // Follow
                    $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id, created_at) VALUES (:follower_id, :followed_id, NOW())");
                    $stmt->bindParam(':follower_id', $user_id);
                    $stmt->bindParam(':followed_id', $target_id);
                    $stmt->execute();
                    
                    $is_following = true;
                    $action_text = 'followed';
                    
                    // Create notification for the followed user
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, actor_id, message, created_at) 
                        VALUES (:user_id, 'follow', :actor_id, :message, NOW())
                    ");
                    
                    // Get follower username
                    $stmt2 = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
                    $stmt2->bindParam(':user_id', $user_id);
                    $stmt2->execute();
                    $follower_username = $stmt2->fetchColumn();
                    
                    $message = $follower_username . ' started following you';
                    
                    $stmt->bindParam(':user_id', $target_id);
                    $stmt->bindParam(':actor_id', $user_id);
                    $stmt->bindParam(':message', $message);
                    $stmt->execute();
                }
                
                // Get updated follower count
                $stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = :user_id");
                $stmt->bindParam(':user_id', $target_id);
                $stmt->execute();
                $follower_count = $stmt->fetchColumn();
                
                $response['success'] = true;
                $response['is_following'] = $is_following;
                $response['follower_count'] = $follower_count;
                $response['message'] = 'User ' . $action_text . ' successfully';
            } catch (PDOException $e) {
                // Log error
                error_log("Follow toggle error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        case 'update_profile_picture':
            // Update profile picture
            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] != UPLOAD_ERR_OK) {
                $response['message'] = 'No image uploaded or upload error';
                break;
            }
            
            $file = $_FILES['profile_picture'];
            
            // Validate file size
            if ($file['size'] > $config['MAX_FILE_SIZE']) {
                $response['message'] = 'File size exceeds the maximum limit (' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB).';
                break;
            }
            
            // Validate file type
            $file_type = mime_content_type($file['tmp_name']);
            if (!in_array($file_type, $config['ALLOWED_IMAGE_TYPES'])) {
                $response['message'] = 'Only ' . implode(', ', array_map(function($type) {
                    return strtoupper(str_replace('image/', '', $type));
                }, $config['ALLOWED_IMAGE_TYPES'])) . ' files are allowed.';
                break;
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = $config['UPLOAD_DIR'] . '/profiles/' . $user_id;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'profile_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_path = $upload_dir . '/' . $filename;
            $image_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $response['message'] = 'Failed to upload file. Please try again.';
                break;
            }
            
            try {
                // Get current profile picture
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $old_image = $stmt->fetchColumn();
                
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
                $stmt->bindParam(':profile_picture', $image_url);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    // Delete old image if exists
                    if ($old_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_image)) {
                        @unlink($_SERVER['DOCUMENT_ROOT'] . $old_image);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Profile picture updated successfully';
                    $response['image_url'] = $image_url;
                } else {
                    // Delete uploaded file if database update fails
                    @unlink($file_path);
                    $response['message'] = 'Failed to update profile picture. Please try again.';
                }
            } catch (PDOException $e) {
                // Delete uploaded file if database error occurs
                @unlink($file_path);
                
                // Log error
                error_log("Profile picture update error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        case 'update_cover_image':
            // Update cover image
            if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] != UPLOAD_ERR_OK) {
                $response['message'] = 'No image uploaded or upload error';
                break;
            }
            
            $file = $_FILES['cover_image'];
            
            // Validate file size
            if ($file['size'] > $config['MAX_FILE_SIZE']) {
                $response['message'] = 'File size exceeds the maximum limit (' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB).';
                break;
            }
            
            // Validate file type
            $file_type = mime_content_type($file['tmp_name']);
            if (!in_array($file_type, $config['ALLOWED_IMAGE_TYPES'])) {
                $response['message'] = 'Only ' . implode(', ', array_map(function($type) {
                    return strtoupper(str_replace('image/', '', $type));
                }, $config['ALLOWED_IMAGE_TYPES'])) . ' files are allowed.';
                break;
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = $config['UPLOAD_DIR'] . '/profiles/' . $user_id;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'cover_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_path = $upload_dir . '/' . $filename;
            $image_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $response['message'] = 'Failed to upload file. Please try again.';
                break;
            }
            
            try {
                // Get current cover image
                $stmt = $conn->prepare("SELECT cover_image FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $old_image = $stmt->fetchColumn();
                
                // Update cover image in database
                $stmt = $conn->prepare("UPDATE users SET cover_image = :cover_image WHERE id = :user_id");
                $stmt->bindParam(':cover_image', $image_url);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    // Delete old image if exists
                    if ($old_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_image)) {
                        @unlink($_SERVER['DOCUMENT_ROOT'] . $old_image);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Cover image updated successfully';
                    $response['image_url'] = $image_url;
                } else {
                    // Delete uploaded file if database update fails
                    @unlink($file_path);
                    $response['message'] = 'Failed to update cover image. Please try again.';
                }
            } catch (PDOException $e) {
                // Delete uploaded file if database error occurs
                @unlink($file_path);
                
                // Log error
                error_log("Cover image update error: " . $e->getMessage());
                $response['message'] = 'An error occurred. Please try again.';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);