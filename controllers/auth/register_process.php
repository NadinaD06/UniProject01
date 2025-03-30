<?php
// Start session
session_start();

// Include database connection
require_once '../../database.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $age = intval($_POST['age'] ?? 0);
    $bio = trim($_POST['bio'] ?? '');
    $interests = $_POST['interests'] ?? '[]';
    
    // Validate inputs
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores.';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors['password'] = 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }
    
    // Validate age
    if ($age < 16) {
        $errors['age'] = 'You must be at least 16 years old to register.';
    }
    
    // If there are validation errors, return them
    if (count($errors) > 0) {
        $response['errors'] = $errors;
        $response['message'] = 'Please fix the errors in the form.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $response['errors']['username'] = 'This username is already taken.';
            $response['message'] = 'Please choose a different username.';
            echo json_encode($response);
            exit;
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $response['errors']['email'] = 'This email is already registered.';
            $response['message'] = 'Please use a different email or login to your existing account.';
            echo json_encode($response);
            exit;
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash, age, bio, interests, created_at) 
            VALUES (:username, :email, :password_hash, :age, :bio, :interests, NOW())
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':interests', $interests);
        
        if ($stmt->execute()) {
            // Registration successful, get the new user's ID
            $user_id = $conn->lastInsertId();
            
            // Log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['last_activity'] = time();
            
            $response['success'] = true;
            $response['message'] = 'Registration successful! Redirecting to your feed...';
            $response['redirect'] = 'feed.html';
        } else {
            $response['message'] = 'An error occurred during registration. Please try again.';
        }
    } catch (PDOException $e) {
        // Log error
        error_log("Registration error: " . $e->getMessage());
        $response['message'] = 'An error occurred during registration. Please try again.';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>