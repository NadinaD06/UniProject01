<?php
// Load configuration
$config = require_once 'config/config.php';

try {
    // Connect to PostgreSQL database
    $pdo = new PDO(
        "pgsql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};port=5432",
        $config['DB_USER'],
        $config['DB_PASS']
    );
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 