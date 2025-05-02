<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Friend;
use App\Services\FileUploadService;

class ProfileController extends Controller {
    private $userModel;
    private $postModel;
    private $friendModel;
    private $fileUpload;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->postModel = new Post($pdo);
        $this->friendModel = new Friend($pdo);
        $this->fileUpload = new FileUploadService();
    }

    /**
     * Show user profile page
     */
    public function show($username) {
        // Get user data
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return $this->redirect('/404');
        }

        // Get user posts
        $posts = $this->postModel->getUserPosts($user['id']);

        // Get friend status
        $friendStatus = null;
        if ($_SESSION['user_id'] !== $user['id']) {
            $friendStatus = $this->friendModel->getFriendStatus($_SESSION['user_id'], $user['id']);
        }

        // Get mutual friends
        $mutualFriends = $this->friendModel->getMutualFriends($_SESSION['user_id'], $user['id']);

        return $this->view('profile/show', [
            'user' => $user,
            'posts' => $posts,
            'friendStatus' => $friendStatus,
            'mutualFriends' => $mutualFriends
        ]);
    }

    /**
     * Show profile edit page
     */
    public function edit() {
        // Get current user data
        $user = $this->userModel->findById($_SESSION['user_id']);

        return $this->view('profile/edit', [
            'user' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/profile/edit');
        }

        $data = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'bio' => $_POST['bio'] ?? '',
            'location' => $_POST['location'] ?? ''
        ];

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = $this->fileUpload->uploadImage($_FILES['profile_image'], 'profiles');
            if ($imageUrl) {
                $data['profile_image'] = $imageUrl;
            }
        }

        // Update user
        $success = $this->userModel->update($_SESSION['user_id'], $data);

        if ($success) {
            $_SESSION['success'] = 'Profile updated successfully';
        } else {
            $_SESSION['error'] = 'Error updating profile';
        }

        return $this->redirect('/profile/edit');
    }

    /**
     * Update user password
     */
    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/profile/edit');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate passwords
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = 'All password fields are required';
            return $this->redirect('/profile/edit');
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'New passwords do not match';
            return $this->redirect('/profile/edit');
        }

        // Verify current password
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['error'] = 'Current password is incorrect';
            return $this->redirect('/profile/edit');
        }

        // Update password
        $success = $this->userModel->updatePassword($_SESSION['user_id'], $newPassword);

        if ($success) {
            $_SESSION['success'] = 'Password updated successfully';
        } else {
            $_SESSION['error'] = 'Error updating password';
        }

        return $this->redirect('/profile/edit');
    }

    /**
     * Delete user account
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/profile/edit');
        }

        $password = $_POST['password'] ?? '';

        // Verify password
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Password is incorrect';
            return $this->redirect('/profile/edit');
        }

        // Delete user
        $success = $this->userModel->delete($_SESSION['user_id']);

        if ($success) {
            // Destroy session
            session_destroy();
            return $this->redirect('/');
        } else {
            $_SESSION['error'] = 'Error deleting account';
            return $this->redirect('/profile/edit');
        }
    }
} 