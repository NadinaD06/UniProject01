/**
 * assets/js/login.js
 * Handles login functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(loginForm);
            const username = formData.get('username');
            const password = formData.get('password');
            const rememberMe = formData.get('rememberMe') === 'on';
            
            // Basic validation
            if (!username || !password) {
                showError('Please enter both username/email and password.');
                return;
            }
            
            // Show loading state
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerText;
            submitButton.innerText = 'Logging in...';
            submitButton.disabled = true;
            
            // Send login request
            fetch('../controllers/auth_controller.php?action=login', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitButton.innerText = originalButtonText;
                submitButton.disabled = false;
                
                if (data.success) {
                    // Show success message
                    showSuccess(data.message);
                    
                    // Store user data in localStorage for frontend use
                    if (data.user) {
                        localStorage.setItem('currentUser', JSON.stringify(data.user));
                    }
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'feed.html';
                    }, 1000);
                } else {
                    // Show error message
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                submitButton.innerText = originalButtonText;
                submitButton.disabled = false;
                showError('An error occurred. Please try again.');
            });
        });
    }
    
    // Check if already logged in
    checkSession();
});

/**
 * Check if user is already logged in
 */
function checkSession() {
    fetch('../controllers/auth_controller.php?action=check_session')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                // User is already logged in, redirect to feed
                window.location.href = 'feed.html';
            }
        })
        .catch(error => {
            console.error('Session check error:', error);
        });
}

/**
 * Show error message
 * 
 * @param {string} message Error message
 */
function showError(message) {
    // Create error element if it doesn't exist
    let errorElement = document.getElementById('errorMessage');
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.id = 'errorMessage';
        errorElement.className = 'error-message';
        
        // Insert after form heading
        const formHeading = document.querySelector('h2');
        if (formHeading && formHeading.parentNode) {
            formHeading.parentNode.insertBefore(errorElement, formHeading.nextSibling);
        } else {
            // Fallback to beginning of form
            const form = document.getElementById('loginForm');
            form.prepend(errorElement);
        }
    }
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    
    // Scroll to error message
    errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Show success message
 * 
 * @param {string} message Success message
 */
function showSuccess(message) {
    // Create success element if it doesn't exist
    let successElement = document.getElementById('successMessage');
    
    if (!successElement) {
        successElement = document.createElement('div');
        successElement.id = 'successMessage';
        successElement.className = 'success-message';
        
        // Insert after form heading
        const formHeading = document.querySelector('h2');
        if (formHeading && formHeading.parentNode) {
            formHeading.parentNode.insertBefore(successElement, formHeading.nextSibling);
        } else {
            // Fallback to beginning of form
            const form = document.getElementById('loginForm');
            form.prepend(successElement);
        }
    }
    
    successElement.textContent = message;
    successElement.style.display = 'block';
    
    // Hide any error messages
    const errorElement = document.getElementById('errorMessage');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
    
    // Scroll to success message
    successElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Toggle password visibility
 * 
 * @param {string} inputId ID of password input field
 */
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = passwordInput.nextElementSibling;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Make togglePassword available globally
window.togglePassword = togglePassword;