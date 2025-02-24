document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        alert('Please fill in all fields');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        const response = await fetch('login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            alert(data.message || 'Login failed');
        }
    } catch (error) {
        alert('An error occurred during login');
    }
});

function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    
    const icon = passwordField.nextElementSibling;
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}