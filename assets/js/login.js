document.addEventListener('DOMContentLoaded', function() {
        // Check if user is already logged in
        function checkSession() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '../controller/check_session.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.loggedIn) {
                        window.location.href = 'dashboard.html';
                    }
                }
            };
            xhr.send();
        }
        
        // Call session check on page load
        window.addEventListener('DOMContentLoaded', checkSession);
        
        // Password visibility toggle
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        }
    // Get the login form
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            
            // Show loading state
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            
            // Gather form data
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('rememberMe').checked;
            
            // Create form data object
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('rememberMe', rememberMe);
            
            // Create an AJAX request using fetch
            fetch('../controllers/login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.message, 'success');
                    
                    // Redirect to the specified page or default to feed
                    setTimeout(function() {
                        window.location.href = data.redirect || 'feed.html';
                    }, 1000);
                } else {
                    // Show error message
                    showMessage(data.message, 'error');
                    
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
    
    // Function to check active session
    function checkSession() {
        fetch('../controllers/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    window.location.href = 'feed.html';
                }
            })
            .catch(error => {
                console.error('Session check error:', error);
            });
    }
    
    // Call session check on page load
    checkSession();
    
    // Function to show message
    function showMessage(message, type) {
        // Look for existing message container
        let messageContainer = document.querySelector('.message-container');
        
        // Create container if it doesn't exist
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.querySelector('.container').prepend(messageContainer);
        }
        
        // Create message element
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.textContent = message;
        
        // Add to container
        messageContainer.innerHTML = '';
        messageContainer.appendChild(messageElement);
        
        // Auto remove after 5 seconds
        if (type === 'error') {
            setTimeout(() => {
                messageElement.remove();
            }, 5000);
        }
    }
});

// Password visibility toggle
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = document.querySelector(`#${fieldId} + i`);
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        if (toggleIcon) {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        }
    } else {
        passwordField.type = 'password';
        if (toggleIcon) {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
}