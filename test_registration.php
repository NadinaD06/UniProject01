<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
$config = require_once 'config/config.php';

// Load database connection
require_once 'get_db_connection.php';

// Test user data
$testUser = [
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'Test123!',
    'confirm_password' => 'Test123!',
    'age' => 25,
    'bio' => 'Test user for functionality testing',
    'interests' => 'Testing, Development'
];

echo "<h2>Testing Registration Functionality</h2>";

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$testUser['username'], $testUser['email']]);
    if ($stmt->fetch()) {
        echo "<p style='color: orange;'>Test user already exists, skipping registration test</p>";
    } else {
        // Attempt registration
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, age, bio, interests, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $hashedPassword = password_hash($testUser['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $testUser['username'],
            $testUser['email'],
            $hashedPassword,
            $testUser['age'],
            $testUser['bio'],
            $testUser['interests']
        ]);
        
        echo "<p style='color: green;'>Registration successful!</p>";
        echo "<p>Test user created:</p>";
        echo "<ul>";
        echo "<li>Username: " . htmlspecialchars($testUser['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($testUser['email']) . "</li>";
        echo "<li>Age: " . htmlspecialchars($testUser['age']) . "</li>";
        echo "<li>Bio: " . htmlspecialchars($testUser['bio']) . "</li>";
        echo "<li>Interests: " . htmlspecialchars($testUser['interests']) . "</li>";
        echo "</ul>";
        
        // Test login with the created user
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$testUser['username']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($testUser['password'], $user['password_hash'])) {
            echo "<p style='color: green;'>Login test successful!</p>";
        } else {
            echo "<p style='color: red;'>Login test failed!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Display database structure
echo "<h3>Users Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE users");
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
?> 