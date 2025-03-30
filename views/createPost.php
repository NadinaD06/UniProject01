<?php
/**
 * Create Post Page
 * Allows users to upload and share their artwork
 */

// Start session
session_start();

// Include required files
require_once '../includes/utilities.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in to create a post';
    header('Location: login.php');
    exit;
}

// Set page info
$page_title = "ArtSpace - Create Post";
$page_css = "create_post";
$page_js = "createPost";

// Get user ID
$user_id = $_SESSION['user_id'];

// Get error messages if any
$error_message = $_SESSION['post_error'] ?? null;
unset($_SESSION['post_error']);
?>

<?php require_once '../includes/header.php'; ?>

<div class="create-post-container">
    <div class="create-post-card">
        <h2>Share Your Artwork</h2>
        <p class="subtitle">Upload your latest creation and share it with the ArtSpace community</p>
        
        <?php if ($error_message): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <form id="createPostForm" action="../controllers/posts/createPost_process.php" method="POST" enctype="multipart/form-data">
            <!-- Add CSRF token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="upload-section">
                <div id="uploadPreview" class="upload-preview">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop your artwork here</p>
                    <p class="upload-hint">or click to browse</p>
                </div>
                <div id="imagePreview" class="image-preview" style="display: none;">
                    <img id="previewImg" src="" alt="Artwork Preview">
                    <button type="button" id="removeImage" class="remove-image">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <input type="file" id="artworkFile" name="artwork_image" class="file-input" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="artworkTitle">Title *</label>
                <input type="text" id="artworkTitle" name="title" placeholder="Give your artwork a title" required>
            </div>
            
            <div class="form-group">
                <label for="artworkDescription">Description</label>
                <textarea id="artworkDescription" name="description" rows="4" placeholder="Tell us about your artwork..."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="artworkCategory">Category *</label>
                    <select id="artworkCategory" name="category" required>
                        <option value="">Select a category</option>
                        <option value="digital-art">Digital Art</option>
                        <option value="traditional">Traditional</option>
                        <option value="photography">Photography</option>
                        <option value="3d-art">3D Art</option>
                        <option value="illustration">Illustration</option>
                        <option value="animation">Animation</option>
                        <option value="concept-art">Concept Art</option>
                        <option value="character-design">Character Design</option>
                    </select>
                </div>
                
                <div class="form-group half">
                    <label for="artworkTags">Tags</label>
                    <input type="text" id="artworkTags" name="tags" placeholder="Add tags separated by commas">
                </div>
            </div>
            
            <div class="form-group">
                <div class="toggle-container">
                    <label for="usedAI" class="toggle-label">Used AI Tools?</label>
                    <label class="switch">
                        <input type="checkbox" id="usedAI" name="used_ai">
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            
            <div id="aiToolsSection" class="form-group" style="display: none;">
                <label for="aiTools">AI Tools Used</label>
                <input type="text" id="aiTools" name="ai_tools" placeholder="e.g. Midjourney, DALL-E, Stable Diffusion">
            </div>
            
            <div class="form-group">
                <div class="toggle-container">
                    <label for="commentsEnabled" class="toggle-label">Enable Comments</label>
                    <label class="switch">
                        <input type="checkbox" id="commentsEnabled" name="comments_enabled" checked>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="toggle-container">
                    <label for="nsfw" class="toggle-label">NSFW Content</label>
                    <label class="switch">
                        <input type="checkbox" id="nsfw" name="nsfw">
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="cancel-btn" onclick="window.location.href='feed.php'">Cancel</button>
                <button type="submit" class="post-btn">Share Artwork</button>
            </div>
        </form>
    </div>
    
    <div class="post-guidelines">
        <h3>Posting Guidelines</h3>
        <ul>
            <li>Only share original content or credit the original artist</li>
            <li>Supported formats: JPG, PNG, GIF (max. 10MB)</li>
            <li>Be honest about AI-assisted artwork</li>
            <li>Tag NSFW content appropriately</li>
            <li>Be respectful of others' work and feedback</li>
            <li>Use descriptive titles and tags to reach the right audience</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>