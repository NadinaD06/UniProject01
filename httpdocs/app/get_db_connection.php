<?php
/**
 * app/get_db_connection.php
 * Establishes database connection
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
$config = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'unisocial',
    'DB_USER' => 'unisocial_user',
    'DB_PASS' => 'password'
];

// Try to load from config file if exists
if (file_exists(dirname(__DIR__) . '/config/config.php')) {
    $fileConfig = require dirname(__DIR__) . '/config/config.php';
    $config = array_merge($config, $fileConfig);
}

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4",
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Return database connection
    return $pdo;
} catch (PDOException $e) {
    // Log error
    error_log("Database connection error: " . $e->getMessage());
    
    // If this file is included directly, show error
    if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
        echo "Database connection failed: " . $e->getMessage();
    }
    
    // Otherwise, throw the exception for the caller to handle
    throw $e;
}