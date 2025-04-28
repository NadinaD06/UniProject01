<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Connection Test</h2>";

// Load configuration
$config = require_once 'config/config.php';

echo "<h3>Configuration:</h3>";
echo "Database host: " . $config['DB_HOST'] . "<br>";
echo "Database name: " . $config['DB_NAME'] . "<br>";
echo "Database user: " . $config['DB_USER'] . "<br>";

// Test if PostgreSQL extension is loaded
echo "<h3>PHP Extensions:</h3>";
echo "pgsql extension loaded: " . (extension_loaded('pgsql') ? 'Yes' : 'No') . "<br>";
echo "pdo_pgsql extension loaded: " . (extension_loaded('pdo_pgsql') ? 'Yes' : 'No') . "<br>";

// Test connection with different hosts
$hosts = [
    'localhost',
    '127.0.0.1',
    $config['DB_HOST']
];

echo "<h3>Connection Tests:</h3>";

foreach ($hosts as $host) {
    echo "<h4>Trying host: $host</h4>";
    
    try {
        $dsn = "pgsql:host=$host;dbname={$config['DB_NAME']};port=5432";
        $options = [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        echo "Attempting to connect to: $dsn<br>";
        
        $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
        echo "<span style='color: green;'>Connection successful!</span><br>";
        
        // Test if we can query the database
        $stmt = $pdo->query("SELECT version()");
        $version = $stmt->fetchColumn();
        echo "PostgreSQL version: $version<br>";
        
        // Test if we can list databases
        $stmt = $pdo->query("SELECT datname FROM pg_database");
        echo "Available databases:<br>";
        while ($row = $stmt->fetch()) {
            echo "- " . $row['datname'] . "<br>";
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
$socket = @fsockopen('localhost', 5432, $errno, $errstr, 5);
if ($socket) {
    echo "Socket connection to PostgreSQL successful<br>";
    fclose($socket);
} else {
    echo "Socket connection failed: $errstr ($errno)<br>";
}
?> 