<?php
/**
 * Web Routes
 * This file defines all routes for the web application
 */

// Home routes
$router->get('/', 'HomeController@index');
$router->get('/about', 'HomeController@about');
$router->get('/terms', 'HomeController@terms');
$router->get('/privacy', 'HomeController@privacy');
$router->get('/help', 'HomeController@help');
$router->get('/contact', 'HomeController@contact');
$router->post('/contact/submit', 'HomeController@submitContact');

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
$router->get('/feed', 'FeedController@index');
$router->post('/feed/load-more', 'FeedController@loadMore');
$router->get('/explore', 'FeedController@explore');
$router->post('/explore/load-more', 'FeedController@loadMoreExplore');
$router->post('/search', 'FeedController@search');
$router->post('/post/view', 'FeedController@viewPost');
$router->post('/post/like', 'FeedController@toggleLike');
$router->post('/post/save', 'FeedController@toggleSave');

// Post routes
$router->get('/post/{id}', 'PostController@show');
$router->get('/create-post', 'PostController@create');
$router->post('/post/store', 'PostController@store');
$router->get('/post/{id}/edit', 'PostController@edit');
$router->post('/post/{id}/update', 'PostController@update');
$router->post('/post/{id}/delete', 'PostController@delete');

// Comment routes
$router->post('/comment/store', 'CommentController@store');
$router->post('/comment/{id}/update', 'CommentController@update');
$router->post('/comment/{id}/delete', 'CommentController@delete');

// User routes
$router->get('/profile', 'UserController@profile');
$router->get('/profile/{username}', 'UserController@show');
$router->get('/settings', 'UserController@settings');
$router->post('/settings/update', 'UserController@updateSettings');
$router->post('/follow', 'UserController@toggleFollow');

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

// Upload routes
$router->post('/upload/profile-image', 'UploadController@uploadProfileImage');
$router->post('/upload/post-image', 'UploadController@uploadPostImage');

// Error routes
$router->get('/404', 'HomeController@notFound');
$router->get('/500', 'HomeController@serverError');
$router->get('/maintenance', 'HomeController@maintenance');

return $router;