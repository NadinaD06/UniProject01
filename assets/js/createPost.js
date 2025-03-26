// createPost.js - JavaScript for the create post page
document.addEventListener('DOMContentLoaded', function() {
    // Image upload preview functionality
    const artworkFile = document.getElementById('artworkFile');
    const uploadPreview = document.getElementById('uploadPreview');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImage = document.getElementById('removeImage');

    // Click on the upload area to trigger file input
    uploadPreview.addEventListener('click', function() {
        artworkFile.click();
    });

    // Handle drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadPreview.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadPreview.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadPreview.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadPreview.classList.add('highlight');
    }

    function unhighlight() {
        uploadPreview.classList.remove('highlight');
    }

    uploadPreview.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length) {
            artworkFile.files = files;
            updatePreview(files[0]);
        }
    }

    // Preview image when selected
    artworkFile.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            updatePreview(this.files[0]);
        }
    });

    function updatePreview(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            uploadPreview.style.display = 'none';
            imagePreview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    }

    // Remove image
    removeImage.addEventListener('click', function() {
        artworkFile.value = '';
        previewImg.src = '';
        imagePreview.style.display = 'none';
        uploadPreview.style.display = 'flex';
    });

    // Show/hide AI tools section based on checkbox
    const usedAICheckbox = document.getElementById('usedAI');
    const aiToolsSection = document.getElementById('aiToolsSection');
    
    usedAICheckbox.addEventListener('change', function() {
        aiToolsSection.style.display = this.checked ? 'block' : 'none';
    });

    // Form submission with validation and AJAX
    const form = document.getElementById('createPostForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const title = document.getElementById('artworkTitle').value;
        const category = document.getElementById('artworkCategory').value;
        const artworkFile = document.getElementById('artworkFile').files;
        
        if (!title) {
            alert('Please enter a title for your artwork.');
            return;
        }
        
        if (!category) {
            alert('Please select a category for your artwork.');
            return;
        }
        
        if (!artworkFile || artworkFile.length === 0) {
            alert('Please upload an image of your artwork.');
            return;
        }
        
        // File size validation (max 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if (artworkFile[0].size > maxSize) {
            alert('File size exceeds the maximum limit (10MB).');
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(artworkFile[0].type)) {
            alert('Only JPG, PNG, and GIF files are allowed.');
            return;
        }
        
        // Show loading state
        const submitButton = form.querySelector('.post-btn');
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        // Submit form with AJAX
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and redirect
                alert(data.message);
                window.location.href = 'profile.html'; // Redirect to profile page
            } else {
                // Show error message
                alert('Error: ' + data.message);
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while posting your artwork. Please try again.');
            
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        });
    });
    
    // Character counter for description
    const description = document.getElementById('artworkDescription');
    
    if (description) {
        // Create character counter element
        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.textContent = '0 / 1000 characters';
        
        // Insert after the description textarea
        description.parentNode.insertBefore(counterDiv, description.nextSibling);
        
        // Update counter on input
        description.addEventListener('input', function() {
            const count = this.value.length;
            const maxLength = 1000;
            
            counterDiv.textContent = `${count} / ${maxLength} characters`;
            
            // Visual indicator when approaching limit
            if (count > maxLength * 0.9) {
                counterDiv.classList.add('near-limit');
            } else {
                counterDiv.classList.remove('near-limit');
            }
            
            // Prevent typing more than the limit
            if (count > maxLength) {
                this.value = this.value.substring(0, maxLength);
                counterDiv.textContent = `${maxLength} / ${maxLength} characters`;
                counterDiv.classList.add('limit-reached');
            } else {
                counterDiv.classList.remove('limit-reached');
            }
        });
    }
});