<?php
/**
 * Storage Service
 * app/Services/StorageService.php
 */ 
namespace App\Services;

class StorageService {
    /**
     * Get storage path
     * 
     * @param string $path Path relative to storage directory
     * @return string Absolute path
     */
    public function path($path = '') {
        return __DIR__ . '/../../storage/' . trim($path, '/');
    }
    
    /**
     * Get public storage path
     * 
     * @param string $path Path relative to public storage directory
     * @return string Absolute path
     */
    public function publicPath($path = '') {
        return $this->path('app/public/' . trim($path, '/'));
    }
    
    /**
     * Check if file exists
     * 
     * @param string $path Path to file
     * @return bool True if file exists
     */
    public function exists($path) {
        return file_exists($this->path($path));
    }
    
    /**
     * Create directory if it doesn't exist
     * 
     * @param string $path Path to directory
     * @return bool True if directory exists or was created
     */
    public function makeDirectory($path) {
        $path = $this->path($path);
        
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        
        return true;
    }
    
    /**
     * Store uploaded file
     * 
     * @param array $file Uploaded file ($_FILES array item)
     * @param string $path Storage path
     * @param string $filename Custom filename (optional)
     * @return string|bool Stored file path or false on failure
     */
    public function storeUploadedFile($file, $path, $filename = null) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Generate filename if not provided
        if ($filename === null) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
        }
        
        // Create directory if it doesn't exist
        $this->makeDirectory($path);
        
        // Generate full path
        $fullPath = $this->path($path . '/' . $filename);
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return $path . '/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Store uploaded image with validation
     * 
     * @param array $file Uploaded file ($_FILES array item)
     * @param string $path Storage path
     * @param array $options Options (width, height, quality)
     * @return string|bool Stored file path or false on failure
     */
    public function storeImage($file, $path, $options = []) {
        // Validate image file
        if (!$this->isValidImage($file)) {
            return false;
        }
        
        // Generate filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        
        // Process image if needed
        if (!empty($options)) {
            $processedPath = $this->processImage($file['tmp_name'], $options);
            
            if ($processedPath) {
                // Create directory if it doesn't exist
                $this->makeDirectory($path);
                
                // Generate full path
                $fullPath = $this->path($path . '/' . $filename);
                
                // Move processed image
                if (rename($processedPath, $fullPath)) {
                    return $path . '/' . $filename;
                }
            }
            
            return false;
        }
        
        // Store original image
        return $this->storeUploadedFile($file, $path, $filename);
    }
    
    /**
     * Delete file
     * 
     * @param string $path Path to file
     * @return bool True if file was deleted
     */
    public function delete($path) {
        $fullPath = $this->path($path);
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Check if file is a valid image
     * 
     * @param array $file Uploaded file ($_FILES array item)
     * @return bool True if file is a valid image
     */
    protected function isValidImage($file) {
        // Check if file is uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check MIME type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowedTypes)) {
            return false;
        }
        
        // Verify it's a valid image
        $imageInfo = getimagesize($file['tmp_name']);
        
        if ($imageInfo === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process image (resize, crop, etc.)
     * 
     * @param string $path Path to source image
     * @param array $options Processing options
     * @return string|bool Path to processed image or false on failure
     */
    protected function processImage($path, $options) {
        // Get image info
        $imageInfo = getimagesize($path);
        
        if ($imageInfo === false) {
            return false;
        }
        
        // Create image resource based on type
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($path);
                break;
            default:
                return false;
        }
        
        if ($image === false) {
            return false;
        }
        
        // Get original dimensions
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        
        // Set target dimensions
        $dstWidth = $options['width'] ?? $srcWidth;
        $dstHeight = $options['height'] ?? $srcHeight;
        
        // Create new image
        $newImage = imagecreatetruecolor($dstWidth, $dstHeight);
        
        // Handle transparency for PNG
        if ($imageInfo[2] === IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $dstWidth, $dstHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        
        // Create temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'img');
        
        // Save image based on type
        $quality = $options['quality'] ?? 90;
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $tempPath, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $tempPath, min(floor($quality / 10), 9));
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $tempPath);
                break;
            default:
                imagedestroy($image);
                imagedestroy($newImage);
                return false;
        }
        
        // Free memory
        imagedestroy($image);
        imagedestroy($newImage);
        
        return $tempPath;
    }
}