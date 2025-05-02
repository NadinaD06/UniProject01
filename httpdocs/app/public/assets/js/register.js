/**
 * assets/js/register.js
 * Handles user registration functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const errorContainer = document.getElementById('errorContainer');
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    // Initialize selected interests array
    let selectedInterests = [];
    
    if (registerForm) {
        // Password strength checker
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }
        
        // Form submission
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Update hidden interests field
            document.getElementById('interests').value = JSON.stringify(selectedInterests);
            
            // Get form data
            const formData = new FormData(registerForm);
            
            // Show loading state
            const submitButton = registerForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerText;
            submitButton.innerText = 'Creating Account...';
            submitButton.disabled = true;
            
            // Send registration request
            fetch('../controllers/auth_controller.php?action=register', {
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
                    }, 1500);
                } else {
                    // Show error messages
                    if (data.errors) {
                        showValidationErrors(data.errors);
                    } else {
                        showError(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                submitButton.innerText = originalButtonText;
                submitButton.disabled = false;
                showError('An error occurred. Please try again.');
            });
        });
    }
    
    // Interest selection
    const interestElements = document.querySelectorAll('.art-interest');
    interestElements.forEach(interest => {
        interest.addEventListener('click', function() {
            toggleInterest(this);
        });
    });
    
    /**
     * Validate the registration form
     * 
     * @return {boolean} True if form is valid
     */
    function validateForm() {
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const age = document.getElementById('age').value;
        
        let errors = {};
        
        // Validate username
        if (!username) {
            errors.username = 'Username is required';
        } else if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
            errors.username = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
        }
        
        // Validate email
        if (!email) {
            errors.email = 'Email is required';
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            errors.email = 'Please enter a valid email address';
        }
        
        // Validate password
        if (!password) {
            errors.password = 'Password is required';
        } else if (password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
        }
        
        // Validate confirm password
        if (password !== confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }
        
        // Validate age
        if (!age) {
            errors.age = 'Age is required';
        } else if (Number(age) < 16) {
            errors.age = 'You must be at least 16 years old to register';
        }
        
        // Show errors if any
        if (Object.keys(errors).length > 0) {
            showValidationErrors(errors);
            return false;
        }
        
        return true;
    }
    
    /**
     * Show validation errors
     * 
     * @param {object} errors Error messages by field
     */
    function showValidationErrors(errors) {
        // Clear previous error highlights
        document.querySelectorAll('.input-field').forEach(input => {
            input.classList.remove('error');
        });
        
        // Build error messages
        let errorHTML = '<ul>';
        
        Object.keys(errors).forEach(field => {
            errorHTML += `<li>${errors[field]}</li>`;
            
            // Highlight error field
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('error');
            }
        });
        
        errorHTML += '</ul>';
        
        // Display errors
        showError(errorHTML);
    }
    
    /**
     * Show error message
     * 
     * @param {string} message Error message
     */
    function showError(message) {
        if (errorContainer) {
            errorContainer.innerHTML = message;
            errorContainer.style.display = 'block';
            errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
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
            const formHeader = document.querySelector('.form-header');
            if (formHeader) {
                formHeader.appendChild(successElement);
            } else {
                // Fallback to beginning of form
                registerForm.prepend(successElement);
            }
        }
        
        successElement.textContent = message;
        successElement.style.display = 'block';
        
        // Hide error container
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
        
        // Scroll to success message
        successElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Check password strength
     * 
     * @param {string} password Password to check
     */
    function checkPasswordStrength(password) {
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
     * 
     * @param {number} strength Password strength (0-5)
     * @param {string} message Custom message (optional)
     */
    function updateStrengthIndicator(strength, message) {
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
});

/**
 * Toggle interest selection
 * 
 * @param {HTMLElement} element Interest element
 */
function toggleInterest(element) {
    element.classList.toggle('selected');
    
    const interest = element.getAttribute('data-interest');
    const interestsField = document.getElementById('interests');
    
    // Get current interests
    let interests = [];
    try {
        interests = JSON.parse(interestsField.value);
    } catch (e) {
        interests = [];
    }
    
    // Update interests array
    if (element.classList.contains('selected')) {
        // Add interest if not already in array
        if (!interests.includes(interest)) {
            interests.push(interest);
        }
    } else {
        // Remove interest
        interests = interests.filter(item => item !== interest);
    }
    
    // Update hidden field
    interestsField.value = JSON.stringify(interests);
}

// Make toggleInterest available globally
window.toggleInterest = toggleInterest;

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