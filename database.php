<?php
// Include the configuration file
$config = include_once 'config.php';
$host = $config['DB_HOST'];
$db = $config['DB_NAME'];
$user = $config['DB_USER'];
$pass = $config['DB_PASS'];

try {
    // Connect to the database
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    
    // Set the PDO error mode to exception
    // If an error occurs, it will throw an exception
    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Connection failed: " . $e->getMessage());
    die("Connection to database failed. Please try again later.");
}
?>