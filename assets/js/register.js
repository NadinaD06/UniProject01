document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    // Password strength checker (if elements exist)
    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }

    // Form submission
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            return validateForm(event);
        });
    }
});

// Validation functions
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
        .map(el => el.getAttribute('data-interest') || el.textContent);
    
    // Set interests to hidden input if it exists
    const interestsInput = document.getElementById('interests');
    if (interestsInput) {
        interestsInput.value = JSON.stringify(selectedInterests);
    }

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

function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (!strengthBar || !strengthText) return;
    
    let strength = 0;
    let feedback = '';
    
    // Length check
    if (password.length >= 8) {
        strength += 1;
    }
    
    // Contains lowercase
    if (/[a-z]/.test(password)) {
        strength += 1;
    }
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) {
        strength += 1;
    }
    
    // Contains number
    if (/\d/.test(password)) {
        strength += 1;
    }
    
    // Contains special character
    if (/[@$!%*?&]/.test(password)) {
        strength += 1;
    }
    
    // Update UI based on strength
    let percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    
    if (strength === 0) {
        strengthBar.style.backgroundColor = '#ddd';
        feedback = 'Enter a password';
    } else if (strength < 3) {
        strengthBar.style.backgroundColor = '#ff6b6b';
        feedback = 'Weak';
    } else if (strength < 5) {
        strengthBar.style.backgroundColor = '#ffaa33';
        feedback = 'Moderate';
    } else {
        strengthBar.style.backgroundColor = '#4ECDC4';
        feedback = 'Strong';
    }
    
    strengthText.textContent = feedback;
}

function showError(message) {
    // Try to find existing error container
    let errorContainer = document.getElementById('errorContainer');
    
    // If error container doesn't exist, create a new error div
    if (!errorContainer) {
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
    } else {
        // Use existing error container
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';
        
        // Scroll to error
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
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