<?php
/**
 * Front controller - All requests are routed through this file
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Load the bootstrap file
    require_once dirname(__DIR__) . '/app/bootstrap.php';
} catch (Exception $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Show a user-friendly error message
    header('HTTP/1.1 500 Internal Server Error');
    echo 'An error occurred. Please try again later.';
    exit;
}

// Define the application path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/config/config.php';

// Start session
session_start();

// Get the request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Basic routing
switch ($request_uri) {
    case '/':
    case '/home':
        require_once BASE_PATH . '/app/Controllers/HomeController.php';
        $controller = new HomeController();
        $controller->index();
        break;
        
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once BASE_PATH . '/app/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->login();
        } else {
            require_once BASE_PATH . '/app/Views/auth/login.php';
        }
        break;
        
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once BASE_PATH . '/app/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->register();
        } else {
            require_once BASE_PATH . '/app/Views/auth/register.php';
        }
        break;
        
    default:
        // Handle 404
        header("HTTP/1.0 404 Not Found");
        require_once BASE_PATH . '/app/Views/errors/404.php';
        break;
}