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
    <title>Join ArtSpace - Connect with Artists</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/register.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="/index.php" class="logo-link">ArtSpace</a>
        </div>
    </nav>

    <div class="register-container">
        <div class="form-header">
            <h1>Join ArtSpace</h1>
            <p>Connect with artists and share your creative journey</p>
        </div>

        <form id="registerForm" action="../controllers/auth/register_process.php" method="POST" onsubmit="return validateForm(event)">
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="input-field" required minlength="3" maxlength="20" placeholder="Choose a unique username">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="input-field" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="input-field" required minlength="8" placeholder="Create a strong password">
                    <i class="fas fa-eye" onclick="togglePassword('password')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="input-group">
                    <input type="password" id="confirmPassword" name="confirmPassword" class="input-field" required placeholder="Confirm your password">
                    <i class="fas fa-eye" onclick="togglePassword('confirmPassword')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" class="input-field" required min="16" placeholder="Must be 16 or older">
            </div>

            <div class="form-group">
                <label>Art Interests (Select all that apply)</label>
                <div class="art-interests">
                    <span class="art-interest" onclick="toggleInterest(this)" data-interest="Digital Art">Digital Art</span>
                    <span class="art-interest" onclick="toggleInterest(this)" data-interest="Traditional">Traditional</span>
                    <span class="art-interest" onclick="toggleInterest(this)" data-interest="Photography">Photography</span>
                    <span class="art-interest" onclick="toggleInterest(this)" data-interest="3D Art">3D Art</span>
                    <span class="art-interest" onclick="toggleInterest(this)" data-interest="Illustration">Illustration</span>
                </div>
                <input type="hidden" id="interests" name="interests" value="">
            </div>

            <div class="form-group">
                <label for="bio">Short Bio</label>
                <textarea id="bio" name="bio" class="input-field" rows="3" maxlength="200" placeholder="Tell us about yourself and your art (optional)"></textarea>
            </div>

            <button type="submit" class="register-btn">Create Account</button>

            <p class="terms-text">
                By creating an account, you agree to our 
                <a href="/terms">Terms of Service</a> and 
                <a href="/privacy">Privacy Policy</a>
            </p>
        </form>
        
        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>

    <script src="/assets/js/register.js"></script>
    <script>
        function validateForm(event) {
            event.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const age = parseInt(document.getElementById('age').value);
            
            if (password !== confirmPassword) {
                showError('Passwords do not match!');
                return false;
            }
            
            if (age < 16) {
                showError('You must be at least 16 years old to register.');
                return false;
            }
            
            // Collect selected interests and set to hidden input
            const selectedInterests = Array.from(document.querySelectorAll('.art-interest.selected'))
                .map(el => el.getAttribute('data-interest'));
            document.getElementById('interests').value = JSON.stringify(selectedInterests);

            // Show loading state
            const submitButton = document.querySelector('.register-btn');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';

            // Run validation checks
            try {
                // Validate email format
                if (!validateEmail(document.getElementById('email').value)) {
                    throw new Error('Invalid email format');
                }

                // Validate username
                if (!validateUsername(document.getElementById('username').value)) {
                    throw new Error('Username must be 3-20 characters and contain only letters, numbers, and underscores');
                }

                // Validate password strength
                if (!validatePassword(password)) {
                    throw new Error('Password must be at least 8 characters and include uppercase, lowercase, number, and special character');
                }

                // If all validations pass, submit the form
                document.getElementById('registerForm').submit();

            } catch (error) {
                // Error handling
                submitButton.disabled = false;
                submitButton.textContent = originalText;
                showError(error.message);
                return false;
            }
        }

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function validateUsername(username) {
            return /^[a-zA-Z0-9_]{3,20}$/.test(username);
        }

        function validatePassword(password) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password);
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.padding = '1rem';
            errorDiv.style.marginTop = '1rem';
            errorDiv.style.backgroundColor = 'rgba(255,107,107,0.1)';
            errorDiv.style.borderRadius = '0.5rem';
            errorDiv.textContent = message;

            const form = document.getElementById('registerForm');
            
            // Check if there's already an error message
            const existingError = form.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            form.insertBefore(errorDiv, form.firstChild);

            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function toggleInterest(element) {
            element.classList.toggle('selected');
        }
    </script>
</body>
</html>