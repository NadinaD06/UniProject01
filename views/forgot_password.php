<?php
// forgot_password.php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: feed.php");
    exit();
}

// Include the HTML file
include('forgot_password.html');
?>