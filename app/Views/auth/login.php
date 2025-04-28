<?php
/**
 * Login view
 */
$page_title = 'Login - ' . $config['APP_NAME'];
$page_description = 'Login to your account on ' . $config['APP_NAME'];
$page_css = 'auth';
?>

<div class="auth-container">
    <div class="auth-box">
        <h1>Login</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful! Please login.</div>
        <?php endif; ?>
        
        <form action="/login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="/register">Register here</a></p>
            <p><a href="/forgot-password">Forgot your password?</a></p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.querySelector('.auth-form');
        
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button to prevent multiple submissions
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Logging in...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('/login', {
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