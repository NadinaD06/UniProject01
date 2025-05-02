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
    
    public function __construct() {
        parent::__construct();
        $this->user = new User($this->pdo);
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
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    header('Location: /home');
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
                    $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Email already registered';
                    } else {
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$username, $email, $hashed_password]);
                        
                        $_SESSION['success'] = 'Registration successful! Please login.';
                        header('Location: /login');
                        exit;
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
                header('Location: /register');
                exit;
            }
        }
    }
    
    /**
     * Process logout request
     */
    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
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
        $data = $this->getInputData();
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || !$this->authService->validateCsrfToken($data['csrf_token'])) {
            return $this->error('Invalid CSRF token', [], 403);
        }
        
        // Validate required fields
        if (!isset($data['email']) || empty($data['email'])) {
            if ($this->isAjaxRequest()) {
                return $this->error('Email is required');
            }
            
            $this->setFlashMessage('Email is required', 'error');
            return $this->redirect('/forgot-password');
        }
        
        // Find user by email
        $user = $this->user->findBy('email', $data['email']);
        
        if (!$user) {
            // For security reasons, don't disclose if email exists
            if ($this->isAjaxRequest()) {
                return $this->success([], 'If your email is registered, you will receive a password reset link shortly.');
            }
            
            $this->setFlashMessage('If your email is registered, you will receive a password reset link shortly.', 'success');
            return $this->redirect('/login');
        }
        
        // Generate password reset token
        $token = bin2hex(random_bytes(32));
        $expires = time() + (60 * 60); // 1 hour
        
        // Store token in database
        $this->user->storePasswordResetToken($user['id'], $token, $expires);
        
        // In a real application, send email with reset link
        // For this example, we'll just show a message
        
        if ($this->isAjaxRequest()) {
            return $this->success([], 'Password reset link has been sent to your email.');
        }
        
        $this->setFlashMessage('Password reset link has been sent to your email.', 'success');
        return $this->redirect('/login');
    }
    
    /**
     * Display reset password page
     */
    public function showResetPassword() {
        // Check if already logged in
        if ($this->authService->check()) {
            return $this->redirect('/feed');
        }
        
        // Validate token
        $token = $_GET['token'] ?? null;
        
        if (!$token) {
            $this->setFlashMessage('Invalid password reset token', 'error');
            return $this->redirect('/login');
        }
        
        // Check if token exists and is valid
        $valid = $this->user->isValidPasswordResetToken($token);
        
        if (!$valid) {
            $this->setFlashMessage('Password reset token is invalid or has expired', 'error');
            return $this->redirect('/login');
        }
        
        return $this->view('auth/reset-password', [
            'token' => $token,
            'csrf_token' => $this->authService->generateCsrfToken()
        ]);
    }
    
    /**
     * Process reset password request
     */
    public function resetPassword() {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/login');
        }
        
        // Get input data
        $data = $this->getInputData();
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || !$this->authService->validateCsrfToken($data['csrf_token'])) {
            return $this->error('Invalid CSRF token', [], 403);
        }
        
        // Validate required fields
        $requiredFields = ['token', 'password', 'password_confirmation'];
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate password confirmation
        if (isset($data['password'], $data['password_confirmation']) &&
            $data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }
        
        // If there are validation errors
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                return $this->error('Validation failed', $errors);
            }
            
            // Set error messages and redirect back to reset password page
            $this->setFlashMessage('Please fix the errors below', 'error');
            $this->setValidationErrors($errors);
            return $this->redirect('/reset-password?token=' . urlencode($data['token']));
        }
        
        // Get user by token
        $user = $this->user->getUserByPasswordResetToken($data['token']);
        
        if (!$user) {
            if ($this->isAjaxRequest()) {
                return $this->error('Invalid token');
            }
            
            $this->setFlashMessage('Password reset token is invalid or has expired', 'error');
            return $this->redirect('/login');
        }
        
        // Update user's password
        $this->user->updatePassword($user['id'], $data['password']);
        
        // Remove the token
        $this->user->deletePasswordResetToken($data['token']);
        
        if ($this->isAjaxRequest()) {
            return $this->success([
                'redirect' => '/login'
            ], 'Password has been reset successfully. You can now log in with your new password.');
        }
        
        $this->setFlashMessage('Password has been reset successfully. You can now log in with your new password.', 'success');
        return $this->redirect('/login');
    }
}