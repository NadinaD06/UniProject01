<?php
// Start session
session_start();

// Check if user is logged in
$response = [
    'loggedIn' => isset($_SESSION['user_id'])
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>