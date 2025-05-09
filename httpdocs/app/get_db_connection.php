<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once(BASE_PATH . '/config/config.php');

try {
    // Connect to MySQL database
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
    
    echo "Database connection successful!<br>";
    echo "Connected to database: " . DB_NAME . "<br>";
    echo "Using host: " . DB_HOST . "<br>";
    echo "Base path: " . BASE_PATH . "<br>";
    echo "App path: " . APP_PATH . "<br>";
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}