<?php
/**
 * Login view
 */
$title = "Login - " . $config['APP_NAME'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h2>Welcome Back!</h2>
                    <p class="lead">Sign in to continue your social journey</p>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="/login" method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="/register">Register here</a></p>
                        <p><a href="/forgot-password">Forgot your password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 20px;
    box-shadow: 0 4px 6px var(--shadow-color);
    border: 3px solid var(--primary-color);
    margin-top: 2rem;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 17px 17px 0 0;
    padding: 2rem;
}

.card-header h2 {
    margin: 0;
    font-size: 2.5rem;
    text-shadow: 2px 2px 0 var(--accent-color);
}

.card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-control {
    border-radius: 15px;
    padding: 0.8rem;
    border: 2px solid var(--primary-color);
}

.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 1rem;
    font-size: 1.2rem;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: scale(1.05);
}

.alert {
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

a {
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

a:hover {
    color: var(--secondary-color);
    text-decoration: none;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

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