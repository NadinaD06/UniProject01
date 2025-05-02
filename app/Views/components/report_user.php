<?php
/**
 * Report User Component
 * A reusable component for reporting users
 * 
 * @param int $reportedUserId The ID of the user being reported
 * @param string $buttonClass Optional CSS class for the report button
 */
?>

<!-- Report Button -->
<button type="button" class="btn btn-sm btn-danger <?php echo $buttonClass ?? ''; ?>" 
        data-toggle="modal" 
        data-target="#reportModal<?php echo $reportedUserId; ?>">
    <i class="fas fa-flag"></i> Report
</button>

<!-- Report Modal -->
<div class="modal fade" id="reportModal<?php echo $reportedUserId; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportForm<?php echo $reportedUserId; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $reportedUserId; ?>">
                    <div class="form-group">
                        <label for="reportReason<?php echo $reportedUserId; ?>">Reason for Report</label>
                        <select class="form-control" id="reportReason<?php echo $reportedUserId; ?>" name="reason" required>
                            <option value="">Select a reason</option>
                            <option value="inappropriate_content">Inappropriate Content</option>
                            <option value="harassment">Harassment</option>
                            <option value="spam">Spam</option>
                            <option value="fake_account">Fake Account</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reportDetails<?php echo $reportedUserId; ?>">Additional Details</label>
                        <textarea class="form-control" id="reportDetails<?php echo $reportedUserId; ?>" 
                                name="details" rows="3" placeholder="Please provide any additional details..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitReport(<?php echo $reportedUserId; ?>)">
                    Submit Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function submitReport(userId) {
    const form = document.getElementById(`reportForm${userId}`);
    const reason = document.getElementById(`reportReason${userId}`).value;
    const details = document.getElementById(`reportDetails${userId}`).value;

    if (!reason) {
        alert('Please select a reason for the report');
        return;
    }

    const reportData = {
        user_id: userId,
        reason: reason + (details ? `: ${details}` : '')
    };

    fetch('/api/reports', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reportData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            // Close modal
            $(`#reportModal${userId}`).modal('hide');
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                Report submitted successfully
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
            setTimeout(() => alertDiv.remove(), 5000);
        } else {
            alert(data.error || 'Failed to submit report');
        }
    })
    .catch(error => {
        console.error('Error submitting report:', error);
        alert('An error occurred while submitting the report');
    });
}
</script> 