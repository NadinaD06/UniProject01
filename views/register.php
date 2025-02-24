<?php
// register.php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: feed.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSpace - Register</title>
    <link rel="stylesheet" href="/assets/css/register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <a href="/index.php" class="logo-link">
            <h1>ArtSpace</h1>
        </a>
        <h2>Create Account</h2>
        <p>Join our creative community</p>
        
        <form id="registerForm" action="../controllers/auth/register_process.php" method="POST" onsubmit="return validateForm(event)">
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <!-- Rest of the form remains the same -->
        </form>
        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>
    <script src="/assets/js/register.js"></script>
</body>
</html>