<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

echo "<h2>Fixing Follows Table</h2>";

try {
    // First, let's see what tables exist
    echo "<h3>Existing Tables:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";

    // Check if follows table exists and its structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'follows'");
    if ($stmt->rowCount() > 0) {
        echo "<h3>Current Follows Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE follows");
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Follows table does not exist yet.</p>";
    }

    // Drop and recreate the table with correct structure
    echo "<h3>Recreating Follows Table:</h3>";
    $pdo->exec("DROP TABLE IF EXISTS follows");
    
    $sql = "CREATE TABLE follows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, following_id)
    )";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>Follows table recreated successfully!</p>";
    
    // Verify the new structure
    echo "<h3>New Follows Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE follows");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 