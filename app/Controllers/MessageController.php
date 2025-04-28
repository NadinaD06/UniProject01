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
    private $message;
    private $user;
    private $webSocket;
    
    public function __construct() {
        parent::__construct();
        $this->message = new Message();
        $this->user = new User();
        
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
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        
        // Get user conversations
        $conversations = $this->message->getUserConversations($userId);
        
        return $this->view('messages/index', [
            'conversations' => $conversations
        ]);
    }
    
    /**
     * Display conversation with specific user
     * 
     * @return string Rendered view
     */
    public function conversation() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
        
        $userId = $this->auth->id();
        $username = isset($_GET['username']) ? $_GET['username'] : null;
        
        // Validate username
        if (!$username) {
            return $this->redirect('/messages');
        }
        
        // Get other user by username
        $otherUser = $this->user->findBy('username', $username);
        
        if (!$otherUser) {
            $this->setFlashMessage('User not found', 'error');
            return $this->redirect('/messages');
        }
        
        $otherUserId = $otherUser['id'];
        
        // Check if users are blocked
        if ($this->user->isUserBlocked($userId, $otherUserId) || $this->user->isUserBlocked($otherUserId, $userId)) {
            $this->setFlashMessage('You cannot message this user', 'error');
            return $this->redirect('/messages');
        }
        
        // Get messages between users
        $messages = $this->message->getConversation($userId, $otherUserId, 50, 0);
        
        // Mark messages as read
        $this->message->markMessagesAsRead($otherUserId, $userId);
        
        // Get user conversations for sidebar
        $conversations = $this->message->getUserConversations($userId);
        
        return $this->view('messages/conversation', [
            'messages' => $messages,
            'conversations' => $conversations,
            'other_user' => $otherUser
        ]);
    }
    
    /**
     * Send a message
     * 
     * @return void JSON response
     */
    public function send() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        // Get input data
        $data = $this->getInputData();
        $userId = $this->auth->id();
        
        // Validate required fields
        if (!isset($data['receiver_id']) || !isset($data['content']) || empty(trim($data['content']))) {
            return $this->error('Receiver and content are required');
        }
        
        $receiverId = (int)$data['receiver_id'];
        $content = trim($data['content']);
        
        // Validate receiver exists
        $receiver = $this->user->find($receiverId);
        
        if (!$receiver) {
            return $this->error('Receiver not found');
        }
        
        // Check if users are blocked
        if ($this->user->isUserBlocked($userId, $receiverId) || $this->user->isUserBlocked($receiverId, $userId)) {
            return $this->error('You cannot message this user');
        }
        
        // Send the message
        $messageId = $this->message->sendMessage($userId, $receiverId, $content);
        
        if (!$messageId) {
            return $this->error('Failed to send message');
        }
        
        // Get new message details
        $newMessage = $this->message->getMessageById($messageId);
        
        // Format message for response
        $formattedMessage = $this->formatMessage($newMessage);
        
        // Send real-time notification via WebSocket if enabled
        if (isset($this->webSocket)) {
            $this->sendMessageNotification($newMessage);
        }
        
        return $this->success([
            'message' => $formattedMessage
        ]);
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
        $otherUser = $this->user->find($otherUserId);
        
        if (!$otherUser) {
            return $this->error('User not found');
        }
        
        // Get messages
        $messages = $this->message->getConversation($userId, $otherUserId, 20, $offset);
        
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
        $users = $this->message->searchUsers($userId, $query);
        
        return $this->success([
            'users' => $users
        ]);
    }
    
    /**
     * Get unread message count
     * 
     * @return void JSON response
     */
    public function getUnreadCount() {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            return $this->error('Unauthorized', [], 401);
        }
        
        $userId = $this->auth->id();
        
        // Get unread count
        $count = $this->message->getUnreadCount($userId);
        
        return $this->success([
            'count' => $count
        ]);
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
        $sender = $this->user->find($message['sender_id']);
        
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