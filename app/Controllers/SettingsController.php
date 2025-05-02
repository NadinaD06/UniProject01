<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class SettingsController extends Controller {
    private $userModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
    }
    
    /**
     * Display settings page
     */
    public function index() {
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);
        
        $this->render('settings/index', [
            'user' => $user,
            'errors' => []
        ]);
    }
    
    /**
     * Update profile settings
     */
    public function updateProfile() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->redirect('/settings');
        }
        
        $userId = $this->getCurrentUserId();
        $data = [
            'username' => $this->post('username'),
            'email' => $this->post('email'),
            'bio' => $this->post('bio')
        ];
        
        // Validate input
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif ($this->userModel->usernameExists($data['username'], $userId)) {
            $errors['username'] = 'Username is already taken';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($this->userModel->emailExists($data['email'], $userId)) {
            $errors['email'] = 'Email is already taken';
        }
        
        if (strlen($data['bio']) > 500) {
            $errors['bio'] = 'Bio must be less than 500 characters';
        }
        
        if (!empty($errors)) {
            $this->render('settings/index', [
                'user' => $this->userModel->find($userId),
                'errors' => $errors
            ]);
            return;
        }
        
        // Update profile
        if ($this->userModel->update($userId, $data)) {
            $this->setFlash('success', 'Profile updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update profile');
        }
        
        $this->redirect('/settings');
    }
    
    /**
     * Update password
     */
    public function updatePassword() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->redirect('/settings');
        }
        
        $userId = $this->getCurrentUserId();
        $currentPassword = $this->post('current_password');
        $newPassword = $this->post('new_password');
        $confirmPassword = $this->post('confirm_password');
        
        // Validate input
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required';
        } elseif (!$this->userModel->verifyPassword($userId, $currentPassword)) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            $this->render('settings/index', [
                'user' => $this->userModel->find($userId),
                'errors' => $errors
            ]);
            return;
        }
        
        // Update password
        if ($this->userModel->updatePassword($userId, $newPassword)) {
            $this->setFlash('success', 'Password updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update password');
        }
        
        $this->redirect('/settings');
    }
    
    /**
     * Update profile image
     */
    public function updateProfileImage() {
        $this->requireLogin();
        
        if (!$this->post() || !isset($_FILES['profile_image'])) {
            $this->redirect('/settings');
        }
        
        $userId = $this->getCurrentUserId();
        $file = $_FILES['profile_image'];
        
        // Validate file
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['profile_image'] = 'Failed to upload image';
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $errors['profile_image'] = 'Invalid file type. Only JPG, PNG and GIF are allowed';
            } elseif ($file['size'] > $maxSize) {
                $errors['profile_image'] = 'File is too large. Maximum size is 5MB';
            }
        }
        
        if (!empty($errors)) {
            $this->render('settings/index', [
                'user' => $this->userModel->find($userId),
                'errors' => $errors
            ]);
            return;
        }
        
        // Upload image
        $uploadDir = 'public/uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update profile image
            if ($this->userModel->update($userId, ['profile_image' => '/' . $filepath])) {
                $this->setFlash('success', 'Profile image updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update profile image');
            }
        } else {
            $this->setFlash('error', 'Failed to upload image');
        }
        
        $this->redirect('/settings');
    }
    
    /**
     * Delete account
     */
    public function deleteAccount() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->redirect('/settings');
        }
        
        $userId = $this->getCurrentUserId();
        $password = $this->post('password');
        
        // Verify password
        if (!$this->userModel->verifyPassword($userId, $password)) {
            $this->setFlash('error', 'Incorrect password');
            $this->redirect('/settings');
            return;
        }
        
        // Delete account
        if ($this->userModel->delete($userId)) {
            $this->logout();
            $this->redirect('/login');
        } else {
            $this->setFlash('error', 'Failed to delete account');
            $this->redirect('/settings');
        }
    }
} 