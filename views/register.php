<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: feed.php");
    exit();
}

// Get any error/success messages
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;

// Clear session messages
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Check for form data in session (in case of validation error)
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join ArtSpace - Connect with Artists</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/register.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo-link">
            <h1>ArtSpace</h1>
        </a>
    </nav>

    <div class="register-container">
        <div class="form-header">
            <h1>Join ArtSpace</h1>
            <p>Connect with artists and share your creative journey</p>
        </div>

        <?php if ($error_message): ?>
            <div id="errorContainer" class="error-message" style="display: block;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" action="../controllers/register_process.php" method="POST">
            <div id="errorContainer" class="error-message" style="display: none;"></div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="input-field" required 
                    minlength="3" maxlength="20" placeholder="Choose a unique username"
                    value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="input-field" required 
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="input-field" required 
                        minlength="8" placeholder="Create a strong password">
                    <i class="fas fa-eye" onclick="togglePassword('password')"></i>
                </div>
                <div class="password-strength">
                    <div class="strength-bar-container">
                        <div id="strengthBar" class="strength-bar"></div>
                    </div>
                    <span id="strengthText" class="strength-text">Enter a password</span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="input-group">
                    <input type="password" id="confirmPassword" name="confirmPassword" class="input-field" required 
                        placeholder="Confirm your password">
                    <i class="fas fa-eye" onclick="togglePassword('confirmPassword')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" class="input-field" required min="16" 
                    placeholder="Must be 16 or older"
                    value="<?php echo htmlspecialchars($form_data['age'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Art Interests (Select all that apply)</label>
                <div class="art-interests">
                    <span class="art-interest" data-interest="Digital Art">Digital Art</span>
                    <span class="art-interest" data-interest="Traditional">Traditional</span>
                    <span class="art-interest" data-interest="Photography">Photography</span>
                    <span class="art-interest" data-interest="3D Art">3D Art</span>
                    <span class="art-interest" data-interest="Illustration">Illustration</span>
                </div>
                <input type="hidden" id="interests" name="interests" 
                    value="<?php echo htmlspecialchars($form_data['interests'] ?? '[]'); ?>">
            </div>

            <div class="form-group">
                <label for="bio">Short Bio</label>
                <textarea id="bio" name="bio" class="input-field" rows="3" maxlength="200" 
                    placeholder="Tell us about yourself and your art (optional)"><?php echo htmlspecialchars($form_data['bio'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="register-btn">Create Account</button>

            <p class="terms-text">
                By creating an account, you agree to our 
                <a href="terms.php">Terms of Service</a> and 
                <a href="privacy.php">Privacy Policy</a>
            </p>
        </form>
        
        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>

    <script src="../assets/js/register.js"></script>
</body>
</html>