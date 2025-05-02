<?php
/**
 * Bootstrap file - Initializes the application
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

// Define base paths
define('ROOT_PATH', dirname(dirname(__DIR__))); // Go up one more level to handle double httpdocs
define('APP_PATH', dirname(__FILE__));
define('CONFIG_PATH', dirname(ROOT_PATH) . '/httpdocs/config'); // Updated to look in httpdocs/config
define('PUBLIC_PATH', ROOT_PATH);
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Log paths for debugging
error_log("ROOT_PATH: " . ROOT_PATH);
error_log("APP_PATH: " . APP_PATH);
error_log("CONFIG_PATH: " . CONFIG_PATH);

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $file = str_replace('\\', '/', $class) . '.php';
    error_log("Attempting to load class: " . $class);
    error_log("Looking for file: " . $file);
    
    // Check in app directory
    $appFile = APP_PATH . '/' . $file;
    error_log("Checking app file: " . $appFile);
    if (file_exists($appFile)) {
        error_log("Found file in app directory: " . $appFile);
        require $appFile;
        return true;
    }
    
    // Check in httpdocs directory
    $httpdocsFile = dirname(ROOT_PATH) . '/httpdocs/' . $file;
    error_log("Checking httpdocs file: " . $httpdocsFile);
    if (file_exists($httpdocsFile)) {
        error_log("Found file in httpdocs directory: " . $httpdocsFile);
        require $httpdocsFile;
        return true;
    }
    
    error_log("Class file not found: " . $class . " (tried: " . $appFile . " and " . $httpdocsFile . ")");
    return false;
});

// Load configuration
$configFile = CONFIG_PATH . '/config.php';
error_log("Loading config file: " . $configFile);
if (!file_exists($configFile)) {
    throw new Exception("Configuration file not found at: " . $configFile);
}
require_once $configFile;

// Initialize database connection
try {
    error_log("Attempting database connection to: " . DB_HOST . " with database: " . DB_NAME);
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('UTC');

// Initialize router
try {
    error_log("Initializing router");
    $router = new \App\Core\Router($pdo);
    
    // Load routes
    $routesFile = APP_PATH . '/routes.php';
    error_log("Loading routes file: " . $routesFile);
    if (!file_exists($routesFile)) {
        throw new Exception("Routes file not found at: " . $routesFile);
    }
    require_once $routesFile;
    
    // Dispatch the request
    error_log("Dispatching request");
    $router->dispatch();
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    throw $e;
} 