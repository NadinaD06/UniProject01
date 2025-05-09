<?php
/**
 * Bootstrap file - Initializes the application
 */

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', dirname(ROOT_PATH)); // Add this line
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config'); // Update this to use BASE_PATH
define('VIEWS_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
// Log paths for debugging
error_log("ROOT_PATH: " . ROOT_PATH);
error_log("APP_PATH: " . APP_PATH);
error_log("CONFIG_PATH: " . CONFIG_PATH);

// Load configuration first
$configFile = CONFIG_PATH . '/config.php';
error_log("Loading config file: " . $configFile);
if (!file_exists($configFile)) {
    throw new Exception("Configuration file not found at: " . $configFile);
}

// Only load config if constants are not already defined
if (!defined('DB_HOST')) {
    $config = require $configFile;
}

// Start session after loading config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $file = str_replace('\\', '/', $class) . '.php';
    error_log("Attempting to load class: " . $class);
    error_log("Looking for file: " . $file);
    
    // Check in app directory first
    $appFile = APP_PATH . '/' . $file;
    error_log("Checking app file: " . $appFile);
    if (file_exists($appFile)) {
        error_log("Found file in app directory: " . $appFile);
        require $appFile;
        return true;
    }
    
    // Check in Controllers directory
    $controllerFile = APP_PATH . '/Controllers/' . basename($file);
    error_log("Checking controller file: " . $controllerFile);
    if (file_exists($controllerFile)) {
        error_log("Found file in controllers directory: " . $controllerFile);
        require $controllerFile;
        return true;
    }
    
    // Check in Services directory
    $serviceFile = APP_PATH . '/Services/' . basename($file);
    error_log("Checking service file: " . $serviceFile);
    if (file_exists($serviceFile)) {
        error_log("Found file in services directory: " . $serviceFile);
        require $serviceFile;
        return true;
    }
    
    // Check in Core directory
    $coreFile = APP_PATH . '/Core/' . basename($file);
    error_log("Checking core file: " . $coreFile);
    if (file_exists($coreFile)) {
        error_log("Found file in core directory: " . $coreFile);
        require $coreFile;
        return true;
    }
    
    // Check in Models directory
    $modelFile = APP_PATH . '/Models/' . basename($file);
    error_log("Checking model file: " . $modelFile);
    if (file_exists($modelFile)) {
        error_log("Found file in models directory: " . $modelFile);
        require $modelFile;
        return true;
    }
    
    // Check in Core/Controller directory
    $coreControllerFile = APP_PATH . '/Core/Controller/' . basename($file);
    error_log("Checking core controller file: " . $coreControllerFile);
    if (file_exists($coreControllerFile)) {
        error_log("Found file in core controller directory: " . $coreControllerFile);
        require $coreControllerFile;
        return true;
    }
    
    // Check in Core/models directory
    $coreModelFile = APP_PATH . '/Core/models/' . basename($file);
    error_log("Checking core model file: " . $coreModelFile);
    if (file_exists($coreModelFile)) {
        error_log("Found file in core models directory: " . $coreModelFile);
        require $coreModelFile;
        return true;
    }
    
    error_log("Class file not found: " . $class . " (tried: " . $appFile . ", " . $controllerFile . ", " . $serviceFile . ", " . $coreFile . ", " . $modelFile . ", " . $coreControllerFile . ", and " . $coreModelFile . ")");
    return false;
});

// Initialize database connection
try {
    error_log("Attempting database connection to: " . DB_HOST . " with database: " . DB_NAME);
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
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