document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const likeBtn = document.getElementById('likeBtn');
    const likeCounter = document.getElementById('likeCounter');
    const saveBtn = document.getElementById('savePostBtn');
    const commentBtn = document.getElementById('commentBtn');
    const commentInput = document.getElementById('commentInput');
    const addCommentForm = document.getElementById('addCommentForm');
    const reportLink = document.getElementById('reportLink');
    const reportModal = document.getElementById('reportModal');
    const reportForm = document.getElementById('reportForm');
    const closeModalBtn = document.querySelector('.modal .close');
    const shareBtn = document.getElementById('shareBtn');
    const copyLinkBtn = document.getElementById('copyLink');
    const followBtn = document.getElementById('followBtn');
    
    // Initialize state
    let isLiked = false;
    let isSaved = false;
    let isFollowing = false;
    
    // Handle like button click
    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            isLiked = !isLiked;
            
            // Toggle heart icon
            const heartIcon = likeBtn.querySelector('i');
            heartIcon.className = isLiked ? 'fas fa-heart' : 'far fa-heart';
            
            // Update like count in the button
            const likeText = likeBtn.textContent.replace(/[0-9]+/, '');
            
            // Get current count from the counter element
            const currentCount = parseInt(likeCounter.querySelector('.count').textContent);
            const newCount = isLiked ? currentCount + 1 : currentCount - 1;
            
            // Update the counter display
            likeCounter.querySelector('.count').textContent = newCount;
            
            // Call API to update like status (simulated)
            updateLikeStatus(isLiked);
        });
    }
    
    // Handle save button click
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            isSaved = !isSaved;
            
            // Toggle bookmark icon
            const saveIcon = saveBtn.querySelector('i');
            saveIcon.className = isSaved ? 'fas fa-bookmark' : 'far fa-bookmark';
            
            // Show toast notification
            showToast(isSaved ? 'Post saved to your collection' : 'Post removed from your collection');
            
            // Call API to update save status (simulated)
            updateSaveStatus(isSaved);
        });
    }
    
    // Focus comment input when comment button is clicked
    if (commentBtn && commentInput) {
        commentBtn.addEventListener('click', function() {
            commentInput.focus();
        });
    }
    
    // Handle comment submission
    if (addCommentForm) {
        addCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (commentInput.value.trim() === '') {
                return;
            }
            
            // Get comment text
            const commentText = commentInput.value.trim();
            
            // Create new comment (in a real app, would be added after API confirmation)
            addNewComment(commentText);
            
            // Clear input
            commentInput.value = '';
        });
    }
    
    // Function to add a new comment to the UI
    function addNewComment(text) {
        // Get comments container
        const commentsContainer = document.querySelector('.comments-container');
        
        // Create new comment element
        const newComment = document.createElement('div');
        newComment.className = 'comment';
        
        // Get current timestamp
        const now = new Date();
        
        // Build comment HTML
        newComment.innerHTML = `
            <img src="/api/placeholder/40/40" alt="Your Avatar" class="comment-avatar">
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-username">You</span>
                    <span class="comment-time">Just now</span>
                </div>
                <p class="comment-text">${text}</p>
                <div class="comment-actions">
                    <button class="comment-action"><i class="far fa-heart"></i> 0</button>
                    <button class="comment-action">Reply</button>
                </div>
            </div>
        `;
        
        // Insert at the top of the comments
        const firstComment = commentsContainer.querySelector('.comment');
        if (firstComment) {
            commentsContainer.insertBefore(newComment, firstComment);
        } else {
            commentsContainer.appendChild(newComment);
        }
        
        // Update comment counter
        updateCommentCount(1);
        
        // Scroll to the new comment
        newComment.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Update comment count in the UI
    function updateCommentCount(increment) {
        const commentCounter = document.getElementById('commentCounter');
        if (commentCounter) {
            const countElement = commentCounter.querySelector('.count');
            const currentCount = parseInt(countElement.textContent);
            countElement.textContent = currentCount + increment;
        }
        
        // Also update the header count
        const commentsHeader = document.querySelector('.comments-section h3');
        if (commentsHeader) {
            const headerCountMatch = commentsHeader.textContent.match(/\(([0-9]+)\)/);
            if (headerCountMatch) {
                const currentHeaderCount = parseInt(headerCountMatch[1]);
                commentsHeader.textContent = commentsHeader.textContent.replace(
                    /\([0-9]+\)/, `(${currentHeaderCount + increment})`
                );
            }
        }
    }
    
    // Handle follow button
    if (followBtn) {
        followBtn.addEventListener('click', function() {
            isFollowing = !isFollowing;
            
            if (isFollowing) {
                followBtn.innerHTML = '<i class="fas fa-user-check"></i> Following';
                followBtn.classList.add('following');
                showToast('You are now following this artist');
            } else {
                followBtn.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                followBtn.classList.remove('following');
                showToast('You unfollowed this artist');
            }
            
            // Call API to update follow status (simulated)
            updateFollowStatus(isFollowing);
        });
    }
    
    // Report modal functionality
    if (reportLink && reportModal) {
        reportLink.addEventListener('click', function(e) {
            e.preventDefault();
            reportModal.style.display = 'block';
        });
    }
    
    // Close modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            reportModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === reportModal) {
            reportModal.style.display = 'none';
        }
    });
    
    // Handle report form submission
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(reportForm);
            const reason = formData.get('reportReason');
            const details = formData.get('reportDetails');
            
            if (!reason) {
                alert('Please select a reason for your report.');
                return;
            }
            
            // Simulate API call to submit report
            submitReport(reason, details)
                .then(() => {
                    reportModal.style.display = 'none';
                    showToast('Your report has been submitted. Thank you for helping keep ArtSpace safe.');
                    reportForm.reset();
                })
                .catch(error => {
                    alert('There was an error submitting your report. Please try again.');
                    console.error('Report submission error:', error);
                });
        });
    }
    
    // Share functionality
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            if (navigator.share) {
                // Use Web Share API if available
                navigator.share({
                    title: document.querySelector('.artwork-title').textContent,
                    text: 'Check out this amazing artwork on ArtSpace!',
                    url: window.location.href
                })
                .then(() => console.log('Shared successfully'))
                .catch(error => console.error('Share error:', error));
            } else {
                // Fallback - show a share dropdown or copy link
                showToast('Share options appear here');
                // In a real implementation, you'd show a custom share dialog
            }
        });
    }
    
    // Copy link functionality
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Copy URL to clipboard
            const tempInput = document.createElement('input');
            tempInput.value = window.location.href;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            showToast('Link copied to clipboard');
        });
    }
    
    // Load more comments functionality
    const loadMoreCommentsBtn = document.querySelector('.load-more-comments');
    if (loadMoreCommentsBtn) {
        loadMoreCommentsBtn.addEventListener('click', function() {
            // In a real app, this would fetch more comments from an API
            // For demo purposes, we'll just show a loading state
            this.textContent = 'Loading...';
            this.disabled = true;
            
            setTimeout(() => {
                // Simulate loading more comments
                loadAdditionalComments();
                this.textContent = 'Load More Comments';
                this.disabled = false;
            }, 1500);
        });
    }
    
    // Function to load additional comments (simulation)
    function loadAdditionalComments() {
        const commentsContainer = document.querySelector('.comments-container');
        
        // Sample comments to add
        const additionalComments = [
            {
                username: 'PaintingPro',
                time: '1 day ago',
                text: 'The lighting in this piece is incredible! How did you achieve that glow effect?',
                likes: 5
            },
            {
                username: 'DigitalDreamer',
                time: '2 days ago',
                text: 'This would make an amazing desktop wallpaper. Are you selling prints?',
                likes: 3
            },
            {
                username: 'ColorTheory101',
                time: '2 days ago',
                text: 'The color palette you chose works perfectly for creating that ethereal mood.',
                likes: 7
            }
        ];
        
        // Add each comment to the container
        additionalComments.forEach(comment => {
            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            commentElement.innerHTML = `
                <img src="/api/placeholder/40/40" alt="User Avatar" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-username">${comment.username}</span>
                        <span class="comment-time">${comment.time}</span>
                    </div>
                    <p class="comment-text">${comment.text}</p>
                    <div class="comment-actions">
                        <button class="comment-action"><i class="far fa-heart"></i> ${comment.likes}</button>
                        <button class="comment-action">Reply</button>
                    </div>
                </div>
            `;
            
            // Add before the "Load More" button
            commentsContainer.insertBefore(commentElement, loadMoreCommentsBtn);
        });
    }
    
    // Simulate API calls (would be real API calls in production)
    function updateLikeStatus(liked) {
        console.log('API call: Update like status to', liked);
        // In a real app, this would be an actual API call
    }
    
    function updateSaveStatus(saved) {
        console.log('API call: Update save status to', saved);
        // In a real app, this would be an actual API call
    }
    
    function updateFollowStatus(following) {
        console.log('API call: Update follow status to', following);
        // In a real app, this would be an actual API call
    }
    
    function submitReport(reason, details) {
        console.log('API call: Submit report', { reason, details });
        // In a real app, this would be an actual API call
        return new Promise(resolve => setTimeout(resolve, 1000));
    }
    
    // Toast notification system
    function showToast(message) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast';
        toastContainer.textContent = message;
        
        document.body.appendChild(toastContainer);
        
        // Show toast
        setTimeout(() => {
            toastContainer.classList.add('show');
        }, 100);
        
        // Hide and remove toast after 3 seconds
        setTimeout(() => {
            toastContainer.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toastContainer);
            }, 300);
        }, 3000);
    }
    
    // Initialize any AI badge visibility
    const aiBadge = document.getElementById('aiBadge');
    const aiToolsDetail = document.getElementById('aiToolsDetail');
    
    // Check if AI was used (in a real app, this would come from the API)
    const aiUsed = true; // This would be determined by the artwork data
    
    if (aiBadge && !aiUsed) {
        aiBadge.style.display = 'none';
    }
    
    if (aiToolsDetail && !aiUsed) {
        aiToolsDetail.style.display = 'none';
    }
});