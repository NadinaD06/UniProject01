<?php
/**
 * Upload Controller
 * app/Controllers/UploadController.php
 */ 
namespace App\Controllers;

use App\Core\Controller;
use App\Services\StorageService;

class UploadController extends Controller {
    private $storage;
    
    public function __construct() {
        parent::__construct();
        $this->storage = new StorageService();
    }
    
    public function uploadProfileImage() {
        // Validate request
        if (!isset($_FILES['image'])) {
            return $this->error('No image uploaded', [], 400);
        }
        
        // Validate image
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error('Error uploading image', [], 400);
        }
        
        // Set max file size (5MB)
        $maxFileSize = 5 * 1024 * 1024;
        
        if ($file['size'] > $maxFileSize) {
            return $this->error('Image size exceeds the limit (5MB)', [], 400);
        }
        
        // Store image
        $path = $this->storage->storeImage($file, 'app/public/profiles', [
            'width' => 300,
            'height' => 300
        ]);
        
        if (!$path) {
            return $this->error('Error processing image', [], 500);
        }
        
        // Update user profile image
        $userId = $this->auth->id();
        
        $user = new \App\Models\User();
        $user->update($userId, ['profile_picture' => $path]);
        
        // Return success response
        return $this->success([
            'path' => $path,
            'url' => '/storage/' . $path
        ], 'Profile image updated successfully');
    }
    
    public function uploadPostImage() {
        // Validate request
        if (!isset($_FILES['image'])) {
            return $this->error('No image uploaded', [], 400);
        }
        
        // Validate image
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error('Error uploading image', [], 400);
        }
        
        // Set max file size (10MB)
        $maxFileSize = 10 * 1024 * 1024;
        
        if ($file['size'] > $maxFileSize) {
            return $this->error('Image size exceeds the limit (10MB)', [], 400);
        }
        
        // Store image
        $path = $this->storage->storeImage($file, 'app/public/posts', [
            'width' => 1200,
            'height' => 1200,
            'quality' => 85
        ]);
        
        if (!$path) {
            return $this->error('Error processing image', [], 500);
        }
        
        // Return success response
        return $this->success([
            'path' => $path,
            'url' => '/storage/' . $path
        ], 'Image uploaded successfully');
    }
}