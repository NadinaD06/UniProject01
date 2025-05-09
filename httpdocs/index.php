<?php
// httpdocs/index.php - Main entry point

// Load configuration first
require_once dirname(__DIR__) . '/config/config.php';

// Error handling 
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    error_log(json_encode($error));
    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    error_log($e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo '<h1>Error Details:</h1>';
    echo '<pre>';
    echo 'Message: ' . htmlspecialchars($e->getMessage()) . "\n";
    echo 'File: ' . htmlspecialchars($e->getFile()) . "\n";
    echo 'Line: ' . $e->getLine() . "\n";
    echo 'Trace: ' . htmlspecialchars($e->getTraceAsString());
    echo '</pre>';
    exit;
});

// Start session
session_start();

// Load database connection
require_once APP_PATH . '/get_db_connection.php';

// Get the request URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Basic routing
switch ($request_uri) {
    case '/':
    case '/home':
        require_once APP_PATH . '/Controllers/HomeController.php';
        $controller = new App\Controllers\HomeController($pdo);
        $controller->index();
        break;
        
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle login POST request
            require_once APP_PATH . '/Controllers/AuthController.php';
            $controller = new App\Controllers\AuthController($pdo);
            $controller->login();
        } else {
            // Display login form
            require_once APP_PATH . '/Views/auth/login.php';
        }
        break;
        
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle registration POST request
            require_once APP_PATH . '/Controllers/AuthController.php';
            $controller = new App\Controllers\AuthController($pdo);
            $controller->register();
        } else {
            // Display registration form
            require_once APP_PATH . '/Views/auth/register.php';
        }
        break;
    
    case '/logout':
        // Handle logout
        require_once APP_PATH . '/Controllers/AuthController.php';
        $controller = new App\Controllers\AuthController($pdo);
        $controller->logout();
        break;
        
    default:
        // Handle 404
        header("HTTP/1.0 404 Not Found");
        require_once APP_PATH . '/Views/errors/404.php';
        break;
}
