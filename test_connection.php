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
    
    echo "Database connection successful!<br>";
    
    // Test if tables exist
    $tables = ['users', 'posts', 'comments', 'likes', 'follows'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
        $exists = $stmt->fetchColumn();
        echo "Table '$table' exists: " . ($exists ? 'Yes' : 'No') . "<br>";
    }
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
