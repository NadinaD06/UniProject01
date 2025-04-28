<?php
namespace App\Controllers;

use App\Auth\UserAuth;

class AuthController {
    private $auth;
    
    public function __construct($pdo) {
        $this->auth = new UserAuth($pdo);
    }
    
    public function showRegister() {
        require_once 'views/register.php';
    }
    
    public function showLogin() {
        require_once 'views/login.php';
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $age = $_POST['age'] ?? null;
        $bio = $_POST['bio'] ?? null;
        $interests = $_POST['interests'] ?? null;
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All required fields must be filled out';
            require_once 'views/register.php';
            return;
        }
        
        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
            require_once 'views/register.php';
            return;
        }
        
        // Attempt registration
        $result = $this->auth->register($username, $email, $password, $age, $bio, $interests);
        
        if ($result['success']) {
            // Registration successful, redirect to login
            header('Location: /login?registered=1');
            exit;
        } else {
            // Registration failed, show error
            $error = $result['message'];
            require_once 'views/register.php';
        }
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once 'views/login.php';
            return;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
            require_once 'views/login.php';
            return;
        }
        
        // Attempt login
        $result = $this->auth->login($username, $password);
        
        if ($result['success']) {
            // Login successful, redirect to home
            header('Location: /');
            exit;
        } else {
            // Login failed, show error
            $error = $result['message'];
            require_once 'views/login.php';
        }
    }
    
    public function logout() {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}
?> 