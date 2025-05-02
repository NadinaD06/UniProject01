<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Detailed Plesk Database Connection Test</h2>";

// Load configuration
$config = require_once 'config/config.php';

echo "<h3>Configuration:</h3>";
echo "Database host: " . $config['DB_HOST'] . "<br>";
echo "Database name: " . $config['DB_NAME'] . "<br>";
echo "Database user: " . $config['DB_USER'] . "<br>";

// Test DNS resolution
echo "<h3>DNS Resolution Test:</h3>";
$ip = gethostbyname($config['DB_HOST']);
echo "Hostname resolves to: " . ($ip === $config['DB_HOST'] ? "Failed to resolve" : $ip) . "<br>";

// Test if MySQL extension is loaded
echo "<h3>PHP Extensions:</h3>";
echo "mysql extension loaded: " . (extension_loaded('mysql') ? 'Yes' : 'No') . "<br>";
echo "mysqli extension loaded: " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "<br>";
echo "pdo_mysql extension loaded: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";

// Test connection with different hosts
$hosts = [
    $config['DB_HOST'],
    'localhost',
    '127.0.0.1'
];

echo "<h3>Connection Tests:</h3>";

foreach ($hosts as $host) {
    echo "<h4>Trying host: $host</h4>";
    
    try {
        $dsn = "mysql:host=$host;dbname={$config['DB_NAME']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ];
        
        echo "Attempting to connect to: $dsn<br>";
        
        $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
        echo "<span style='color: green;'>Connection successful!</span><br>";
        
        // Test if we can query the database
        $stmt = $pdo->query("SELECT VERSION()");
        $version = $stmt->fetchColumn();
        echo "MySQL version: $version<br>";
        
        // Test if we can list databases
        $stmt = $pdo->query("SHOW DATABASES");
        echo "Available databases:<br>";
        while ($row = $stmt->fetch()) {
            echo "- " . $row[0] . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "<span style='color: red;'>Connection failed:</span><br>";
        echo "Error code: " . $e->getCode() . "<br>";
        echo "Error message: " . $e->getMessage() . "<br>";
    }
}

// System information
echo "<h3>System Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Check if we can connect to the database server using socket
echo "<h3>Socket Connection Test:</h3>";
$socket = @fsockopen($config['DB_HOST'], 3306, $errno, $errstr, 5);
if ($socket) {
    echo "Socket connection to MySQL successful<br>";
    fclose($socket);
} else {
    echo "Socket connection failed: $errstr ($errno)<br>";
}
?> 