<?php
/**
 * Main entry point for the application
 */

// Define the application path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
require_once BASE_PATH . '/config/config.php';

// Load bootstrap file
require_once BASE_PATH . '/app/bootstrap.php';

// Start session
session_start();

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];

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