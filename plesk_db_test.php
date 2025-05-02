<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Plesk Database Connection Test</h2>";

// Load configuration
$config = require_once 'config/config.php';

echo "<h3>Connection Details:</h3>";
echo "Host: " . $config['DB_HOST'] . "<br>";
echo "Database: " . $config['DB_NAME'] . "<br>";
echo "User: " . $config['DB_USER'] . "<br>";

try {
    // Connect to MySQL
    $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5 // 5 second timeout
    ];
    
    echo "<h3>Attempting Connection:</h3>";
    echo "DSN: $dsn<br>";
    
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
    echo "<span style='color: green;'>Connection successful!</span><br>";
    
    // Test if we can query the database
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetchColumn();
    echo "MySQL version: $version<br>";
    
    // List all tables
    $stmt = $pdo->query("SHOW TABLES");
    echo "<h3>Available Tables:</h3>";
    while ($row = $stmt->fetch()) {
        echo "- " . $row[0] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>Connection failed:</span><br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "Error message: " . $e->getMessage() . "<br>";
    
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "1. Verify the database exists in Plesk<br>";
    echo "2. Check if the user has proper permissions<br>";
    echo "3. Verify the password is correct<br>";
    echo "4. Make sure the database is accessible from the web server<br>";
    echo "5. Check if the hostname is correct and resolves properly<br>";
}
?> 