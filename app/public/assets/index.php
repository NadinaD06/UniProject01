<?php
/**
 * ArtSpace Social Media Platform
 * Front Controller - Entry point for all requests
 */

// Define application path constants
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('PUBLIC_PATH', APP_ROOT . '/public');

// Load Composer autoloader
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}

// Load configuration
$config = require APP_ROOT . '/config/app.php';

// Set environment
$environment = $config['environment'] ?? 'production';
define('APP_ENV', $environment);

// Enable error display in development mode
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// Define WebSocket settings
define('WEBSOCKET_ENABLED', $config['websocket']['enabled'] ?? false);
define('WEBSOCKET_URL', $config['websocket']['url'] ?? 'ws://localhost:8080');

// Initialize error handler
new \App\Core\ErrorHandler();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Set default timezone
date_default_timezone_set($config['timezone'] ?? 'UTC');

// Load routes
$router = require APP_ROOT . '/routes/web.php';

// Handle the request
$router->dispatch();