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
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->message = new Message($pdo);
        $this->user = new User($pdo);
        
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
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        $conversations = $this->message->getConversations($_SESSION['user_id']);
        
        $this->view('messages/index', [
            'conversations' => $conversations
        ]);
    }
    
    /**
     * Display conversation with specific user
     * 
     * @return string Rendered view
     */
    public function conversation($username) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        $user = $this->user->findByUsername($username);
        if (!$user) {
            $this->redirect('/messages');
        }
        
        $page = $this->getQuery('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $messages = $this->message->getConversation(
            $_SESSION['user_id'],
            $user['id'],
            $limit,
            $offset
        );
        
        // Mark messages as read
        foreach ($messages as $message) {
            if ($message['receiver_id'] == $_SESSION['user_id'] && !$message['read_at']) {
                $this->message->markAsRead($message['id']);
            }
        }
        
        $this->view('messages/conversation', [
            'user' => $user,
            'messages' => $messages,
            'page' => $page,
            'hasMore' => count($messages) === $limit
        ]);
    }
    
    /**
     * Send a message
     * 
     * @return void JSON response
     */
    public function send() {
        if (!$this->isPost()) {
            $this->redirect('/messages');
        }
        
        $receiverId = $this->getPost('receiver_id');
        $content = $this->getPost('content');
        
        if (empty($content)) {
            $this->json([
                'success' => false,
                'message' => 'Message cannot be empty'
            ]);
            return;
        }
        
        $data = [
            'sender_id' => $_SESSION['user_id'],
            'receiver_id' => $receiverId,
            'content' => $content
        ];
        
        if ($this->message->send($data)) {
            $this->json([
                'success' => true,
                'message' => 'Message sent successfully'
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to send message'
            ]);
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
        if (!isset($_SESSION['user_id'])) {
            $this->json(['count' => 0]);
            return;
        }
        
        $count = $this->message->getUnreadCount($_SESSION['user_id']);
        $this->json(['count' => $count]);
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