<?php
/**
 * Authentication Controller
 * Handles user authentication operations
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/utilities.php';

/**
 * Register a new user
 * 
 * @param array $data User registration data
 * @return array Response with success/error message
 */
function register_user($data) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    // Validate inputs
    $username = sanitize_input($data['username'] ?? '');
    $email = sanitize_input($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm_password = $data['confirmPassword'] ?? '';
    $age = intval($data['age'] ?? 0);
    $bio = sanitize_input($data['bio'] ?? '');
    $interests = $data['interests'] ?? '[]';
    
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
    } elseif (strlen($password) < $config['PASSWORD_MIN_LENGTH']) {
        $errors['password'] = 'Password must be at least ' . $config['PASSWORD_MIN_LENGTH'] . ' characters.';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }
    
    // Validate age
    if ($age < 16) {
        $errors['age'] = 'You must be at least 16 years old to register.';
    }
    
    // If validation errors exist, return them
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors,
            'message' => 'Please fix the errors in the form.'
        ];
    }
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'errors' => ['username' => 'This username is already taken.'],
                'message' => 'Please choose a different username.'
            ];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'errors' => ['email' => 'This email is already registered.'],
                'message' => 'Please use a different email or login to your existing account.'
            ];
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (
                username, 
                email, 
                password_hash, 
                age, 
                bio, 
                interests, 
                created_at
            ) VALUES (
                :username, 
                :email, 
                :password_hash, 
                :age, 
                :bio, 
                :interests, 
                NOW()
            )
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':interests', $interests);
        
        if ($stmt->execute()) {
            // Get the new user's ID
            $user_id = $conn->lastInsertId();
            
            // Log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['last_activity'] = time();
            
            return [
                'success' => true,
                'message' => 'Registration successful! Welcome to ArtSpace!',
                'redirect' => 'feed.php',
                'user' => [
                    'id' => $user_id,
                    'username' => $username
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.'
        ];
    }
}

/**
 * Log in a user
 * 
 * @param array $data Login data
 * @param bool $remember_me Whether to remember the user
 * @return array Response with success/error message
 */
function login_user($data, $remember_me = false) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    // Validate inputs
    $username = sanitize_input($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Please enter both username/email and password.'
        ];
    }
    
    try {
        // Query to check user credentials
        $stmt = $conn->prepare("
            SELECT id, username, email, password_hash, is_admin 
            FROM users 
            WHERE username = :username OR email = :email
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Incorrect username or password.'
            ];
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['last_activity'] = time();
            
            // Set remember me cookie if requested
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Delete any existing tokens for this user
                $stmt = $conn->prepare("
                    DELETE FROM auth_tokens 
                    WHERE user_id = :user_id
                ");
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->execute();
                
                // Create new token
                $stmt = $conn->prepare("
                    INSERT INTO auth_tokens (
                        user_id, 
                        token, 
                        expires_at
                    ) VALUES (
                        :user_id, 
                        :token, 
                        :expires_at
                    )
                ");
                
                $expires_at = date('Y-m-d H:i:s', $expiry);
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires_at', $expires_at);
                $stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, $expiry, '/', '', false, true);
            }
            
            // Update last login time
            $stmt = $conn->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $user['id']);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => 'feed.php',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Incorrect username or password.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Log out a user
 * 
 * @return array Response with success/error message
 */
function logout_user() {
    // Remove remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        global $conn;
        $token = $_COOKIE['remember_token'];
        
        try {
            // Delete token from database
            $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            // Remove cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        } catch (PDOException $e) {
            // Log error but continue with logout
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    // Clear session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Logged out successfully.'
    ];
}

/**
 * Check if a user is authenticated through session or remember me cookie
 * 
 * @return bool True if user is authenticated
 */
function check_auth() {
    global $conn;
    $config = include_once __DIR__ . '/../config.php';
    
    // Check if user is logged in via session
    if (isset($_SESSION['user_id'])) {
        // Check for session timeout
        $session_timeout = $config['SESSION_TIMEOUT'];
        $last_activity = $_SESSION['last_activity'] ?? 0;
        
        if (time() - $last_activity < $session_timeout) {
            // Session is valid, update last activity
            $_SESSION['last_activity'] = time();
            return true;
        } else {
            // Session expired, destroy it
            session_unset();
            session_destroy();
        }
    }
    
    // Check for remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.email, u.is_admin
                FROM users u
                JOIN auth_tokens t ON u.id = t.user_id
                WHERE t.token = :token AND t.expires_at > NOW()
            ");
            
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user) {
                // User found, create new session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['last_activity'] = time();
                
                return true;
            }
        } catch (PDOException $e) {
            error_log("Remember token check error: " . $e->getMessage());
        }
        
        // Invalid token, remove cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    return false;
}

/**
 * Request password reset
 * 
 * @param string $email User email
 * @return array Response with success/error message
 */
function request_password_reset($email) {
    global $conn;
    
    $email = sanitize_input($email);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    }
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            // Don't reveal if email exists for security
            return [
                'success' => true,
                'message' => 'If your email is registered, you will receive password reset instructions.'
            ];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
        
        // Delete any existing tokens for this user
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        
        // Create new token
        $stmt = $conn->prepare("
            INSERT INTO password_resets (
                user_id, 
                token, 
                expires_at
            ) VALUES (
                :user_id, 
                :token, 
                :expires_at
            )
        ");
        
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires_at);
        
        if ($stmt->execute()) {
            // In a real application, send email with reset link
            // For demo purposes, just return success
            
            return [
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email.',
                'debug_token' => $token // Remove in production
            ];
        } else {
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Password reset request error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Reset password using token
 * 
 * @param string $token Reset token
 * @param string $password New password
 * @param string $confirm_password Confirm new password
 * @return array Response with success/error message
 */
function reset_password($token, $password, $confirm_password) {
    global $conn;
    
    // Get config
    $config = include_once __DIR__ . '/../config.php';
    
    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Invalid password reset token.'
        ];
    }
    
    if (empty($password) || strlen($password) < $config['PASSWORD_MIN_LENGTH']) {
        return [
            'success' => false,
            'message' => 'Password must be at least ' . $config['PASSWORD_MIN_LENGTH'] . ' characters.'
        ];
    }
    
    if ($password !== $confirm_password) {
        return [
            'success' => false,
            'message' => 'Passwords do not match.'
        ];
    }
    
    try {
        // Verify token exists and is not expired
        $stmt = $conn->prepare("
            SELECT pr.user_id, u.username
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = :token AND pr.expires_at > NOW()
        ");
        
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Invalid or expired password reset token.'
            ];
        }
        
        $user_id = $result['user_id'];
        
        // Update password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET password_hash = :password_hash 
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            // Delete used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Your password has been reset successfully. You can now log in.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Password reset error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}