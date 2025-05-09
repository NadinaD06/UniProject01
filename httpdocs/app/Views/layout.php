<?php
/**
 * Main layout template for ArtSpace
 * All views will extend this template
 */

// Define default values if not set
$page_title = $page_title ?? 'ArtSpace - Connect, Create, Inspire';
$page_description = $page_description ?? 'Join ArtSpace, the social media platform for artists to share, connect, and grow their creative skills.';
$page_css = $page_css ?? 'main';
$page_js = $page_js ?? null;
$body_class = $body_class ?? '';

// Start output buffering to capture the content
if (!isset($content)) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Base CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if ($page_css && $page_css !== 'main'): ?>
    <link href="/assets/css/<?php echo $page_css; ?>.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- CSRF Token for AJAX calls -->
    <script>
        const CSRF_TOKEN = "<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>";
    </script>
</head>
<body class="<?php echo $body_class; ?>">
    <?php
    // Include header for logged-in users
    if (isset($_SESSION['user_id'])) {
        include VIEW_PATH . '/includes/header.php';
    }
    
    // Output the content
    if (!isset($content)) {
        $content = ob_get_clean();
    }
    echo $content;
    
    // Include footer
    include VIEW_PATH . '/includes/footer.php';
    ?>
    
    <!-- Base JavaScript -->
    <script src="/assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if ($page_js): ?>
    <script src="/assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>
    
    <!-- Toast container for notifications -->
    <div id="toastContainer" class="toast-container"></div>
</body>
</html>
<?php
// If we're in a view file, we need to stop execution here
if (isset($stop_execution) && $stop_execution) {
    exit;
}
?>