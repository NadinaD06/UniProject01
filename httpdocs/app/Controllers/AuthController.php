<?php
/**
* app/Controllers/AuthController.php
**/

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use PDO;
use PDOException;

class AuthController extends Controller {
    private $user;
    private $authService;
    
    public function __construct(\PDO $db) {
        parent::__construct($db);
        $this->user = new User($this->db);
        $this->authService = new AuthService();
    }
    
    /**
     * Display login page
     */
    public function showLogin() {
        // Check if already logged in
        if ($this->authService->check()) {
            return $this->redirect('/feed');
        }
        
        return $this->view('auth/login', [
            'csrf_token' => $this->authService->generateCsrfToken()
        ]);
    }
    
    /**
     * Process login request
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            
            try {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    return $this->redirect('/home');
                } else {
                    $_SESSION['errors'] = ['Invalid email or password'];
                    return $this->redirect('/login');
                }
            } catch (PDOException $e) {
                $_SESSION['errors'] = ['An error occurred. Please try again.'];
                return $this->redirect('/login');
            }
        }
    }
    
    /**
     * Display registration page
     */
    public function showRegister() {
        // Check if already logged in
        if ($this->authService->check()) {
            return $this->redirect('/feed');
        }
        
        return $this->view('auth/register', [
            'csrf_token' => $this->authService->generateCsrfToken()
        ]);
    }
    
    /**
     * Process registration request
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
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
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$username, $email, $hashed_password]);
                        
                        $_SESSION['success'] = 'Registration successful! Please login.';
                        return $this->redirect('/login');
                    }
                } catch (PDOException $e) {
                    $errors[] = 'An error occurred. Please try again.';
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['form_data'] = [
                    'username' => $username,
                    'email' => $email
                ];
                return $this->redirect('/register');
            }
        }
    }
    
    /**
     * Process logout request
     */
    public function logout() {
        session_destroy();
        return $this->redirect('/login');
    }
    
    /**
     * Display forgot password page
     */
    public function showForgotPassword() {
        // Check if already logged in
        if ($this->authService->check()) {
            return $this->redirect('/feed');
        }
        
        return $this->view('auth/forgot-password', [
            'csrf_token' => $this->authService->generateCsrfToken()
        ]);
    }
    
    /**
     * Process forgot password request
     */
    public function forgotPassword() {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/forgot-password');
        }
        
        // Get input data
        $data = $this->request->all();
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || !$this->authService->validateCsrfToken($data['csrf_token'])) {
            return $this->error('Invalid CSRF token', [], 403);
        }
        
        // Validate required fields
        if (!isset($data['email']) || empty($data['email'])) {
            if ($this->request->isAjax()) {
                return $this->error('Email is required');
            }
            
            $_SESSION['error'] = 'Email is required';
            return $this->redirect('/forgot-password');
        }
        
        // Find user by email
        $user = $this->user->findBy('email', $data['email']);
        
        if (!$user) {
            if ($this->request->isAjax()) {
                return $this->error('No account found with that email address');
            }
            
            $_SESSION['error'] = 'No account found with that email address';
            return $this->redirect('/forgot-password');
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        try {
            $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // TODO: Send reset email
            
            if ($this->request->isAjax()) {
                return $this->success([], 'Password reset instructions have been sent to your email');
            }
            
            $_SESSION['success'] = 'Password reset instructions have been sent to your email';
            return $this->redirect('/login');
        } catch (PDOException $e) {
            if ($this->request->isAjax()) {
                return $this->error('An error occurred. Please try again.');
            }
            
            $_SESSION['error'] = 'An error occurred. Please try again.';
            return $this->redirect('/forgot-password');
        }
    }
    
    /**
     * Display reset password page
     */
    public function showResetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            return $this->redirect('/login');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['error'] = 'Invalid or expired reset token';
                return $this->redirect('/login');
            }
            
            return $this->view('auth/reset-password', [
                'token' => $token,
                'csrf_token' => $this->authService->generateCsrfToken()
            ]);
        } catch (PDOException $e) {
            $_SESSION['error'] = 'An error occurred. Please try again.';
            return $this->redirect('/login');
        }
    }
    
    /**
     * Process reset password request
     */
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/login');
        }
        
        $data = $this->request->all();
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || !$this->authService->validateCsrfToken($data['csrf_token'])) {
            return $this->error('Invalid CSRF token', [], 403);
        }
        
        // Validate required fields
        if (!isset($data['token']) || empty($data['token'])) {
            return $this->error('Invalid reset token');
        }
        
        if (!isset($data['password']) || empty($data['password'])) {
            return $this->error('Password is required');
        }
        
        if (!isset($data['confirm_password']) || $data['password'] !== $data['confirm_password']) {
            return $this->error('Passwords do not match');
        }
        
        try {
            // Find user with valid reset token
            $stmt = $this->db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$data['token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return $this->error('Invalid or expired reset token');
            }
            
            // Update password and clear reset token
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            
            if ($this->request->isAjax()) {
                return $this->success([], 'Password has been reset successfully');
            }
            
            $_SESSION['success'] = 'Password has been reset successfully';
            return $this->redirect('/login');
        } catch (PDOException $e) {
            if ($this->request->isAjax()) {
                return $this->error('An error occurred. Please try again.');
            }
            
            $_SESSION['error'] = 'An error occurred. Please try again.';
            return $this->redirect('/login');
        }
    }
}