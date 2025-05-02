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
                <div class="password-input">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength">
                    <div class="strength-bar-container">
                        <div id="strengthBar" class="strength-bar"></div>
                    </div>
                    <span id="strengthText" class="strength-text">Password strength</span>
                </div>
                <?php if (isset($_SESSION['validation_errors']['password'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['password']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="password-input">
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password_confirmation')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($_SESSION['validation_errors']['password_confirmation'])): ?>
                <span class="error"><?php echo $_SESSION['validation_errors']['password_confirmation']; ?></span>
                <?php endif; ?>
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
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        // Check password strength on input
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }
        
        registerForm.addEventListener('submit', function(e) {
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
                e.preventDefault();
                
                // Display errors
                Object.keys(errors).forEach(field => {
                    const inputElement = document.getElementById(field);
                    
                    // Remove existing error message
                    const existingError = inputElement.parentNode.querySelector('.error');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Add new error message
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'error';
                    errorSpan.textContent = errors[field];
                    
                    if (field === 'terms') {
                        inputElement.parentNode.appendChild(errorSpan);
                    } else {
                        const formGroup = inputElement.closest('.form-group');
                        formGroup.appendChild(errorSpan);
                    }
                });
                
                return;
            }
            
            // If form is submitted via AJAX, prevent default action
            if (window.useAjax) {
                e.preventDefault();
                
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
                                const inputElement = document.getElementById(field);
                                if (inputElement) {
                                    // Remove existing error message
                                    const existingError = inputElement.parentNode.querySelector('.error');
                                    if (existingError) {
                                        existingError.remove();
                                    }
                                    
                                    // Add new error message
                                    const errorSpan = document.createElement('span');
                                    errorSpan.className = 'error';
                                    errorSpan.textContent = data.errors[field];
                                    
                                    if (field === 'terms') {
                                        inputElement.parentNode.appendChild(errorSpan);
                                    } else {
                                        const formGroup = inputElement.closest('.form-group');
                                        formGroup.appendChild(errorSpan);
                                    }
                                }
                            });
                        }
                        
                        // Re-enable submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Create Account';
                    }
                })
                .catch(error => {
                    console.error('Registration error:', error);
                    
                    // Display generic error message
                    let alertElement = document.querySelector('.alert');
                    if (!alertElement) {
                        alertElement = document.createElement('div');
                        alertElement.className = 'alert alert-error';
                        registerForm.parentNode.insertBefore(alertElement, registerForm);
                    } else {
                        alertElement.className = 'alert alert-error';
                    }
                    
                    alertElement.textContent = 'A network error occurred. Please try again.';
                    
                    // Re-enable submit button
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Create Account';
                });
            }
        });
    });
    
    /**
     * Check password strength
     */
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        let strength = 0;
        
        // Empty password
        if (password.length === 0) {
            updateStrengthIndicator(0, 'Enter a password');
            return;
        }
        
        // Length check
        if (password.length >= 8) {
            strength += 1;
        }
        
        // Contains lowercase letters
        if (/[a-z]/.test(password)) {
            strength += 1;
        }
        
        // Contains uppercase letters
        if (/[A-Z]/.test(password)) {
            strength += 1;
        }
        
        // Contains numbers
        if (/[0-9]/.test(password)) {
            strength += 1;
        }
        
        // Contains special characters
        if (/[^a-zA-Z0-9]/.test(password)) {
            strength += 1;
        }
        
        // Update strength indicator
        updateStrengthIndicator(strength);
    }
    
    /**
     * Update password strength indicator
     */
    function updateStrengthIndicator(strength, message) {
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        if (strengthBar && strengthText) {
            // Calculate percentage
            const percent = (strength / 5) * 100;
            
            // Update strength bar
            strengthBar.style.width = percent + '%';
            
            // Update color based on strength
            if (strength === 0) {
                strengthBar.style.backgroundColor = '#ddd';
            } else if (strength <= 2) {
                strengthBar.style.backgroundColor = '#f44336'; // Weak
            } else if (strength <= 3) {
                strengthBar.style.backgroundColor = '#ff9800'; // Medium
            } else {
                strengthBar.style.backgroundColor = '#4caf50'; // Strong
            }
            
            // Update text
            if (message) {
                strengthText.textContent = message;
            } else {
                switch (strength) {
                    case 0:
                        strengthText.textContent = 'No password entered';
                        break;
                    case 1:
                    case 2:
                        strengthText.textContent = 'Weak password';
                        break;
                    case 3:
                        strengthText.textContent = 'Medium password';
                        break;
                    case 4:
                        strengthText.textContent = 'Strong password';
                        break;
                    case 5:
                        strengthText.textContent = 'Very strong password';
                        break;
                }
            }
        }
    }
    
    /**
     * Toggle password visibility
     */
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const toggleBtn = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            toggleBtn.classList.remove('fa-eye');
            toggleBtn.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            toggleBtn.classList.remove('fa-eye-slash');
            toggleBtn.classList.add('fa-eye');
        }
    }
</script>

<?php
// Clear session data
unset($_SESSION['old_input']);
unset($_SESSION['validation_errors']);
?>