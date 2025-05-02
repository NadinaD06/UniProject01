<?php

namespace App\Services;

class FileUploadService {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct() {
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
    }
    
    /**
     * Upload an image file
     * 
     * @param array $file The uploaded file from $_FILES
     * @param string $type The type of upload (posts, profiles, etc.)
     * @return string|false The URL of the uploaded file or false on failure
     */
    public function uploadImage($file, $type) {
        // Validate file
        if (!$this->validateFile($file)) {
            return false;
        }
        
        // Create directory if it doesn't exist
        $uploadPath = $this->uploadDir . $type . '/';
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadPath . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return the URL path
            return '/uploads/' . $type . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Delete an image file
     * 
     * @param string $url The URL of the file to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteImage($url) {
        // Convert URL to filesystem path
        $path = dirname(__DIR__, 2) . '/public' . $url;
        
        // Check if file exists
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return false;
    }
    
    /**
     * Validate an uploaded file
     * 
     * @param array $file The uploaded file from $_FILES
     * @return bool True if valid, false otherwise
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return false;
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the maximum file size in a human-readable format
     * 
     * @return string
     */
    public function getMaxFileSize() {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->maxFileSize;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Get allowed file types
     * 
     * @return array
     */
    public function getAllowedTypes() {
        return $this->allowedTypes;
    }
} 