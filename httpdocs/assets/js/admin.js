// Admin Dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Load recent activity
    loadRecentActivity();
    
    // Set up auto-refresh for activity
    setInterval(loadRecentActivity, 30000); // Refresh every 30 seconds
});

// Load recent activity
function loadRecentActivity() {
    fetch('/api/admin/activity')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = '';
            
            data.forEach(activity => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${activity.type}</td>
                    <td>${activity.user}</td>
                    <td>${activity.action}</td>
                    <td>${new Date(activity.date).toLocaleString()}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error loading activity:', error));
}

// User Management
function confirmDelete(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/deleteUser';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_id';
        input.value = userId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Report Management
function updateReportStatus(reportId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/updateReport';
    
    const reportInput = document.createElement('input');
    reportInput.type = 'hidden';
    reportInput.name = 'report_id';
    reportInput.value = reportId;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status;
    
    form.appendChild(reportInput);
    form.appendChild(statusInput);
    document.body.appendChild(form);
    form.submit();
}

// Search functionality
function searchUsers(query) {
    const rows = document.querySelectorAll('tbody tr');
    const searchTerm = query.toLowerCase();
    
    rows.forEach(row => {
        const username = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
        
        if (username.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Export functions for use in HTML
window.confirmDelete = confirmDelete;
window.updateReportStatus = updateReportStatus;
window.searchUsers = searchUsers; 