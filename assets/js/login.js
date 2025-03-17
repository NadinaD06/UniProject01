document.getElementById('loginForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent default form submission

    // Gather form data
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    // Create an AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/login_process.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    // Handle the server response
    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Redirect to a dashboard or display a success message
                alert('Login successful! Redirecting...');
                window.location.href = 'dashboard.html';
            } else {
                // Display error message
                alert('Error: ' + response.message);
            }
        } else {
            console.error('Error: ' + xhr.statusText);
        }
    };

    // Send the request with the form data
    xhr.send(`username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&rememberMe=${rememberMe}`);
});