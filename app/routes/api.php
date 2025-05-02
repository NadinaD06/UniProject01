<?php
/**
 * API Routes
 */

use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Controllers\AdminController;

// Post routes
$router->post('/api/posts/create', [PostController::class, 'create']);
$router->get('/api/posts/feed', [PostController::class, 'feed']);
$router->get('/api/posts/user', [PostController::class, 'userPosts']);
$router->post('/api/posts/like', [PostController::class, 'like']);
$router->post('/api/posts/unlike', [PostController::class, 'unlike']);
$router->post('/api/posts/comment', [PostController::class, 'comment']);
$router->get('/api/posts/{id}/comments', [PostController::class, 'comments']);
$router->post('/api/posts/delete', [PostController::class, 'delete']);

// User routes
$router->post('/api/users/block', [UserController::class, 'block']);
$router->post('/api/users/unblock', [UserController::class, 'unblock']);
$router->post('/api/users/report', [UserController::class, 'report']);
$router->get('/api/users/blocked', [UserController::class, 'blockedUsers']);
$router->get('/api/users/reports', [UserController::class, 'reports']);

// Admin routes
$router->get('/api/admin/stats/posts', [AdminController::class, 'postStats']);
$router->get('/api/admin/stats/reports', [AdminController::class, 'reportStats']);
$router->post('/api/admin/users/delete', [AdminController::class, 'deleteUser']);
$router->post('/api/admin/reports/handle', [AdminController::class, 'handleReport']); 