<?php
/**
 * Footer include file
 * Contains the footer elements and any modals/popups
 */
?>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-links">
            <a href="/about">About</a>
            <a href="/terms">Terms of Service</a>
            <a href="/privacy">Privacy Policy</a>
            <a href="/help">Help Center</a>
            <a href="/contact">Contact Us</a>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> ArtSpace. All rights reserved.</p>
        </div>
    </div>
</footer>

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Create Post Floating Button -->
<a href="/create-post" class="create-post-btn">
    <i class="fas fa-plus"></i>
</a>

<!-- Report Modal -->
<div class="modal" id="reportModal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Report Content</h2>
        <form id="reportForm">
            <input type="hidden" id="reportType" name="report_type" value="">
            <input type="hidden" id="contentId" name="content_id" value="">
            
            <div class="form-group">
                <label>Reason for reporting:</label>
                <select name="reason" id="reportReason" required>
                    <option value="" disabled selected>Select a reason</option>
                    <option value="inappropriate">Inappropriate Content</option>
                    <option value="spam">Spam or Misleading</option>
                    <option value="harassment">Harassment or Bullying</option>
                    <option value="copyright">Copyright Infringement</option>
                    <option value="ai_disclosure">AI Usage Not Disclosed</option>
                    <option value="impersonation">Impersonation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Additional details:</label>
                <textarea name="description" id="reportDescription" rows="4" placeholder="Please provide any additional details about your report..."></textarea>
            </div>
            
            <button type="submit" class="submit-report">Submit Report</button>
            <button type="button" class="cancel-btn" id="cancelReport">Cancel</button>
        </form>
    </div>
</div>

<!-- Global JavaScript for modals and common functionality -->
<script>
    // Report modal functionality
    const reportModal = document.getElementById('reportModal');
    const reportForm = document.getElementById('reportForm');
    const reportTypeInput = document.getElementById('reportType');
    const contentIdInput = document.getElementById('contentId');
    const cancelReportBtn = document.getElementById('cancelReport');
    const closeModalBtn = reportModal.querySelector('.close');
    
    // Function to open report modal
    window.openReportModal = function(type, id) {
        reportTypeInput.value = type;
        contentIdInput.value = id;
        reportModal.style.display = 'block';
        document.body.classList.add('modal-open');
    };
    
    // Close modal functionality
    function closeReportModal() {
        reportModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        reportForm.reset();
    }
    
    closeModalBtn.addEventListener('click', closeReportModal);
    cancelReportBtn.addEventListener('click', closeReportModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === reportModal) {
            closeReportModal();
        }
    });
    
    // Handle report form submission
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(reportForm);
        
        // Submit report via AJAX
        fetch('/api/report?action=create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Report submitted successfully. Thank you for helping keep ArtSpace safe.', 'success');
                closeReportModal();
            } else {
                showToast(data.message || 'Failed to submit report', 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting report:', error);
            showToast('An error occurred while submitting your report', 'error');
        });
    });
    
    // Toast notification system
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            </div>
            <div class="toast-content">${message}</div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Add show class after a small delay (for animation)
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Add close button functionality
        const closeButton = toast.querySelector('.toast-close');
        closeButton.addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
        
        // Remove toast after 5 seconds
        setTimeout(() => {
            if (toast.parentNode === toastContainer) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode === toastContainer) {
                        toast.remove();
                    }
                }, 300);
            }
        }, 5000);
    };
    
    <?php if (isset($_SESSION['flash_message'])): ?>
    // Show flash message if it exists
    showToast('<?php echo $_SESSION['flash_message']['message']; ?>', '<?php echo $_SESSION['flash_message']['type']; ?>');
    <?php 
    // Clear the flash message
    unset($_SESSION['flash_message']);
    endif; 
    ?>
</script>
<?php endif; ?>