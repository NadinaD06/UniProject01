<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the config file path
$configFile = __DIR__ . '/config/config.php';

// Check if config file exists
if (!file_exists($configFile)) {
    die("Error: Configuration file not found at: " . $configFile);
}

// Try to load configuration
try {
    $config = require $configFile;
    
    // Debug output
    echo "<!-- Debug: Config file loaded -->\n";
    echo "<!-- Debug: Config type: " . gettype($config) . " -->\n";
    if (is_array($config)) {
        echo "<!-- Debug: Config keys: " . implode(', ', array_keys($config)) . " -->\n";
    }
    
    if (!is_array($config)) {
        die("Error: Configuration file did not return an array. Got: " . gettype($config));
    }
    
    // Validate required configuration values
    $required_config = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($required_config as $key) {
        if (!isset($config[$key]) || empty($config[$key])) {
            die("Error: Missing required configuration value: {$key}");
        }
    }
    
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
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\nFile: " . $configFile);
}
?> 