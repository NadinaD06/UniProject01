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
        if (!isset($data['username']) || !isset($data['password'])) {
            return $this->error('Username and password are required');
        }
        
        $username = $data['username'];
        $password = $data['password'];
        $remember = isset($data['remember']) && $data['remember'] === 'on';
        
        // Attempt to authenticate
        if ($this->authService->attempt($username, $password, $remember)) {
            // If this is an AJAX request, return JSON response
            if ($this->isAjaxRequest()) {
                return $this->success([
                    'redirect' => '/feed'
                ], 'Login successful');
            }
            
            // Otherwise redirect to feed
            return $this->redirect('/feed');
        }
        
        // Authentication failed
        if ($this->isAjaxRequest()) {
            return $this->error('Invalid credentials');
        }
        
        // Set error message and redirect to login page
        $this->setFlashMessage('Invalid username or password', 'error');
        return $this->redirect('/login');
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
        if (!$this->isPost()) {
            $this->redirect('/register');
        }
        
        $username = $this->getPost('username');
        $email = $this->getPost('email');
        $password = $this->getPost('password');
        $confirmPassword = $this->getPost('confirm_password');
        $age = $this->getPost('age');
        $bio = $this->getPost('bio');
        $interests = $this->getPost('interests');
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $this->view('auth/register', [
                'error' => 'All required fields must be filled out',
                'username' => $username,
                'email' => $email,
                'age' => $age,
                'bio' => $bio,
                'interests' => $interests
            ]);
            return;
        }
        
        if ($password !== $confirmPassword) {
            $this->view('auth/register', [
                'error' => 'Passwords do not match',
                'username' => $username,
                'email' => $email,
                'age' => $age,
                'bio' => $bio,
                'interests' => $interests
            ]);
            return;
        }
        
        // Check if username or email already exists
        if ($this->user->findByUsername($username) || $this->user->findByEmail($email)) {
            $this->view('auth/register', [
                'error' => 'Username or email already exists',
                'username' => $username,
                'email' => $email,
                'age' => $age,
                'bio' => $bio,
                'interests' => $interests
            ]);
            return;
        }
        
        // Create user
        $data = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'age' => $age,
            'bio' => $bio,
            'interests' => $interests
        ];
        
        if ($this->user->create($data)) {
            $this->redirect('/login?registered=1');
        } else {
            $this->view('auth/register', [
                'error' => 'Registration failed. Please try again.',
                'username' => $username,
                'email' => $email,
                'age' => $age,
                'bio' => $bio,
                'interests' => $interests
            ]);
        }
    }
    
    /**
     * Process logout request
     */
    public function logout() {
        $this->authService->logout();
        
        if ($this->isAjaxRequest()) {
            return $this->success([], 'Logged out successfully');
        }
        
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