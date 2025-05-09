<?php
/**
* app/Controllers/AuthController.php
**/

namespace App\Controllers;

use App\Models\User;
use PDO;
use PDOException;

class AuthController extends Controller {
    private $user;
    
    public function __construct(\PDO $db) {
        parent::__construct($db);
        $this->user = new User($db);
    }
    
    /**
     * Process login request
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            try {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'] ?? false;
                    
                    // Redirect to feed or home page
                    header('Location: /feed');
                    exit;
                } else {
                    $_SESSION['errors'] = ['Invalid email or password'];
                    header('Location: /login');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['errors'] = ['An error occurred. Please try again.'];
                header('Location: /login');
                exit;
            }
        }
    }
    
    /**
     * Process registration request
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = [];

            // Validate input
            if (empty($username)) {
                $errors[] = 'Username is required';
            }
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }

            if (empty($errors)) {
                try {
                    // Check if email already exists
                    $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Email already registered';
                    } else {
                        // Check if username already exists
                        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        if ($stmt->fetch()) {
                            $errors[] = 'Username already taken';
                        } else {
                            // Create new user
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $this->db->prepare("
                                INSERT INTO users (username, email, password, created_at) 
                                VALUES (?, ?, ?, NOW())
                            ");
                            $stmt->execute([$username, $email, $hashed_password]);

                            $_SESSION['success'] = 'Registration successful! Please login.';
                            header('Location: /login');
                            exit;
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = 'An error occurred. Please try again.';
                    // Log the error for debugging
                    error_log("Registration error: " . $e->getMessage());
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['form_data'] = [
                    'username' => $username,
                    'email' => $email
                ];
                header('Location: /register');
                exit;
            }
        }
    }
    
    /**
     * Process logout request
     */
    public function logout() {
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        header('Location: /login');
        exit;
    }
}