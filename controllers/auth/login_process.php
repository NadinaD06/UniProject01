<?php
// Start session
session_start();

// Include database connection
require_once('../../config/database.php');

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
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password']; // Password will be verified with password_verify, no need to sanitize
        $rememberMe = isset($_POST['rememberMe']) ? true : false;
        
        // Validate input
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username/email and password.');
        }
        
        // Query to check user credentials
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, is_admin FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['last_activity'] = time();
                
                // Set remember me cookie if requested
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                    $stmt->bindParam(':user_id', $user['id']);
                    $stmt->bindParam(':token', $token);
                    $stmt->bindParam(':expires_at', date('Y-m-d H:i:s', $expiry));
                    $stmt->execute();
                    
                    setcookie('remember_token', $token, $expiry, '/', '', false, true);
                }
                
                // Return success response
                echo json_encode([
                    'success' => true, 
                    'message' => 'Login successful!',
                    'redirect' => '../gui/feed.html'
                ]);
            } else {
                // Incorrect password
                throw new Exception('Incorrect username or password.');
            }
        } else {
            // User not found
            throw new Exception('Incorrect username or password.');
        }
    } catch (Exception $e) {
        // Return error response
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>