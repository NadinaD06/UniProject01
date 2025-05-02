<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Models\Block;
use App\Models\User;

$blockModel = new Block();
$userModel = new User();

// Clean up expired blocks
$blockModel->cleanupExpiredBlocks();

// Get users who were recently unblocked
$unblockedUsers = $blockModel->getRecentlyUnblockedUsers();

// Send notifications to unblocked users
foreach ($unblockedUsers as $user) {
    $userData = $userModel->getById($user['blocked_id']);
    if ($userData) {
        $headers = [
            'From' => 'noreply@unisocial.com',
            'Reply-To' => 'noreply@unisocial.com',
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        $message = "Your account has been automatically unblocked. You can now access all features of the platform.";
        
        mail($userData['email'], 'Account Unblocked', $message, $headers);
    }
} 