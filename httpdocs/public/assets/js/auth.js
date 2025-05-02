document.addEventListener('DOMContentLoaded', function() {
    // Registration form validation
    const registerForm = document.querySelector('form[action="/register"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const username = this.querySelector('#username').value.trim();
            const email = this.querySelector('#email').value.trim();
            const password = this.querySelector('#password').value;
            const confirmPassword = this.querySelector('#confirm_password').value;
            const age = this.querySelector('#age').value;
            
            // Clear previous errors
            clearErrors();
            
            // Validate username
            if (username.length < 3) {
                showError('username', 'Username must be at least 3 characters long');
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showError('username', 'Username can only contain letters, numbers, and underscores');
                return;
            }
            
            // Validate email
            if (!isValidEmail(email)) {
                showError('email', 'Please enter a valid email address');
                return;
            }
            
            // Validate password
            if (password.length < 8) {
                showError('password', 'Password must be at least 8 characters long');
                return;
            }
            
            if (!/[A-Z]/.test(password)) {
                showError('password', 'Password must contain at least one uppercase letter');
                return;
            }
            
            if (!/[a-z]/.test(password)) {
                showError('password', 'Password must contain at least one lowercase letter');
                return;
            }
            
            if (!/[0-9]/.test(password)) {
                showError('password', 'Password must contain at least one number');
                return;
            }
            
            // Validate password confirmation
            if (password !== confirmPassword) {
                showError('confirm_password', 'Passwords do not match');
                return;
            }
            
            // Validate age if provided
            if (age && (age < 13 || age > 120)) {
                showError('age', 'Age must be between 13 and 120');
                return;
            }
            
            // If all validation passes, submit the form
            this.submit();
        });
    }
    
    // Login form validation
    const loginForm = document.querySelector('form[action="/login"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const username = this.querySelector('#username').value.trim();
            const password = this.querySelector('#password').value;
            
            // Clear previous errors
            clearErrors();
            
            // Validate username
            if (!username) {
                showError('username', 'Username is required');
                return;
            }
            
            // Validate password
            if (!password) {
                showError('password', 'Password is required');
                return;
            }
            
            // If all validation passes, submit the form
            this.submit();
        });
    }
    
    // Helper functions
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
        field.classList.add('error');
    }
    
    function clearErrors() {
        // Remove all error messages
        document.querySelectorAll('.error-message').forEach(error => error.remove());
        
        // Remove error class from all fields
        document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
    }
}); 