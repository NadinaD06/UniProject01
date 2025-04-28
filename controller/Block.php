<?php
/**
 * Block Controller
 * Handles user blocking operations
 */

require_once '../models/Block.php';
require_once '../models/User.php';
require_once '../controllers/BaseController.php';

class BlockController extends BaseController {
    private $block;
    private $user;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Call parent constructor
        parent::__construct();
        
        // Initialize models
        $this->block = new Block($this->conn);
        $this->user = new User($this->conn);
    }
    
    /**
     * Process block/unblock requests
     */
    public function processRequest() {
        // Require authentication for all block actions
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get action from request
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'block':
                $this->blockUser();
                break;
                
            case 'unblock':
                $this->unblockUser();
                break;
                
            case 'get_blocked':
                $this->getBlockedUsers();
                break;
                
            case 'check_status':
                $this->checkBlockStatus();
                break;
                
            default:
                $this->respondWithError('Invalid action', 400);
                break;
        }
    }
    
    /**
     * Block a user
     */
    private function blockUser() {
        // Validate request method
        if (!$this->validateMethod('POST')) {
            return;
        }
        
        // Get input data
        $data = $this->getInputData();
        
        // Validate required parameters
        if (!$this->validateRequiredParams($data, ['user_id'])) {
            return;
        }
        
        // Get target user ID
        $target_user_id = intval($data['user_id']);
        
        // Cannot block yourself
        if ($target_user_id === $this->current_user_id) {
            $this->respondWithError('You cannot block yourself', 400);
            return;
        }
        
        // Check if target user exists
        if (!$this->user->userExists($target_user_id)) {
            $this->respondWithError('User not found', 404);
            return;
        }
        
        // Block the user
        if ($this->block->blockUser($this->current_user_id, $target_user_id)) {
            // Get user info for the response
            $target_user = $this->user->getUserById($target_user_id);
            
            $this->respondWithSuccess([
                'message' => 'User blocked successfully',
                'blocked_user' => [
                    'id' => $target_user_id,
                    'username' => $target_user['username'] ?? 'Unknown User'
                ]
            ]);
        } else {
            $this->respondWithError('Failed to block user', 500);
        }
    }
    
 $target_user_id
            ]);
        } else {
            $this->respondWithError('Failed to unblock user', 500);
        }
    }
    
    /**
     * Get list of blocked users
     * 
     * @param int $current_user_id Current user ID
     */
    private function getBlockedUsers($current_user_id) {
        // Get pagination parameters
        $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;
        $offset = isset($_GET['offset']) ? max(intval($_GET['offset']), 0) : 0;
        
        // Get blocked users
        $blocked_users = $this->block->getBlockedUsers($current_user_id, $limit, $offset);
        $total_count = $this->block->getBlockedCount($current_user_id);
        
        // Format response
        $this->respondWithSuccess([
            'blocked_users' => $blocked_users,
            'pagination' => [
                'total' => $total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ]);
    }
    
    /**
     * Check block status between current user and another user
     * 
     * @param int $current_user_id Current user ID
     */
    private function checkBlockStatus($current_user_id) {
        // Get target user ID from query parameters
        $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        // Validate target user ID
        if ($target_user_id <= 0) {
            $this->respondWithError('Invalid user ID', 400);
            return;
        }
        
        // Get block status
        $status = $this->block->getBlockStatus($current_user_id, $target_user_id);
        
        $this->respondWithSuccess([
            'status' => $status
        ]);
    }
    
    /**
     * Send success response
     * 
     * @param array $data Response data
     */
    private function respondWithSuccess($data) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code
     */
    private function respondWithError($message, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

// Handle the request if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $controller = new BlockController();
    $controller->processRequest();
}