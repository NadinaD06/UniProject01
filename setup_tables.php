<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    
    // Array of table creation SQL statements
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                age INTEGER,
                bio TEXT,
                interests TEXT,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'posts' => "
            CREATE TABLE IF NOT EXISTS posts (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                content TEXT,
                image_url VARCHAR(255),
                location VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'comments' => "
            CREATE TABLE IF NOT EXISTS comments (
                id SERIAL PRIMARY KEY,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'likes' => "
            CREATE TABLE IF NOT EXISTS likes (
                id SERIAL PRIMARY KEY,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(post_id, user_id)
            );
        ",
        'messages' => "
            CREATE TABLE IF NOT EXISTS messages (
                id SERIAL PRIMARY KEY,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'blocks' => "
            CREATE TABLE IF NOT EXISTS blocks (
                id SERIAL PRIMARY KEY,
                blocker_id INTEGER NOT NULL,
                blocked_id INTEGER NOT NULL,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(blocker_id, blocked_id)
            );
        ",
        'reports' => "
            CREATE TABLE IF NOT EXISTS reports (
                id SERIAL PRIMARY KEY,
                reporter_id INTEGER NOT NULL,
                reported_id INTEGER NOT NULL,
                reason VARCHAR(255) NOT NULL,
                reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
            );
        "
    ];
 
    // Iterate through each table and create it if it doesn't exist
    foreach ($tables as $tableName => $createSQL) {
        try {
            $pdo->exec($createSQL);
            echo "Table '$tableName' is ready.<br>";
        } catch (PDOException $e) {
            echo "Error creating table '$tableName': " . $e->getMessage() . "<br>";
        }
    }
 
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>