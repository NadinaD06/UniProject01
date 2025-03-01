let typingTimer;
const doneTypingInterval = 500;

// Sanitize input to prevent XSS and SQL injection
function sanitizeInput(input) {
    return input.replace(/['"\\;()<>]/g, '').trim();
}

// Username validation
document.getElementById('username').addEventListener('keyup', function() {
    clearTimeout(typingTimer);
    const username = this.value;
    
    if (username) {
        typingTimer = setTimeout(() => checkUsername(sanitizeInput(username)), doneTypingInterval);
    }
});

async function checkUsername(username) {
    const statusElement = document.getElementById('usernameStatus');
    
    if (username.length < 3) {
        statusElement.textContent = '✗ Username must be at least 3 characters';
        statusElement.className = 'status-message error';
        return;
    }

    try {
        const response = await fetch('/api/check-username', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username: username })
        });
        
        const data = await response.json();
        statusElement.textContent = data.available ? '✓ Username available' : '✗ Username taken';
        statusElement.className = 'status-message ' + (data.available ? 'success' : 'error');
    } catch (error) {
        statusElement.textContent = 'Error checking username';
        statusElement.className = 'status-message error';
    }
}

// Email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = sanitizeInput(this.value);
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const statusElement = document.getElementById('emailStatus');
    
    if (emailRegex.test(email)) {
        checkEmail(email);
    } else {
        statusElement.textContent = '✗ Invalid email format';
        statusElement.className = 'status-message error';
    }
});

async function checkEmail(email) {
    const statusElement = document.getElementById('emailStatus');
    try {
        const response = await fetch('/api/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        statusElement.textContent = data.available ? '✓ Email available' : '✗ Email already registered';
        statusElement.className = 'status-message ' + (data.available ? 'success' : 'error');
    } catch (error) {
        statusElement.textContent = 'Error checking email';
        statusElement.className = 'status-message error';
    }
}

// Age validation
document.getElementById('age').addEventListener('input', function() {
    const age = parseInt(this.value);
    const statusElement = document.getElementById('ageStatus');
    
    if (age < 13) {
        statusElement.textContent = '✗ Must be 13 or older to register';
        statusElement.className = 'status-message error';
    } else if (age > 120) {
        statusElement.textContent = '✗ Please enter a valid age';
        statusElement.className = 'status-message error';
    } else {
        statusElement.textContent = '✓ Valid age';
        statusElement.className = 'status-message success';
    }
});

// Password strength check
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthElement = document.getElementById('passwordStrength');
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    let strength = 0;
    let message = '';
    
    if (password.length >= 8) strength++;
    if (hasUpperCase && hasLowerCase) strength++;
    if (hasNumbers) strength++;
    if (hasSpecialChar) strength++;
    
    switch(strength) {
        case 0:
        case 1:
            message = '✗ Weak password';
            strengthElement.className = 'status-message error';
            break;
        case 2:
            message = '⚠ Moderate password';
            strengthElement.className = 'status-message warning';
            break;
        case 3:
            message = '✓ Strong password';
            strengthElement.className = 'status-message success';
            break;
        case 4:
            message = '✓ Very strong password';
            strengthElement.className = 'status-message success';
            break;
    }
    
    strengthElement.textContent = message;
});

// Password matching validation
document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);
document.getElementById('password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchStatus = document.getElementById('passwordMatch');
    
    if (confirmPassword) {
        if (password === confirmPassword) {
            matchStatus.textContent = '✓ Passwords match';
            matchStatus.className = 'status-message success';
        } else {
            matchStatus.textContent = '✗ Passwords do not match';
            matchStatus.className = 'status-message error';
        }
    }
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
}

// Form validation
function validateForm(event) {
    event.preventDefault();
    
    const username = sanitizeInput(document.getElementById('username').value);
    const email = sanitizeInput(document.getElementById('email').value);
    const age = parseInt(document.getElementById('age').value);
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!username || !email || !age || !password || !confirmPassword) {
        alert('Please fill in all fields');
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }

    if (age < 13 || age > 120) {
        alert('Please enter a valid age (13-120)');
        return false;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }

    if (password.length < 8) {
        alert('Password must be at least 8 characters long');
        return false;
    }

    submitForm(username, email, age, password);
}

async function submitForm(username, email, age, password) {
    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                email: email,
                age: age,
                password: password
            })
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.href = '/dashboard';
        } else {
            alert(data.message || 'Registration failed');
        }
    } catch (error) {
        alert('An error occurred during registration');
    }
}