<?php
// filepath: /Users/nadinad/UniProject01/httpdocs/index.php
switch ($request_uri) {
    case '/feed':
        require_once APP_PATH . '/Controllers/PostController.php';
        $controller = new PostController();
        $controller->index();
        break;
    case '/profile':
        require_once APP_PATH . '/Controllers/ProfileController.php';
        $controller = new ProfileController();
        $controller->show();
        break;
    // Add other routes here
}<?php
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request_uri) {
    case '/feed':
        require_once APP_PATH . '/Controllers/PostController.php';
        $controller = new PostController();
        $controller->index();
        break;
    case '/profile':
        require_once APP_PATH . '/Controllers/ProfileController.php';
        $controller = new ProfileController();
        $controller->show();
        break;
    case '/admin':
        require_once APP_PATH . '/Controllers/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
        break;
    default:
        http_response_code(404);
        echo "Page not found.";
        break;
}<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UniSocial</title>
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
                        <a class="nav-link active" href="/admin">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/reports">Reports</a>
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

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2 class="card-text"><?= $stats['users'] ?></h2>
                        <a href="/admin/users" class="btn btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Reports</h5>
                        <?php foreach ($stats['reports'] as $report): ?>
                            <p class="card-text">
                                <?= ucfirst($report['status']) ?>: <?= $report['count'] ?>
                            </p>
                        <?php endforeach; ?>
                        <a href="/admin/reports" class="btn btn-primary">View Reports</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Blocks</h5>
                        <h2 class="card-text"><?= $stats['blocks'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Activity data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/admin.js"></script>
</body>
</html> 