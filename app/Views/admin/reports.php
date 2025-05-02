<?php
/**
 * Admin Reports View
 * Displays and manages user reports
 */
?>

<div class="container mt-4">
    <h2>User Reports</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Reports</h5>
                    <h2 class="card-text" id="totalReports">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="card-text" id="pendingReports">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Resolved</h5>
                    <h2 class="card-text" id="resolvedReports">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">This Month</h5>
                    <h2 class="card-text" id="monthlyReports">0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="reportFilters" class="row">
                        <div class="col-md-3">
                            <label for="statusFilter">Status</label>
                            <select class="form-control" id="statusFilter">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFilter">Date Range</label>
                            <select class="form-control" id="dateFilter">
                                <option value="week">Last Week</option>
                                <option value="month" selected>Last Month</option>
                                <option value="year">Last Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="searchFilter">Search</label>
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search reports...">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reporter</th>
                            <th>Reported User</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <!-- Reports will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div id="pagination" class="mt-4">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Report Action Modal -->
<div class="modal fade" id="reportActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Take Action</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="reportActionForm">
                    <input type="hidden" id="reportId">
                    <div class="form-group">
                        <label for="reportStatus">Status</label>
                        <select class="form-control" id="reportStatus" required>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adminAction">Action</label>
                        <select class="form-control" id="adminAction">
                            <option value="">No Action</option>
                            <option value="warn_user">Warn User</option>
                            <option value="block_user">Block User</option>
                            <option value="delete_content">Delete Content</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="actionNotes">Notes</label>
                        <textarea class="form-control" id="actionNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitAction">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    const reportsPerPage = 10;

    // Load initial data
    loadReports();
    loadStats();

    // Filter form submission
    document.getElementById('reportFilters').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadReports();
    });

    // Load reports
    function loadReports() {
        const status = document.getElementById('statusFilter').value;
        const dateRange = document.getElementById('dateFilter').value;
        const search = document.getElementById('searchFilter').value;

        fetch(`/api/reports?page=${currentPage}&status=${status}&period=${dateRange}&search=${search}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('reportsTableBody');
                tbody.innerHTML = '';

                data.reports.forEach(report => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${report.id}</td>
                        <td>${report.reporter_name}</td>
                        <td>${report.reported_user_name}</td>
                        <td>${report.reason}</td>
                        <td><span class="badge badge-${getStatusBadgeClass(report.status)}">${report.status}</span></td>
                        <td>${new Date(report.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="viewReport(${report.id})">View</button>
                            ${report.status === 'pending' ? 
                                `<button class="btn btn-sm btn-success" onclick="showActionModal(${report.id})">Take Action</button>` : 
                                ''}
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Update pagination
                updatePagination(data.total);
            })
            .catch(error => console.error('Error loading reports:', error));
    }

    // Load statistics
    function loadStats() {
        fetch('/api/reports/stats')
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalReports').textContent = data.total;
                document.getElementById('pendingReports').textContent = data.pending;
                document.getElementById('resolvedReports').textContent = data.resolved;
                document.getElementById('monthlyReports').textContent = data.monthly;
            })
            .catch(error => console.error('Error loading stats:', error));
    }

    // Update pagination
    function updatePagination(total) {
        const totalPages = Math.ceil(total / reportsPerPage);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        if (totalPages > 1) {
            const ul = document.createElement('ul');
            ul.className = 'pagination justify-content-center';

            // Previous button
            ul.innerHTML += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>
                </li>
            `;

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                ul.innerHTML += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }

            // Next button
            ul.innerHTML += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
                </li>
            `;

            pagination.appendChild(ul);
        }
    }

    // Change page
    window.changePage = function(page) {
        currentPage = page;
        loadReports();
    };

    // Show action modal
    window.showActionModal = function(reportId) {
        document.getElementById('reportId').value = reportId;
        $('#reportActionModal').modal('show');
    };

    // Submit action
    document.getElementById('submitAction').addEventListener('click', function() {
        const reportId = document.getElementById('reportId').value;
        const status = document.getElementById('reportStatus').value;
        const action = document.getElementById('adminAction').value;
        const notes = document.getElementById('actionNotes').value;

        fetch('/api/reports/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                report_id: reportId,
                status: status,
                admin_action: action,
                admin_notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                $('#reportActionModal').modal('hide');
                loadReports();
                loadStats();
                showAlert('success', 'Report updated successfully');
            } else {
                showAlert('danger', data.error || 'Failed to update report');
            }
        })
        .catch(error => {
            console.error('Error updating report:', error);
            showAlert('danger', 'An error occurred while updating the report');
        });
    });

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'pending': return 'warning';
            case 'reviewed': return 'info';
            case 'resolved': return 'success';
            default: return 'secondary';
        }
    }

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.card'));
        setTimeout(() => alertDiv.remove(), 5000);
    }
});
</script> 