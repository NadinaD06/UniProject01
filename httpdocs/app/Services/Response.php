<?php
namespace App\Services;

class Response {
    /**
     * Redirect to a URL
     */
    public function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Send JSON response
     */
    public function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send error response
     */
    public function error($message, $data = [], $status = 400) {
        return $this->json([
            'error' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
    
    /**
     * Send success response
     */
    public function success($data = [], $message = 'Success') {
        return $this->json([
            'error' => false,
            'message' => $message,
            'data' => $data
        ]);
    }
} 