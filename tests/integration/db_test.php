<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testing database connection...<br>";

// Load configuration
$config = require_once 'config/config.php';

echo "Database host: " . $config['DB_HOST'] . "<br>";
echo "Database name: " . $config['DB_NAME'] . "<br>";
echo "Database user: " . $config['DB_USER'] . "<br>";

try {
    // Test connection with timeout
    $dsn = "pgsql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};port=5432";
    $options = [
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    echo "Attempting to connect to: $dsn<br>";
    
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
    echo "Database connection successful!<br>";
    
    // Test if we can query the database
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "PostgreSQL version: $version<br>";
    
} catch (PDOException $e) {
    echo "Database connection failed:<br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "Error message: " . $e->getMessage() . "<br>";
    
    // Additional troubleshooting information
    echo "<br>Troubleshooting steps:<br>";
    echo "1. Verify PostgreSQL is running on the server<br>";
    echo "2. Check if port 5432 is open and accessible<br>";
    echo "3. Verify the database user has proper permissions<br>";
    echo "4. Check if the database exists<br>";
    echo "5. Verify the host is correct and accessible<br>";
}
?> 