<?php
namespace App\Auth;

class UserAuth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($username, $email, $password, $age = null, $bio = null, $interests = null) {
        try {
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, age, bio, interests)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $passwordHash, $age, $bio, $interests]);
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        try {
            // Get user by username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Start session and store user data
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            return null;
        }
    }
}
?> 