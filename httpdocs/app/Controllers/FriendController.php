<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Friend;
use App\Models\User;
use App\Models\Notification;

class FriendController extends Controller {
    private $friendModel;
    private $userModel;
    private $notificationModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->friendModel = new Friend($pdo);
        $this->userModel = new User($pdo);
        $this->notificationModel = new Notification($pdo);
    }
    
    /**
     * Display friends page
     */
    public function index() {
        $this->requireLogin();
        
        $userId = $this->getCurrentUserId();
        $page = (int) $this->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Get friends
        $friends = $this->friendModel->getFriends($userId, $perPage, $offset);
        
        // Get friend requests
        $requests = $this->friendModel->getRequests($userId);
        
        $this->render('friends/index', [
            'friends' => $friends,
            'requests' => $requests,
            'user' => $this->userModel->find($userId)
        ]);
    }
    
    /**
     * Send a friend request
     */
    public function sendRequest() {
        $this->requireLogin();
        
        if (!$this->post()) {
            $this->json(['error' => 'Invalid request.'], 400);
        }
        
        $receiverId = (int) $this->post('user_id');
        
        // Check if receiver exists
        $receiver = $this->userModel->find($receiverId);
        if (!$receiver) {
            $this->json(['error' => 'User not found.'], 404);
        }
        
        // Check if already friends
        if ($this->friendModel->areFriends($this->getCurrentUserId(), $receiverId)) {
            $this->json(['error' => 'Already friends.'], 400);
        }
        
        try {
            $success = $this->friendModel->sendRequest($this->getCurrentUserId(), $receiverId);
            
            if ($success) {
                // Create notification
                $this->notificationModel->create($receiverId, 'friend_request', [
                    'username' => $this->userModel->find($this->getCurrentUserId())['username']
                ]);
                
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Friend request already sent.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
    
    /**
     * Accept a friend request
     */
    public function acceptRequest($requestId) {
        $this->requireLogin();
        
        try {
            $success = $this->friendModel->acceptRequest($requestId, $this->getCurrentUserId());
            
            if ($success) {
                // Get request details
                $request = $this->friendModel->getRequests($this->getCurrentUserId(), 'accepted')[0];
                
                // Create notification
                $this->notificationModel->create($request['sender_id'], 'friend_accept', [
                    'username' => $this->userModel->find($this->getCurrentUserId())['username']
                ]);
                
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Invalid request.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
    
    /**
     * Reject a friend request
     */
    public function rejectRequest($requestId) {
        $this->requireLogin();
        
        try {
            $success = $this->friendModel->rejectRequest($requestId, $this->getCurrentUserId());
            
            if ($success) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Invalid request.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
    
    /**
     * Remove a friend
     */
    public function removeFriend($friendId) {
        $this->requireLogin();
        
        try {
            $success = $this->friendModel->removeFriend($this->getCurrentUserId(), $friendId);
            
            if ($success) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Failed to remove friend.'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'An error occurred.'], 500);
        }
    }
} 