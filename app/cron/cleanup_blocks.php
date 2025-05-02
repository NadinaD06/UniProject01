<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Models\Block;
use App\Models\User;

$blockModel = new Block();
$userModel = new User();

// Clean up expired blocks
$blockModel->cleanupExpiredBlocks();

// Get users who were recently unblocked
$sql = "SELECT DISTINCT blocked_id 
        FROM blocks 
        WHERE expires_at < NOW() 
        AND expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
$stmt = $blockModel->db->prepare($sql);
$stmt->execute();
$unblockedUsers = $stmt->fetchAll();

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