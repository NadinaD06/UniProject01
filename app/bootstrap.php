<?php
// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $file = BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
require_once BASE_PATH . '/config/config.php';

// Initialize database connection
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('UTC'); 