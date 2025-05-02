<?php
/**
* app/Controllers/MessageController.php
* Controller for direct messaging functionality
**/

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\WebSocketService;

class MessageController extends Controller {
    private $messageModel;
    private $userModel;
    private $webSocket;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->messageModel = new Message($pdo);
        $this->userModel = new User($pdo);
        
        // Initialize WebSocket service if WebSockets are enabled
        if (defined('WEBSOCKET_ENABLED') && WEBSOCKET_ENABLED) {
            $this->webSocket = new WebSocketService();
        }
    }
    
    /**
     * Display messages index page
     * 
     * @return string Rendered view
     */
    public function index() {
        $this->requireLogin();
        
        $page = (int) $this->get('page', 1);
        $perPage = $this->config['pagination']['messages_per_page'];
        $userId = $this->getCurrentUserId();
        
        // Get user's conversations
        $conversations = $this->messageModel->getUserConversations($userId, $page, $perPage);
        
        // Get unread count
        $unreadCount = $this->messageModel->getUnreadCount($userId);
        
        $this->render('messages/index', [
            'conversations' => $conversations,
            'user' => $this->userModel->find($userId),
            'unreadCount' => $unreadCount
        ]);
    }
    
    /**
     * Display conversation with specific user
     * 
     * @return string Rendered view
     */
    public function show($username) {
        $this->requireLogin();
        
        // Get user data
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('/messages');
        }
        
        $page = (int) $this->get('page', 1);
        $perPage = $this->config['pagination']['messages_per_page'];
        $userId = $this->getCurrentUserId();
        
        // Get conversation
        $conversation = $this->messageModel->getConversation($userId, $user['id'], $page, $perPage);
        
        // Mark messages as read
        $this->messageModel->markAsRead($user['id'], $userId);
        
        $this->render('messages/show', [
            'conversation' => $conversation,
            'otherUser' => $user,
            'user' => $this->userModel->find($userId)
        ]);
    }
    
    /**
     * Send a message
     * 
     * @return void JSON response
     */
    public function send() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }
        
        $receiverId = (int) $this->post('receiver_id');
        $content = $this->post('content');
        
        // Validate input
        $errors = $this->validate(
            ['content' => $content],
            ['content' => 'required|max:1000']
        );
        
        if (!empty($errors)) {
            $this->json(['error' => 'Please enter a valid message.'], 400);
        }
        
        // Check if receiver exists
        $receiver = $this->userModel->findById($receiverId);
        if (!$receiver) {
            $this->json(['error' => 'User not found'], 404);
        }
        
        try {
            $messageId = $this->messageModel->sendMessage(
                $this->getCurrentUserId(),
                $receiverId,
                $content
            );
            
            if ($messageId) {
                $this->json([
                    'success' => true,
                    'message' => [
                        'id' => $messageId,
                        'content' => $content,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                $this->json(['error' => 'Failed to send message.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
    
    /**
     * Load more messages for a conversation
     * 
     * @return void JSON response
     */
    public function loadMore() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Validate required fields
        if (!isset($data['other_user_id']) || !isset($data['offset'])) {
            return $this->error('Missing required parameters');
        }
        
        $otherUserId = (int)$data['other_user_id'];
        $offset = (int)$data['offset'];
        
        // Validate other user exists
        $otherUser = $this->userModel->find($otherUserId);
        
        if (!$otherUser) {
            return $this->error('User not found');
        }
        
        // Get messages
        $messages = $this->messageModel->getConversation($userId, $otherUserId, 20, $offset);
        
        // Format messages
        $formattedMessages = [];
        
        foreach ($messages as $message) {
            $formattedMessages[] = $this->formatMessage($message);
        }
        
        return $this->success([
            'messages' => $formattedMessages,
            'has_more' => count($messages) === 20
        ]);
    }
    
    /**
     * Search for users to message
     * 
     * @return void JSON response
     */
    public function searchUsers() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Validate query
        if (!isset($data['q']) || strlen(trim($data['q'])) < 2) {
            return $this->error('Search query too short');
        }
        
        $query = trim($data['q']);
        
        // Search users
        $users = $this->messageModel->searchUsers($userId, $query);
        
        return $this->success([
            'users' => $users
        ]);
    }
    
    /**
     * Get unread message count
     * 
     * @return void JSON response
     */
    public function unreadCount() {
        $this->requireLogin();
        
        try {
            $count = $this->messageModel->getUnreadCount($this->getCurrentUserId());
            $this->json(['count' => $count]);
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
    
    /**
     * Format a message for response
     * 
     * @param array $message Message data
     * @return array Formatted message
     */
    private function formatMessage($message) {
        $userId = $this->auth->id();
        
        return [
            'id' => $message['id'],
            'content' => $message['content'],
            'created_at' => $message['created_at'],
            'formatted_time' => $this->formatTime($message['created_at']),
            'is_mine' => $message['sender_id'] == $userId,
            'is_read' => (bool)$message['is_read']
        ];
    }
    
    /**
     * Format timestamp for display
     * 
     * @param string $timestamp SQL timestamp
     * @return string Formatted time
     */
    private function formatTime($timestamp) {
        $date = new \DateTime($timestamp);
        $now = new \DateTime();
        $diff = $now->diff($date);
        
        if ($diff->d === 0) {
            // Today - show time
            return $date->format('h:i A');
        } elseif ($diff->d === 1) {
            // Yesterday
            return 'Yesterday ' . $date->format('h:i A');
        } elseif ($diff->d < 7) {
            // This week - show day name
            return $date->format('l h:i A'); // Monday, Tuesday, etc.
        } else {
            // Older - show date
            return $date->format('M j, Y h:i A'); // Jan 1, 2023 12:34 PM
        }
    }
    
    /**
     * Send message notification via WebSocket
     * 
     * @param array $message Message data
     * @return bool Success status
     */
    private function sendMessageNotification($message) {
        if (!isset($this->webSocket)) {
            return false;
        }
        
        // Get sender info
        $sender = $this->userModel->find($message['sender_id']);
        
        if (!$sender) {
            return false;
        }
        
        // Prepare message data
        $notificationData = [
            'id' => $message['id'],
            'sender' => [
                'id' => $sender['id'],
                'username' => $sender['username'],
                'profile_picture' => $sender['profile_picture'] ?: '/assets/images/default-avatar.png'
            ],
            'content' => mb_substr($message['content'], 0, 50) . (mb_strlen($message['content']) > 50 ? '...' : ''),
            'created_at' => $message['created_at'],
            'conversation_url' => '/messages/' . $sender['username']
        ];
        
        // Send WebSocket notification
        return $this->webSocket->send(
            'user.' . $message['receiver_id'],
            'message',
            $notificationData
        );
    }
}