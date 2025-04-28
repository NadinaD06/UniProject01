<?php
/**
 * Login view
 */
$page_title = 'Login - ArtSpace';
$page_description = 'Log in to your ArtSpace account';
$body_class = 'auth-page login-page';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Log In to ArtSpace</h1>
            <p>Share your artwork with the world</p>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
            <?php echo $_SESSION['flash_message']['message']; ?>
        </div>
        <?php 
        // Clear the flash message
        unset($_SESSION['flash_message']);
        endif; 
        ?>
        
        <form id="loginForm" action="/auth/login" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_SESSION['old_input']['username']) ? htmlspecialchars($_SESSION['old_input']['username']) : ''; ?>">
                <?php if (isset($_SESSION['validation_errors']['username'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['username']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </div>
            
            <div class="auth-links">
                <a href="/forgot-password">Forgot Password?</a>
                <span class="separator">•</span>
                <a href="/register">Create Account</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button to prevent multiple submissions
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Logging in...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('/auth/login', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to dashboard or specified redirect URL
                    window.location.href = data.data.redirect || '/feed';
                } else {
                    // Display error message
                    const errorMessage = data.message || 'An error occurred during login.';
                    
                    // Create or update alert message
                    let alertElement = document.querySelector('.alert');
                    if (!alertElement) {
                        alertElement = document.createElement('div');
                        alertElement.className = 'alert alert-error';
                        loginForm.parentNode.insertBefore(alertElement, loginForm);
                    } else {
                        alertElement.className = 'alert alert-error';
                    }
                    
                    alertElement.textContent = errorMessage;
                    
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Log In';
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                
                // Display generic error message
                let alertElement = document.querySelector('.alert');
                if (!alertElement) {
                    alertElement = document.createElement('div');
                    alertElement.className = 'alert alert-error';
                    loginForm.parentNode.insertBefore(alertElement, loginForm);
                } else {
                    alertElement.className = 'alert alert-error';
                }
                
                alertElement.textContent = 'A network error occurred. Please try again.';
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Log In';
            });
        });
    });
</script>

<?php
// Clear session data
unset($_SESSION['old_input']);
unset($_SESSION['validation_errors']);
?>