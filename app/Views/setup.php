<?php
// Load configuration
$config = require_once 'config/config.php';

try {
    // Connect to MySQL server
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']}",
        $config['DB_USER'],
        $config['DB_PASS']
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['DB_NAME']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
    
    // Select the database
    $pdo->exec("USE {$config['DB_NAME']}");
    
    // Run the table creation script
    require_once 'setup_tables.php';
    
    // Create necessary directories if they don't exist
    $directories = [
        'assets',
        'assets/css',
        'assets/js',
        'assets/images',
        'uploads',
        'uploads/posts',
        'uploads/profiles'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: $dir\n";
        }
    }
    
    echo "Setup completed successfully!\n";
    
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 