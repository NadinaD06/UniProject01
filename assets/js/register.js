document.addEventListener('DOMContentLoaded', function() {
    // Set up form submission
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegistration);
    }
    
    // Set up password strength checker
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    // Set up art interest toggles
    const artInterests = document.querySelectorAll('.art-interest');
    artInterests.forEach(interest => {
        interest.addEventListener('click', function() {
            this.classList.toggle('selected');
        });
    });
});

// Function to handle registration form submission
function handleRegistration(event) {
    event.preventDefault();
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    
    // Clear previous error messages
    clearErrors();
    
    // Validate form
    const validation = validateForm();
    
    if (!validation.valid) {
        // Show validation errors
        showValidationErrors(validation.errors);
        
        // Reset button
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        return;
    }
    
    // Collect selected interests
    const selectedInterests = [];
    document.querySelectorAll('.art-interest.selected').forEach(interest => {
        selectedInterests.push(interest.getAttribute('data-interest') || interest.textContent.trim());
    });
    
    // Set interests to hidden input
    const interestsInput = document.getElementById('interests');
    if (interestsInput) {
        interestsInput.value = JSON.stringify(selectedInterests);
    }
    
    // Get form data
    const formData = new FormData(this);
    
    // Send registration request
    fetch('../controllers/auth/register_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Save user ID to localStorage for convenience
            if (data.user && data.user.id) {
                localStorage.setItem('user_id', data.user.id);
                localStorage.setItem('username', data.user.username);
            }
            
            // Show success message and redirect
            showMessage('success', data.message || 'Registration successful!');
            
            setTimeout(() => {
                window.location.href = data.redirect || 'feed.html';
            }, 1000);
        } else {
            // Show error messages
            if (data.errors && Object.keys(data.errors).length > 0) {
                showValidationErrors(data.errors);
            } else {
                showMessage('error', data.message || 'Registration failed. Please try again.');
            }
            
            // Reset button
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Registration error:', error);
        showMessage('error', 'An error occurred. Please try again.');
        
        // Reset button
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}

// Function to validate the form
function validateForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const age = parseInt(document.getElementById('age').value) || 0;
    
    const errors = {};
    
    // Validate username
    if (!username) {
        errors.username = 'Username is required';
    } else if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
        errors.username = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
    }
    
    // Validate email
    if (!email) {
        errors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.email = 'Please enter a valid email address';
    }
    
    // Validate password
    if (!password) {
        errors.password = 'Password is required';
    } else if (password.length < 8) {
        errors.password = 'Password must be at least 8 characters';
    } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password)) {
        errors.password = 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character';
    }
    
    // Validate confirm password
    if (password !== confirmPassword) {
        errors.confirmPassword = 'Passwords do not match';
    }
    
    // Validate age
    if (age < 16) {
        errors.age = 'You must be at least 16 years old to register';
    }
    
    return {
        valid: Object.keys(errors).length === 0,
        errors: errors
    };
}

// Function to check password strength
function checkPasswordStrength() {
    const password = this.value;
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

// Function to show validation errors
function showValidationErrors(errors) {
    for (const field in errors) {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('error');
            
            // Create or update error message
            let errorElement = document.getElementById(`${field}-error`);
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = `${field}-error`;
                errorElement.className = 'error-text';
                input.parentNode.appendChild(errorElement);
            }
            
            errorElement.textContent = errors[field];
        }
    }
    
    // If there are errors, also show a general error message
    if (Object.keys(errors).length > 0) {
        showMessage('error', 'Please fix the errors in the form.');
    }
}

// Function to clear all errors
function clearErrors() {
    // Remove error class from inputs
    document.querySelectorAll('.error').forEach(element => {
        element.classList.remove('error');
    });
    
    // Remove error messages
    document.querySelectorAll('.error-text').forEach(element => {
        element.remove();
    });
    
    // Hide message container
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        messageContainer.style.display = 'none';
    }
}

// Function to show messages
function showMessage(type, message) {
    // Check if message container exists, if not create one
    let messageContainer = document.getElementById('message-container');
    
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'message-container';
        messageContainer.style.padding = '10px';
        messageContainer.style.marginBottom = '15px';
        messageContainer.style.borderRadius = '5px';
        messageContainer.style.display = 'none';
        
        // Insert before the form
        const form = document.getElementById('registerForm');
        form.parentNode.insertBefore(messageContainer, form);
    }
    
    // Set message styles based on type
    if (type === 'error') {
        messageContainer.style.backgroundColor = 'rgba(255, 107, 107, 0.1)';
        messageContainer.style.color = '#FF6B6B';
        messageContainer.style.border = '1px solid #FF6B6B';
    } else {
        messageContainer.style.backgroundColor = 'rgba(78, 205, 196, 0.1)';
        messageContainer.style.color = '#4ECDC4';
        messageContainer.style.border = '1px solid #4ECDC4';
    }
    
    // Set message content and show
    messageContainer.textContent = message;
    messageContainer.style.display = 'block';
    
    // Scroll to message
    messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Function to toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Function to toggle art interest selection
function toggleInterest(element) {
    element.classList.toggle('selected');
}