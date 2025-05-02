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

// Start session
session_start();

// Load configuration
$config = require_once 'config/config.php';

// Load database connection
require_once 'get_db_connection.php';

// Load router
require_once 'app/router.php';

// Handle routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = substr($request_uri, strlen($base_path));

// Remove query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Initialize controllers
$authController = new AuthController($pdo);
$postController = new PostController($pdo);
$profileController = new ProfileController($pdo);
$settingsController = new SettingsController($pdo);

// Route handling
switch ($path) {
    // Auth routes
    case '/':
    case '/login':
        $authController->login();
        break;
    case '/register':
        $authController->register();
        break;
    case '/logout':
        $authController->logout();
        break;

    // Post routes
    case '/feed':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->index();
        break;
    case '/post/create':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->create();
        break;
    case '/post/like':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->like();
        break;
    case '/post/comment':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $postController->comment();
        break;

    // Profile routes
    case (preg_match('/^\/profile\/([^\/]+)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $profileController->show($matches[1]);
        break;

    // Settings routes
    case '/settings':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->index();
        break;
    case '/settings/update-profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->updateProfile();
        break;
    case '/settings/update-account':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $settingsController->updateAccount();
        break;

    // 404 - Not Found
    default:
        header("HTTP/1.0 404 Not Found");
        require_once 'app/Views/404.php';
        break;
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
<body class="<?php echo $body_class ?>">