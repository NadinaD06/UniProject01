<?php
/**
 * Registration view
 */
$page_title = 'Register - ' . $config['APP_NAME'];
$page_description = 'Create your account on ' . $config['APP_NAME'];
$page_css = 'auth';
$body_class = 'auth-page register-page';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Join ArtSpace</h1>
            <p>Connect with artists and share your creations</p>
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
        
        <form id="registerForm" action="/auth/register" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_SESSION['old_input']['username']) ? htmlspecialchars($_SESSION['old_input']['username']) : ''; ?>">
                <small>Only letters, numbers, and underscores</small>
                <?php if (isset($_SESSION['validation_errors']['username'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['username']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>">
                <?php if (isset($_SESSION['validation_errors']['email'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['email']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name (Optional)</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo isset($_SESSION['old_input']['full_name']) ? htmlspecialchars($_SESSION['old_input']['full_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small>At least 8 characters long</small>
                <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
                <?php if (isset($_SESSION['validation_errors']['password_confirmation'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['password_confirmation']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="age">Age (optional)</label>
                <input type="number" id="age" name="age" min="13" max="120"
                       value="<?php echo isset($_SESSION['old_input']['age']) ? htmlspecialchars($_SESSION['old_input']['age']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="bio">Bio (optional)</label>
                <textarea id="bio" name="bio" rows="3"><?php echo isset($_SESSION['old_input']['bio']) ? htmlspecialchars($_SESSION['old_input']['bio']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="interests">Interests (optional)</label>
                <textarea id="interests" name="interests" rows="3"><?php echo isset($_SESSION['old_input']['interests']) ? htmlspecialchars($_SESSION['old_input']['interests']) : ''; ?></textarea>
            </div>
            
            <div class="form-group terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a></label>
                <?php if (isset($_SESSION['validation_errors']['terms'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['terms']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </div>
            
            <div class="auth-links">
                <span>Already have an account?</span>
                <a href="/login">Log In</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic client-side validation
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            const terms = document.getElementById('terms').checked;
            
            // Create validation errors object
            let errors = {};
            let hasErrors = false;
            
            // Validate password length
            if (password.length < 8) {
                errors.password = 'Password must be at least 8 characters long';
                hasErrors = true;
            }
            
            // Validate password confirmation
            if (password !== passwordConfirmation) {
                errors.password_confirmation = 'Passwords do not match';
                hasErrors = true;
            }
            
            // Validate terms acceptance
            if (!terms) {
                errors.terms = 'You must agree to the Terms of Service and Privacy Policy';
                hasErrors = true;
            }
            
            // If there are validation errors, display them and stop form submission
            if (hasErrors) {
                // Display errors
                Object.keys(errors).forEach(field => {
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'error';
                    errorSpan.textContent = errors[field];
                    
                    // Remove any existing error message
                    const existingError = document.querySelector(`#${field} + .error`);
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Add new error message
                    document.getElementById(field).parentNode.appendChild(errorSpan);
                });
                
                return;
            }
            
            // Disable submit button to prevent multiple submissions
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Creating account...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('/auth/register', {
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
                    const errorMessage = data.message || 'An error occurred during registration.';
                    
                    // Create or update alert message
                    let alertElement = document.querySelector('.alert');
                    if (!alertElement) {
                        alertElement = document.createElement('div');
                        alertElement.className = 'alert alert-error';
                        registerForm.parentNode.insertBefore(alertElement, registerForm);
                    } else {
                        alertElement.className = 'alert alert-error';
                    }
                    
                    alertElement.textContent = errorMessage;
                    
                    // Display field errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            if (document.getElementById(field)) {
                                const errorSpan = document.createElement('span');
                                errorSpan.className = 'error';
                                errorSpan.textContent = data.errors[field];
                                
                                // Remove any existing error message
                                const existingError = document.querySelector(`