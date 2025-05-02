<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
$config = require_once __DIR__ . '/config/config.php';

if (!is_array($config)) {
    die("Error: Configuration file did not return an array");
}

// Validate required configuration values
$required_config = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required_config as $key) {
    if (!isset($config[$key]) || empty($config[$key])) {
        die("Error: Missing required configuration value: {$key}");
    }
}

try {
    // Connect to MySQL database
    $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5
    ];
    
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
    
    // Test the connection
    $pdo->query('SELECT 1');
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\nDSN: " . $dsn);
}
?> 