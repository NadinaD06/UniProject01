<?php
/**
* app/Core/Router.php
**/

namespace App\Core;

session_start();

// Define base path
define('BASE_PATH', __DIR__);

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load database connection
require_once __DIR__ . '/../get_db_connection.php';

// Load controllers
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/PostController.php';
require_once __DIR__ . '/Controllers/ProfileController.php';
require_once __DIR__ . '/Controllers/SettingsController.php';

// Get the current URL path
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
        require_once __DIR__ . '/Views/404.php';
        break;
}