<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic PHP test
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Test database connection
try {
    $config = require_once 'config/config.php';
    $pdo = new PDO(
        "pgsql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};port=5432",
        $config['DB_USER'],
        $config['DB_PASS']
    );
    echo "Database connection successful!<br>";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}
?> 