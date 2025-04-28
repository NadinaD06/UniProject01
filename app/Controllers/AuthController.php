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
    
    public function __construct() {
        parent::__construct();
        $this->user = new User();
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
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/register');
        }
        
        // Get input data
        $data = $this->getInputData();
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || !$this->authService->validateCsrfToken($data['csrf_token'])) {
            return $this->error('Invalid CSRF token', [], 403);
        }
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'password_confirmation'];
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
        
        // Validate email format
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Validate username (only alphanumeric and underscore)
        if (isset($data['username']) && !preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }
        
        // Check if username already exists
        if (isset($data['username']) && $this->user->findBy('username', $data['username'])) {
            $errors['username'] = 'Username already taken';
        }
        
        // Check if email already exists
        if (isset($data['email']) && $this->user->findBy('email', $data['email'])) {
            $errors['email'] = 'Email already registered';
        }
        
        // If there are validation errors
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                return $this->error('Validation failed', $errors);
            }
            
            // Set error messages and redirect back to registration page
            $this->setFlashMessage('Please fix the errors below', 'error');
            $this->setOldInput($data);
            $this->setValidationErrors($errors);
            return $this->redirect('/register');
        }
        
        // Create the user
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'full_name' => $data['full_name'] ?? null
        ];
        
        $userId = $this->user->createUser($userData);
        
        if (!$userId) {
            if ($this->isAjaxRequest()) {
                return $this->error('Failed to create user');
            }
            
            $this->setFlashMessage('Registration failed. Please try again.', 'error');
            return $this->redirect('/register');
        }
        
        // Log the user in
        $user = $this->user->find($userId);
        $this->authService->login($user);
        
        if ($this->isAjaxRequest()) {
            return $this->success([
                'redirect' => '/feed'
            ], 'Registration successful');
        }
        
        // Set success message and redirect to feed
        $this->setFlashMessage('Registration successful! Welcome to ArtSpace.', 'success');
        return $this->redirect('/feed');
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
    
    /**
     * Set old input data in session
     * 
     * @param array $data
     */
    protected function setOldInput($data) {
        $_SESSION['old_input'] = $data;
    }
    
    /**
     * Set validation errors in session
     * 
     * @param array $errors
     */
    protected function setValidationErrors($errors) {
        $_SESSION['validation_errors'] = $errors;
    }
}