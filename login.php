<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $response = ['success' => true, 'message' => 'Login successful'];
    } else {
        $response = ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Load the HTML view
require_once 'views/login.html';