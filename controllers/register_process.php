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
        // Validate and sanitize input data
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password']; // Will be hashed, so no sanitization
        $confirmPassword = $_POST['confirmPassword'];
        
        // Get additional user information
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $bio = isset($_POST['bio']) ? sanitize_input($_POST['bio']) : '';
        $interests = isset($_POST['interests']) ? $_POST['interests'] : '[]';
        
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            throw new Exception('Username must be 3-20 characters and contain only letters, numbers, and underscores.');
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Validate password
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            throw new Exception('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.');
        }
        
        // Confirm passwords match
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }
        
        // Validate age
        if ($age !== null && $age < 16) {
            throw new Exception('You must be at least 16 years old to register.');
        }
        
        // Validate interests (ensure it's valid JSON)
        if (!json_decode($interests)) {
            // If not valid JSON, try to decode as string
            if (is_string($interests)) {
                $interests = json_encode([$interests]);
            } else {
                $interests = '[]';
            }
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username already taken. Please choose another one.');
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already registered. Please use a different email or login.');
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user data with additional fields
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash, age, bio, interests, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $email, $hashedPassword, $age, $bio, $interests]);
        
        // Get the new user ID
        $userId = $conn->lastInsertId();
        
        // Start session for the new user
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        
        // Return success response
        echo json_encode(['success' => true, 'message' => 'Registration successful!', 'userId' => $userId]);
    } catch (Exception $e) {
        // Return error response
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>