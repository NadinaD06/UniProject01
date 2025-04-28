<?php
$title = "404 - Page Not Found";
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container text-center">
    <div class="error-page">
        <h1 class="display-1">404</h1>
        <h2>Oops! Page Not Found</h2>
        <p class="lead">The page you're looking for doesn't exist or has been moved.</p>
        <div class="error-illustration">
            <img src="/assets/images/404-illustration.png" alt="404 Illustration" class="img-fluid">
        </div>
        <div class="mt-4">
            <a href="/" class="btn btn-primary">Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 4rem 0;
}

.error-page h1 {
    color: var(--primary-color);
    font-size: 8rem;
    font-weight: bold;
    text-shadow: 3px 3px 0 var(--accent-color);
    margin-bottom: 1rem;
}

.error-page h2 {
    color: var(--secondary-color);
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.error-illustration {
    max-width: 400px;
    margin: 2rem auto;
}

.error-illustration img {
    border-radius: 20px;
    box-shadow: 0 4px 6px var(--shadow-color);
    border: 3px solid var(--accent-color);
}

.btn {
    margin: 0 0.5rem;
    padding: 0.8rem 2rem;
    font-size: 1.2rem;
}
</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?> 