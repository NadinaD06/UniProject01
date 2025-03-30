<?php
/**
 * Messages Page
 * Handles private messaging between users
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to view your messages';
    header('Location: login.php');
    exit;
}

// Set page info
$page_title = "ArtSpace - Messages";
$page_css = "messages";
$page_js = "messages";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get selected contact from URL if available
$selected_contact = isset($_GET['user']) ? $_GET['user'] : null;

// Get username param for new messages
$new_message_to = isset($_GET['new']) ? $_GET['new'] : null;
?>

<?php require_once '../includes/header.php'; ?>

<main class="messages-container">
    <div class="contacts-panel">
        <div class="contacts-header">
            <h2>Messages</h2>
            <button class="new-message-btn" id="newMessageBtn" title="New Message"><i class="fas fa-edit"></i></button>
        </div>
        <div class="search-messages">
            <i class="fas fa-search"></i>
            <input type="text" id="contactSearch" placeholder="Search messages">
        </div>
        <div class="contacts-list" id="contacts">
            <!-- Contacts will be loaded via JavaScript -->
            <div class="loading-contacts">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading conversations...</p>
            </div>
        </div>
    </div>

    <div class="chat-panel" id="chatPanel">
        <div class="empty-chat-state" id="emptyChatState">
            <div class="empty-chat-icon">
                <i class="fas fa-envelope-open"></i>
            </div>
            <h3>Your Messages</h3>
            <p>Send private messages to other artists and collectors</p>
            <button class="start-message-btn" id="startMessageBtn"><i class="fas fa-edit"></i> New Message</button>
        </div>

        <div class="chat-content" id="chatContent" style="display: none;">
            <div class="chat-header" id="chatHeader">
                <div class="chat-user-info">
                    <img src="/api/placeholder/40/40" alt="User" id="chatUserAvatar">
                    <div>
                        <h3 id="chatUsername">Username</h3>
                        <span class="user-status" id="userStatus">Active now</span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="info-btn" id="infoBtn" title="User info"><i class="fas fa-info-circle"></i></button>
                    <button class="options-btn" id="optionsBtn" title="More options"><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>

            <div class="messages-area" id="messagesArea">
                <!-- Messages will be loaded via JavaScript -->
                <div class="loading-messages" id="loadingMessages">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading messages...</p>
                </div>
            </div>

            <div class="message-composer">
                <form id="messageForm">
                    <div class="message-input-container">
                        <button type="button" class="emoji-btn" id="emojiBtn"><i class="far fa-smile"></i></button>
                        <input type="text" id="messageContent" placeholder="Type a message...">
                        <button type="button" class="attach-btn" id="attachBtn"><i class="fas fa-paperclip"></i></button>
                    </div>
                    <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>

    <div class="user-info-panel" id="userInfoPanel">
        <div class="user-profile">
            <img src="/api/placeholder/100/100" alt="User" id="userProfileImage">
            <h3 id="userProfileName">Username</h3>
            <p class="user-bio" id="userBio">Artist • Illustrator</p>
            <div class="profile-actions">
                <button class="view-profile-btn" id="viewProfileBtn">View Profile</button>
                <button class="block-user-btn" id="blockUserBtn">Block User</button>
            </div>
        </div>

        <div class="shared-media">
            <h4>Shared Media</h4>
            <div class="media-tabs">
                <button class="media-tab active" data-tab="images">Images</button>
                <button class="media-tab" data-tab="files">Files</button>
                <button class="media-tab" data-tab="links">Links</button>
            </div>
            <div class="media-content" id="mediaContent">
                <div class="media-grid" id="mediaGrid">
                    <!-- Shared media will be loaded via JavaScript -->
                </div>
                <div class="no-media-message">
                    <p>No shared media yet</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- New Message Modal -->
<div class="modal" id="newMessageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>New Message</h3>
            <button class="close-modal" id="closeModalBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="search-users">
                <input type="text" id="userSearch" placeholder="Search for a user">
            </div>
            <div class="search-results" id="searchResults">
                <!-- Search results will be loaded via JavaScript -->
                <div class="loading-results" id="loadingResults" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Searching...</p>
                </div>
                <div class="no-results" id="noResults" style="display: none;">
                    <p>No users found matching your search.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Options Dropdown -->
<div class="dropdown-menu" id="chatOptionsMenu" style="display: none;">
    <ul>
        <li><a href="#" id="clearChatBtn">Clear conversation</a></li>
        <li><a href="#" id="reportUserBtn">Report user</a></li>
        <li><a href="#" id="blockFromChatBtn">Block user</a></li>
    </ul>
</div>

<!-- Toast Notifications Container -->
<div id="toastContainer" class="toast-container"></div>

<input type="hidden" id="currentUserId" value="<?php echo $user_id; ?>">
<input type="hidden" id="selectedContact" value="<?php echo htmlspecialchars($selected_contact ?? ''); ?>">
<input type="hidden" id="newMessageTo" value="<?php echo htmlspecialchars($new_message_to ?? ''); ?>">

<script src="../assets/js/messages.js"></script>
<?php require_once '../includes/footer.php'; ?>