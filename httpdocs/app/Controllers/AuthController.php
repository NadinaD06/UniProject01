<?php
/**
* app/Controllers/AuthController.php
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller {
    private $user;
    private $authService;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->user = new User($pdo);
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
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /feed');
                exit;
            } else {
                $error = "Invalid username or password";
            }
        }

        require_once __DIR__ . '/../Views/auth/login.php';
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
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validate input
            $errors = [];
            if (strlen($username) < 3) {
                $errors[] = "Username must be at least 3 characters long";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            if (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters long";
            }
            if ($password !== $confirm_password) {
                $errors[] = "Passwords do not match";
            }

            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username or email already exists";
            }

            if (empty($errors)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash]);

                $_SESSION['user_id'] = $this->pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header('Location: /feed');
                exit;
            }
        }

        require_once __DIR__ . '/../Views/auth/register.php';
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