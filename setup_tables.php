<?php
 
require_once 'get_db_connection.php';
 
try {
    // Array of table creation SQL statements
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                age INT,
                bio TEXT,
                interests LONGTEXT,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'posts' => "
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                content TEXT,
                image_url VARCHAR(255),
                location VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'comments' => "
            CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'likes' => "
            CREATE TABLE IF NOT EXISTS likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(post_id, user_id)
            );
        ",
        'messages' => "
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                content TEXT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ",
        'blocks' => "
            CREATE TABLE IF NOT EXISTS blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                blocker_id INT NOT NULL,
                blocked_id INT NOT NULL,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(blocker_id, blocked_id)
            );
        ",
        'reports' => "
            CREATE TABLE IF NOT EXISTS reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reporter_id INT NOT NULL,
                reported_id INT NOT NULL,
                reason VARCHAR(255) NOT NULL,
                reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
            );
        "
    ];
 
    // Iterate through each table and create it if it doesn't exist
    foreach ($tables as $tableName => $createSQL) {
        $pdo->exec($createSQL);
        echo "Table '$tableName' is ready.\n";
    }
 
} catch (PDOException $e) {
    echo 'Database setup failed: ' . $e->getMessage();
}
?>