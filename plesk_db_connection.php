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

// Test DNS resolution
echo "<h3>DNS Resolution:</h3>";
$host = explode(':', $config['DB_HOST'])[0];
$ip = gethostbyname($host);
echo "Hostname '$host' resolves to: " . ($ip === $host ? "Failed to resolve" : $ip) . "<br>";

// Test port connectivity
echo "<h3>Port Test:</h3>";
$port = explode(':', $config['DB_HOST'])[1] ?? '3306';
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if ($socket) {
    echo "<span style='color: green;'>Port $port is open and accessible</span><br>";
    fclose($socket);
} else {
    echo "<span style='color: red;'>Port $port is not accessible: $errstr ($errno)</span><br>";
}

try {
    // Try to connect with different DSN formats
    $dsn_formats = [
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4",
        "mysql:host={$host};port={$port};dbname={$config['DB_NAME']};charset=utf8mb4"
    ];
    
    echo "<h3>Connection Attempts:</h3>";
    
    foreach ($dsn_formats as $dsn) {
        echo "<h4>Trying DSN: $dsn</h4>";
        
        try {
            $pdo = new PDO(
                $dsn,
                $config['DB_USER'],
                $config['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            echo "<span style='color: green;'>Connection successful!</span><br>";
            
            // Test if we can query the database
            $stmt = $pdo->query("SELECT VERSION()");
            $version = $stmt->fetchColumn();
            echo "MySQL version: $version<br>";
            
            // List databases
            $stmt = $pdo->query("SHOW DATABASES");
            echo "<h4>Available Databases:</h4>";
            while ($row = $stmt->fetch()) {
                echo "- " . $row[0] . "<br>";
            }
            
            break; // Stop if connection is successful
            
        } catch (PDOException $e) {
            echo "<span style='color: red;'>Connection failed:</span><br>";
            echo "Error code: " . $e->getCode() . "<br>";
            echo "Error message: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>Error:</span><br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "Error message: " . $e->getMessage() . "<br>";
}

// System information
echo "<h3>System Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Client Info: " . (function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Not available') . "<br>";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Additional troubleshooting information
echo "<h3>Troubleshooting Information:</h3>";
echo "1. Make sure the database server is running<br>";
echo "2. Verify the database credentials in Plesk<br>";
echo "3. Check if the database exists in Plesk<br>";
echo "4. Ensure the user has proper permissions<br>";
echo "5. Check if the hostname is correct and resolves properly<br>";
echo "6. Verify that port 3306 is open and accessible<br>";
?> 