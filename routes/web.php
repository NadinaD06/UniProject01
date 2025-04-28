<?php
/**
 * routes/web.php
 * Main routing file for ArtSpace application
 */

use App\Core\Router;

$router = new Router();

// Authentication routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/auth/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/auth/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/auth/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/auth/reset-password', 'AuthController@resetPassword');

// Feed routes
$router->get('/', 'HomeController@index');
$router->get('/feed', 'FeedController@index');
$router->post('/feed/load-more', 'FeedController@loadMore');
$router->get('/explore', 'FeedController@explore');
$router->post('/explore/load-more', 'FeedController@loadMoreExplore');
$router->post('/search', 'FeedController@search');

// Post routes
$router->get('/post/{id}', 'PostController@show');
$router->get('/create-post', 'PostController@create');
$router->post('/post/store', 'PostController@store');
$router->get('/post/{id}/edit', 'PostController@edit');
$router->post('/post/{id}/update', 'PostController@update');
$router->post('/post/{id}/delete', 'PostController@delete');
$router->post('/post/view', 'FeedController@viewPost');
$router->post('/post/like', 'FeedController@toggleLike');
$router->post('/post/save', 'FeedController@toggleSave');

// Comment routes
$router->post('/comment/store', 'CommentController@store');
$router->post('/comment/{id}/update', 'CommentController@update');
$router->post('/comment/{id}/delete', 'CommentController@delete');
$router->post('/comment/like', 'CommentController@toggleLike');
$router->post('/comment/load-more', 'CommentController@loadMore');

// User routes
$router->get('/profile', 'UserController@profile');
$router->get('/profile/{username}', 'UserController@show');
$router->get('/settings', 'UserController@settings');
$router->post('/settings/update-profile', 'UserController@updateProfile');
$router->post('/settings/update-password', 'UserController@updatePassword');
$router->post('/settings/update-privacy', 'UserController@updatePrivacy');
$router->post('/follow', 'UserController@toggleFollow');
$router->get('/followers/{username}', 'UserController@followers');
$router->get('/following/{username}', 'UserController@following');
$router->post('/block', 'UserController@toggleBlock');
$router->get('/blocked-users', 'UserController@blockedUsers');

// Notification routes
$router->get('/notifications', 'NotificationController@index');
$router->post('/notifications/get-unread-count', 'NotificationController@getUnreadCount');
$router->post('/notifications/mark-as-read', 'NotificationController@markAsRead');
$router->post('/notifications/mark-all-as-read', 'NotificationController@markAllAsRead');

// Message routes
$router->get('/messages', 'MessageController@index');
$router->get('/messages/{username}', 'MessageController@conversation');
$router->post('/message/send', 'MessageController@send');
$router->post('/message/load-more', 'MessageController@loadMore');
$router->post('/message/search-users', 'MessageController@searchUsers');
$router->post('/message/get-unread-count', 'MessageController@getUnreadCount');

// Report routes
$router->post('/report', 'ReportController@create');
$router->post('/report/reasons', 'ReportController@getReasons');

// Admin routes
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin/reports', 'AdminController@reports');
$router->post('/admin/report/{id}/update', 'AdminController@updateReport');
$router->get('/admin/users', 'AdminController@users');
$router->post('/admin/user/{id}/update', 'AdminController@updateUser');
$router->get('/admin/posts', 'AdminController@posts');
$router->post('/admin/post/{id}/update', 'AdminController@updatePost');

// Upload routes
$router->post('/upload/profile-image', 'UploadController@uploadProfileImage');
$router->post('/upload/post-image', 'UploadController@uploadPostImage');

// Static pages
$router->get('/about', 'PageController@about');
$router->get('/terms', 'PageController@terms');
$router->get('/privacy', 'PageController@privacy');
$router->get('/help', 'PageController@help');
$router->get('/contact', 'PageController@contact');

return $router;