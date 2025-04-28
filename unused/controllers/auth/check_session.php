<?php
// Start session
session_start();

// Include database connection
require_once '../../database.php';
$config = include_once '../../config.php';

// Initialize response
$response = [
    'loggedIn' => false,
    'user' => null
];

// Check if user is logged in via session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check if session is expired
    $session_timeout = $config['SESSION_TIMEOUT'] ?? 3600; // Default 1 hour
    $last_activity = $_SESSION['last_activity'] ?? 0;
    
    if (time() - $last_activity < $session_timeout) {
        // Session is valid, update last activity
        $_SESSION['last_activity'] = time();
        
        // Get user data
        try {
            $stmt = $conn->prepare("
                SELECT id, username, email, profile_picture
                FROM users 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $response['loggedIn'] = true;
                $response['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'profile_picture' => $user['profile_picture'] ? $user['profile_picture'] : null
                ];
                
                // Update user's last active timestamp
                $stmt = $conn->prepare("UPDATE users SET last_active = NOW(), is_online = 1 WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            // Log error
            error_log("Session check error: " . $e->getMessage());
        }
    } else {
        // Session expired, destroy it
        session_unset();
        session_destroy();
    }
} else {
    // Check for remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.email, u.profile_picture
                FROM users u
                JOIN auth_tokens t ON u.id = t.user_id
                WHERE t.token = :token AND t.expires_at > NOW()
            ");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // User found, create new session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                
                $response['loggedIn'] = true;
                $response['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'profile_picture' => $user['profile_picture'] ? $user['profile_picture'] : null
                ];
                
                // Update user's last active timestamp
                $stmt = $conn->prepare("UPDATE users SET last_active = NOW(), is_online = 1 WHERE id = :id");
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            // Log error
            error_log("Remember token check error: " . $e->getMessage());
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>