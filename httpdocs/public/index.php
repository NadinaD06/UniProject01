<?php
/**
 * Front controller - All requests are routed through this file
 */

// Error handling
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

// Set error handler
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

try {
    // Define base path - adjust for Plesk's httpdocs directory
    define('BASE_PATH', dirname(dirname(__FILE__)));
    
    // Log the bootstrap file path
    $bootstrapFile = BASE_PATH . '/app/bootstrap.php';
    error_log("Attempting to load bootstrap file: " . $bootstrapFile);
    
    if (!file_exists($bootstrapFile)) {
        throw new Exception("Bootstrap file not found at: " . $bootstrapFile);
    }
    
    // Load the bootstrap file
    require_once $bootstrapFile;
    
} catch (Throwable $e) {
    // Log the error with more details
    error_log("Error in index.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Show detailed error message
    header('HTTP/1.1 500 Internal Server Error');
    echo '<h1>Error Details:</h1>';
    echo '<pre>';
    echo 'Message: ' . htmlspecialchars($e->getMessage()) . "\n";
    echo 'File: ' . htmlspecialchars($e->getFile()) . "\n";
    echo 'Line: ' . $e->getLine() . "\n";
    echo 'Trace: ' . htmlspecialchars($e->getTraceAsString());
    echo '</pre>';
    exit;
}

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