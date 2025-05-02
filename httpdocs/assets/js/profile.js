$(document).ready(function() {
    // Handle friend requests
    $('.add-friend').on('click', function() {
        const button = $(this);
        const userId = button.data('user-id');
        
        $.ajax({
            url: '/friends/request',
            type: 'POST',
            data: { user_id: userId },
            success: function(response) {
                if (response.success) {
                    button.prop('disabled', true)
                          .html('<i class="fas fa-clock"></i> Friend Request Sent')
                          .removeClass('btn-primary')
                          .addClass('btn-secondary');
                    showAlert('Friend request sent', 'success');
                } else {
                    showAlert(response.message || 'Error sending friend request', 'danger');
                }
            },
            error: function() {
                showAlert('Error sending friend request', 'danger');
            }
        });
    });

    // Handle password change form
    $('form[action="/profile/update-password"]').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const newPassword = form.find('#new_password').val();
        const confirmPassword = form.find('#confirm_password').val();
        
        if (newPassword !== confirmPassword) {
            showAlert('New passwords do not match', 'danger');
            return;
        }
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('Password updated successfully', 'success');
                    form[0].reset();
                } else {
                    showAlert(response.message || 'Error updating password', 'danger');
                }
            },
            error: function() {
                showAlert('Error updating password', 'danger');
            }
        });
    });

    // Handle profile image upload
    $('#profileImage').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Image size should be less than 5MB', 'danger');
                this.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('Only JPG, PNG and GIF images are allowed', 'danger');
                this.value = '';
                return;
            }
        }
    });

    // Handle profile update form
    $('form[action="/profile/update"]').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('Profile updated successfully', 'success');
                    // Update profile image in navigation if changed
                    if (response.data.profile_image) {
                        $('.navbar .rounded-circle').attr('src', response.data.profile_image);
                    }
                } else {
                    showAlert(response.message || 'Error updating profile', 'danger');
                }
            },
            error: function() {
                showAlert('Error updating profile', 'danger');
            }
        });
    });

    // Handle account deletion
    $('.delete-account-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            return;
        }
        
        const form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = '/';
                } else {
                    showAlert(response.message || 'Error deleting account', 'danger');
                }
            },
            error: function() {
                showAlert('Error deleting account', 'danger');
            }
        });
    });
});

// Helper function to show alerts
function showAlert(message, type = 'info') {
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.container').prepend(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
} 