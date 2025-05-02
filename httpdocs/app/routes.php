<?php
/**
 * Application Routes
 */

// Auth Routes
$router->get('/login', ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/register', ['AuthController', 'showRegister']);
$router->post('/register', ['AuthController', 'register']);
$router->get('/logout', ['AuthController', 'logout']);
$router->get('/forgot-password', ['AuthController', 'showForgotPassword']);
$router->post('/forgot-password', ['AuthController', 'forgotPassword']);
$router->get('/reset-password', ['AuthController', 'showResetPassword']);
$router->post('/reset-password', ['AuthController', 'resetPassword']);

// Post Routes
$router->get('/', ['PostController', 'index']);
$router->get('/posts', ['PostController', 'index']);
$router->post('/posts', ['PostController', 'store']);
$router->get('/posts/{id}', ['PostController', 'show']);
$router->put('/posts/{id}', ['PostController', 'update']);
$router->delete('/posts/{id}', ['PostController', 'delete']);
$router->post('/posts/{id}/like', ['PostController', 'like']);
$router->post('/posts/{id}/comment', ['PostController', 'comment']);

// Profile Routes
$router->get('/profile/{id}', ['ProfileController', 'show']);
$router->get('/profile/edit', ['ProfileController', 'edit']);
$router->post('/profile/update', ['ProfileController', 'update']);

// Message Routes
$router->get('/messages', ['MessageController', 'index']);
$router->get('/messages/{id}', ['MessageController', 'show']);
$router->post('/messages', ['MessageController', 'store']);

// Admin Routes
$router->get('/admin', ['AdminController', 'index']);
$router->get('/admin/users', ['AdminController', 'users']);
$router->get('/admin/reports', ['AdminController', 'reports']);
$router->post('/admin/users/{id}/delete', ['AdminController', 'deleteUser']);
$router->post('/admin/reports/{id}/resolve', ['AdminController', 'resolveReport']);

// API Routes
// Post routes
$router->post('/api/posts/create', ['PostController', 'create']);
$router->get('/api/posts/feed', ['PostController', 'feed']);
$router->get('/api/posts/user', ['PostController', 'userPosts']);
$router->post('/api/posts/like', ['PostController', 'like']);
$router->post('/api/posts/unlike', ['PostController', 'unlike']);
$router->post('/api/posts/comment', ['PostController', 'comment']);
$router->get('/api/posts/{id}/comments', ['PostController', 'comments']);
$router->post('/api/posts/delete', ['PostController', 'delete']);

// User routes
$router->post('/api/users/block', ['UserController', 'block']);
$router->post('/api/users/unblock', ['UserController', 'unblock']);
$router->post('/api/users/report', ['UserController', 'report']);
$router->get('/api/users/blocked', ['UserController', 'blockedUsers']);
$router->get('/api/users/reports', ['UserController', 'reports']);

// Message routes
$router->get('/api/messages/conversations', ['MessageController', 'index']);
$router->get('/api/messages/conversation/{id}', ['MessageController', 'show']);
$router->post('/api/messages/send', ['MessageController', 'send']);
$router->get('/api/messages/unread-count', ['MessageController', 'unreadCount']);

// Notification routes
$router->get('/api/notifications/unread-count', ['NotificationController', 'getUnreadCount']);
$router->post('/api/notifications/mark-as-read', ['NotificationController', 'markAsRead']);
$router->post('/api/notifications/delete-old', ['NotificationController', 'deleteOld']);

// Admin routes
$router->get('/api/admin/stats/posts', ['AdminController', 'postStats']);
$router->get('/api/admin/stats/reports', ['AdminController', 'reportStats']);
$router->post('/api/admin/users/delete', ['AdminController', 'deleteUser']);
$router->post('/api/admin/reports/handle', ['AdminController', 'handleReport']);

// Report routes
$router->post('/reports', ['ReportController', 'create']);
$router->get('/reports', ['ReportController', 'index']);
$router->post('/reports/update', ['ReportController', 'update']);
$router->get('/reports/stats', ['ReportController', 'stats']);
$router->get('/reports/user', ['ReportController', 'userReports']); 