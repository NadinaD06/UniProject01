<?php
$config = include_once 'config.php';
 
$host = $config['DB_HOST'];
$db   = $config['DB_NAME'];
$user = $config['DB_USER'];
$pass = $config['DB_PASS'];
 
try {
    // Connect to the database
    $pdo = new PDO(
        dsn: "mysql:host=$host;dbname=$db;charset=utf8mb4",
        username: $user,
        password: $pass
    );
    // Set the PDO error mode to exception
    // If an error occurs, it will throw an exception
    $pdo->setAttribute(
        attribute: PDO::ATTR_ERRMODE,
        value: PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
 