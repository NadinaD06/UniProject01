<?php
/**
 * New Message Page
 * Create a new private message to another user
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';
require_once '../controllers/users_controller.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to send messages';
    header('Location: login.php');
    exit;
}

// Set page info
$page_title = "ArtSpace - New Message";
$page_css = "new_message";
$page_js = "new_message";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get recipient username from URL if provided
$recipient = isset($_GET['to']) ? $_GET['to'] : null;
$recipient_data = null;

// If recipient is provided, get their data
if ($recipient) {
    $recipient_data = get_user_by_username($recipient);
    
    // If recipient doesn't exist, unset it
    if (!$recipient_data) {
        $recipient = null;
    }
}

// Get trending creators for suggestions
$trending_creators = get_trending_creators(5);

// Get recently interacted users
$recent_users = get_recent_interactions($user_id, 5);

?>

<?php require_once '../includes/header.php'; ?>

<main class="new-message-container">
    <div class="page-header">
        <div class="header-content">
            <h1>New Message</h1>
            <p>Send a private message to another creator</p>
        </div>
    </div>
    
    <div class="message-content-container">
        <div class="message-form-container">
            <form id="newMessageForm" class="message-form">
                <div class="form-group recipient-group">
                    <label for="recipient">To:</label>
                    <div class="recipient-input-container">
                        <?php if ($recipient_data): ?>
                            <div class="selected-recipient" data-id="<?php echo $recipient_data['id']; ?>">
                                <img src="<?php echo !empty($recipient_data['profile_picture']) ? htmlspecialchars($recipient_data['profile_picture']) : '/api/placeholder/32/32'; ?>" alt="<?php echo htmlspecialchars($recipient_data['username']); ?>" class="recipient-avatar">
                                <span class="recipient-username"><?php echo htmlspecialchars($recipient_data['username']); ?></span>
                                <button type="button" class="remove-recipient"><i class="fas fa-times"></i></button>
                            </div>
                            <input type="hidden" name="recipient_id" id="recipientId" value="<?php echo $recipient_data['id']; ?>">
                        <?php else: ?>
                            <input type="text" id="recipient" name="recipient" placeholder="Search for a user..." autocomplete="off">
                            <input type="hidden" name="recipient_id" id="recipientId">
                            <div class="recipient-suggestions" id="recipientSuggestions"></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="messageContent">Message:</label>
                    <textarea id="messageContent" name="message_content" rows="6" placeholder="Type your message here..."></textarea>
                </div>
                
                <div class="message-options">
                    <div class="message-attachments">
                        <button type="button" class="attachment-btn" title="Add attachment"><i class="fas fa-paperclip"></i></button>
                        <button type="button" class="emoji-btn" title="Add emoji"><i class="far fa-smile"></i></button>
                    </div>
                    <div class="message-actions">
                        <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
                        <button type="submit" class="send-btn" id="sendBtn">Send Message</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="message-sidebar">
            <div class="sidebar-section">
                <h3>Trending Creators</h3>
                <div class="user-suggestions">
                    <?php if (!empty($trending_creators)): ?>
                        <?php foreach ($trending_creators as $creator): ?>
                            <div class="user-suggestion" data-id="<?php echo $creator['id']; ?>" data-username="<?php echo htmlspecialchars($creator['username']); ?>">
                                <img src="<?php echo !empty($creator['profile_picture']) ? htmlspecialchars($creator['profile_picture']) : '/api/placeholder/40/40'; ?>" alt="<?php echo htmlspecialchars($creator['username']); ?>" class="user-avatar">
                                <div class="user-info">
                                    <span class="user-name"><?php echo htmlspecialchars($creator['username']); ?></span>
                                    <span class="user-meta"><?php echo number_format($creator['followers_count']); ?> followers</span>
                                </div>
                                <button type="button" class="select-user-btn"><i class="fas fa-plus"></i></button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-suggestions">No trending creators found</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3>Recent Interactions</h3>
                <div class="user-suggestions">
                    <?php if (!empty($recent_users)): ?>
                        <?php foreach ($recent_users as $user): ?>
                            <div class="user-suggestion" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/api/placeholder/40/40'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                                <div class="user-info">
                                    <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="user-meta"><?php echo htmlspecialchars($user['last_interaction']); ?></span>
                                </div>
                                <button type="button" class="select-user-btn"><i class="fas fa-plus"></i></button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-suggestions">No recent interactions</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3>Tips</h3>
                <div class="tips-list">
                    <div class="tip">
                        <i class="fas fa-info-circle"></i>
                        <p>Be respectful and follow our community guidelines when messaging other creators.</p>
                    </div>
                    <div class="tip">
                        <i class="fas fa-star"></i>
                        <p>You can send images and files by clicking the attachment button.</p>
                    </div>
                    <div class="tip">
                        <i class="fas fa-shield-alt"></i>
                        <p>Report any inappropriate messages to our support team.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script src="../assets/js/new_message.js"></script>
<?php require_once '../includes/footer.php'; ?>