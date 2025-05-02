<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Plesk Database Verification</h2>";

// Load configuration
$config = require_once 'config/config.php';

echo "<h3>Configuration:</h3>";
echo "Host: " . $config['DB_HOST'] . "<br>";
echo "Database: " . $config['DB_NAME'] . "<br>";
echo "User: " . $config['DB_USER'] . "<br>";

// Test DNS resolution
echo "<h3>DNS Resolution:</h3>";
$ip = gethostbyname($config['DB_HOST']);
echo "Hostname resolves to: " . ($ip === $config['DB_HOST'] ? "Failed to resolve" : $ip) . "<br>";

// Test port connectivity
echo "<h3>Port Test:</h3>";
$socket = @fsockopen($config['DB_HOST'], 3306, $errno, $errstr, 5);
if ($socket) {
    echo "<span style='color: green;'>Port 3306 is open and accessible</span><br>";
    fclose($socket);
} else {
    echo "<span style='color: red;'>Port 3306 is not accessible: $errstr ($errno)</span><br>";
}

try {
    // Try to connect without specifying a database first
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']}",
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
    echo "<span style='color: green;'>Initial connection successful!</span><br>";
    
    // Check if the database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['DB_NAME']}'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<span style='color: green;'>Database '{$config['DB_NAME']}' exists</span><br>";
        
        // Try to use the database
        $pdo->exec("USE {$config['DB_NAME']}");
        echo "<span style='color: green;'>Successfully selected database</span><br>";
        
        // Check user privileges
        $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER");
        echo "<h3>User Privileges:</h3>";
        while ($row = $stmt->fetch()) {
            echo htmlspecialchars($row[0]) . "<br>";
        }
        
        // List tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Database Tables:</h3>";
        if (empty($tables)) {
            echo "<p style='color: orange;'>No tables found in the database</p>";
        } else {
            foreach ($tables as $table) {
                echo "- " . htmlspecialchars($table) . "<br>";
            }
        }
    } else {
        echo "<span style='color: red;'>Database '{$config['DB_NAME']}' does not exist</span><br>";
        echo "<p>Please create the database in Plesk control panel.</p>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>Error:</span><br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "Error message: " . $e->getMessage() . "<br>";
    
    echo "<h3>Plesk-Specific Troubleshooting:</h3>";
    echo "1. Log into Plesk control panel<br>";
    echo "2. Go to Databases section<br>";
    echo "3. Verify the database exists<br>";
    echo "4. Check if the user exists and has proper permissions<br>";
    echo "5. Verify the password is correct<br>";
    echo "6. Make sure the database is accessible from the web server<br>";
    echo "7. Check if the hostname is correct in Plesk<br>";
}

// System information
echo "<h3>System Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Client Info: " . (function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Not available') . "<br>";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?> 