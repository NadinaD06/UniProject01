<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Management - UniSocial Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/admin">UniSocial Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/reports">Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['flash']['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Status Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="/admin/reports" class="btn btn-outline-primary <?= !$status ? 'active' : '' ?>">
                        All
                    </a>
                    <a href="/admin/reports?status=pending" class="btn btn-outline-primary <?= $status === 'pending' ? 'active' : '' ?>">
                        Pending
                    </a>
                    <a href="/admin/reports?status=resolved" class="btn btn-outline-primary <?= $status === 'resolved' ? 'active' : '' ?>">
                        Resolved
                    </a>
                    <a href="/admin/reports?status=dismissed" class="btn btn-outline-primary <?= $status === 'dismissed' ? 'active' : '' ?>">
                        Dismissed
                    </a>
                </div>
            </div>
        </div>

        <!-- Reports List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Reports</h5>
            </div>
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
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= $report['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $report['reporter_image'] ?: '/assets/images/default-avatar.png' ?>" 
                                                 alt="Reporter" 
                                                 class="rounded-circle me-2"
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                            <?= htmlspecialchars($report['reporter_username']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $report['reported_image'] ?: '/assets/images/default-avatar.png' ?>" 
                                                 alt="Reported" 
                                                 class="rounded-circle me-2"
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                            <?= htmlspecialchars($report['reported_username']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($report['reason']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $report['status'] === 'pending' ? 'warning' : 
                                                              ($report['status'] === 'resolved' ? 'success' : 'secondary') ?>">
                                            <?= ucfirst($report['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($report['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?= $report['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#resolveModal<?= $report['id'] ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#dismissModal<?= $report['id'] ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $report['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Report Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <dl class="row">
                                                            <dt class="col-sm-4">Reporter</dt>
                                                            <dd class="col-sm-8"><?= htmlspecialchars($report['reporter_username']) ?></dd>
                                                            
                                                            <dt class="col-sm-4">Reported User</dt>
                                                            <dd class="col-sm-8"><?= htmlspecialchars($report['reported_username']) ?></dd>
                                                            
                                                            <dt class="col-sm-4">Reason</dt>
                                                            <dd class="col-sm-8"><?= htmlspecialchars($report['reason']) ?></dd>
                                                            
                                                            <dt class="col-sm-4">Status</dt>
                                                            <dd class="col-sm-8">
                                                                <span class="badge bg-<?= $report['status'] === 'pending' ? 'warning' : 
                                                                                      ($report['status'] === 'resolved' ? 'success' : 'secondary') ?>">
                                                                    <?= ucfirst($report['status']) ?>
                                                                </span>
                                                            </dd>
                                                            
                                                            <dt class="col-sm-4">Date</dt>
                                                            <dd class="col-sm-8"><?= date('M j, Y H:i', strtotime($report['created_at'])) ?></dd>
                                                            
                                                            <?php if ($report['admin_id']): ?>
                                                                <dt class="col-sm-4">Handled By</dt>
                                                                <dd class="col-sm-8"><?= htmlspecialchars($report['admin_username']) ?></dd>
                                                                
                                                                <dt class="col-sm-4">Handled At</dt>
                                                                <dd class="col-sm-8"><?= date('M j, Y H:i', strtotime($report['updated_at'])) ?></dd>
                                                            <?php endif; ?>
                                                        </dl>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Resolve Modal -->
                                        <div class="modal fade" id="resolveModal<?= $report['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Resolve Report</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="/admin/updateReport" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                            <input type="hidden" name="status" value="resolved">
                                                            <p>Are you sure you want to mark this report as resolved?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Resolve</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Dismiss Modal -->
                                        <div class="modal fade" id="dismissModal<?= $report['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Dismiss Report</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="/admin/updateReport" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                            <input type="hidden" name="status" value="dismissed">
                                                            <p>Are you sure you want to dismiss this report?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Dismiss</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 