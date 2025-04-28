document.addEventListener('DOMContentLoaded', function() {
    // Image upload preview functionality
    const artworkFile = document.getElementById('artworkFile');
    const uploadPreview = document.getElementById('uploadPreview');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImage = document.getElementById('removeImage');

    // Elements for form validation
    const form = document.getElementById('createPostForm');
    const titleInput = document.getElementById('artworkTitle');
    const descriptionInput = document.getElementById('artworkDescription');
    const categorySelect = document.getElementById('artworkCategory');
    const tagsInput = document.getElementById('artworkTags');
    const usedAICheckbox = document.getElementById('usedAI');
    const aiToolsInput = document.getElementById('aiTools');
    const aiToolsSection = document.getElementById('aiToolsSection');
    const submitButton = form.querySelector('.post-btn');

    // Validation feedback elements - will be created dynamically
    let feedbackElements = {};

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
            handleFileValidation(files[0]);
            updatePreview(files[0]);
        }
    }

    // Preview image when selected
    artworkFile.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            handleFileValidation(this.files[0]);
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
        
        // Clear file validation feedback
        clearValidationFeedback('artworkFile');
    });

    // Show/hide AI tools section based on checkbox
    usedAICheckbox.addEventListener('change', function() {
        aiToolsSection.style.display = this.checked ? 'block' : 'none';
        
        // Clear AI tools validation if not used
        if (!this.checked && feedbackElements['aiTools']) {
            clearValidationFeedback('aiTools');
        }
    });

    // Live validation for title
    titleInput.addEventListener('input', function() {
        validateTitle();
    });

    // Live validation for description (length check)
    descriptionInput.addEventListener('input', function() {
        validateDescription();
    });

    // Validate tags format
    tagsInput.addEventListener('input', function() {
        validateTags();
    });

    // Validate AI tools if AI checkbox is checked
    aiToolsInput.addEventListener('input', function() {
        if (usedAICheckbox.checked) {
            validateAITools();
        }
    });

    // Character counter for description
    if (descriptionInput) {
        // Create character counter element if it doesn't exist
        let counterDiv = descriptionInput.parentNode.querySelector('.char-counter');
        if (!counterDiv) {
            counterDiv = document.createElement('div');
            counterDiv.className = 'char-counter';
            counterDiv.textContent = '0 / 1000 characters';
            descriptionInput.parentNode.insertBefore(counterDiv, descriptionInput.nextSibling);
        }
        
        // Update counter on input
        descriptionInput.addEventListener('input', function() {
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

    // Form submission with validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        const isValid = validateForm();
        
        if (isValid) {
            // Show loading state
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
                    showMessage('success', data.message || 'Your artwork has been posted successfully!');
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'profile.html';
                    }, 1500);
                } else {
                    // Show error message
                    showMessage('error', data.message || 'An error occurred. Please try again.');
                    
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('error', 'An error occurred while posting your artwork. Please try again.');
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        }
    });

    // Validate the entire form
    function validateForm() {
        let isValid = true;
        
        // Validate title
        if (!validateTitle()) isValid = false;
        
        // Validate category
        if (!validateCategory()) isValid = false;
        
        // Validate image
        if (!validateImage()) isValid = false;
        
        // Validate description (optional but with limits)
        if (!validateDescription()) isValid = false;
        
        // Validate tags (optional but with format)
        if (!validateTags()) isValid = false;
        
        // Validate AI tools if AI checkbox is checked
        if (usedAICheckbox.checked && !validateAITools()) isValid = false;
        
        // Scroll to the first error
        if (!isValid) {
            const firstError = document.querySelector('.validation-feedback.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }

    // Validate title
    function validateTitle() {
        const value = titleInput.value.trim();
        
        if (!value) {
            showValidationFeedback('artworkTitle', 'error', 'Please enter a title for your artwork.');
            return false;
        } else if (value.length < 3) {
            showValidationFeedback('artworkTitle', 'error', 'Title should be at least 3 characters long.');
            return false;
        } else if (value.length > 100) {
            showValidationFeedback('artworkTitle', 'error', 'Title should be no more than 100 characters long.');
            return false;
        } else {
            showValidationFeedback('artworkTitle', 'success', 'Title looks good!');
            return true;
        }
    }

    // Validate category
    function validateCategory() {
        const value = categorySelect.value;
        
        if (!value) {
            showValidationFeedback('artworkCategory', 'error', 'Please select a category for your artwork.');
            return false;
        } else {
            showValidationFeedback('artworkCategory', 'success', 'Category selected!');
            return true;
        }
    }

    // Validate image
    function validateImage() {
        if (!artworkFile.files || artworkFile.files.length === 0) {
            showValidationFeedback('artworkFile', 'error', 'Please upload an image of your artwork.');
            return false;
        } else {
            return true; // Further validation for file type and size is handled by handleFileValidation
        }
    }

    // Validate file when selected
    function handleFileValidation(file) {
        // File size validation (max 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if (file.size > maxSize) {
            showValidationFeedback('artworkFile', 'error', 'File size exceeds the maximum limit (10MB).');
            return false;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showValidationFeedback('artworkFile', 'error', 'Only JPG, PNG, and GIF files are allowed.');
            return false;
        }
        
        showValidationFeedback('artworkFile', 'success', 'Image uploaded successfully!');
        return true;
    }

    // Validate description (optional but with length limits)
    function validateDescription() {
        const value = descriptionInput.value.trim();
        
        if (value && value.length > 1000) {
            showValidationFeedback('artworkDescription', 'error', 'Description must be less than 1000 characters.');
            return false;
        } else {
            clearValidationFeedback('artworkDescription');
            return true;
        }
    }

    // Validate tags (optional but with format)
    function validateTags() {
        const value = tagsInput.value.trim();
        
        if (!value) {
            clearValidationFeedback('artworkTags');
            return true;
        }
        
        // Check for proper comma separation
        const tagsArray = value.split(',');
        
        // Check if any tag is too long (over 30 characters)
        const longTags = tagsArray.filter(tag => tag.trim().length > 30);
        if (longTags.length > 0) {
            showValidationFeedback('artworkTags', 'error', 'Tags should be less than 30 characters each.');
            return false;
        }
        
        // Check if there are too many tags (more than 10)
        if (tagsArray.length > 10) {
            showValidationFeedback('artworkTags', 'error', 'Please use a maximum of 10 tags.');
            return false;
        }
        
        showValidationFeedback('artworkTags', 'success', 'Tags look good!');
        return true;
    }

    // Validate AI tools if AI checkbox is checked
    function validateAITools() {
        if (!usedAICheckbox.checked) {
            clearValidationFeedback('aiTools');
            return true;
        }
        
        const value = aiToolsInput.value.trim();
        
        if (!value) {
            showValidationFeedback('aiTools', 'error', 'Please specify which AI tools you used.');
            return false;
        } else {
            showValidationFeedback('aiTools', 'success', 'AI tools specified!');
            return true;
        }
    }

    // Show validation feedback
    function showValidationFeedback(elementId, type, message) {
        clearValidationFeedback(elementId);
        
        const element = document.getElementById(elementId);
        const feedbackElement = document.createElement('div');
        feedbackElement.className = `validation-feedback ${type}`;
        feedbackElement.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
        
        element.parentNode.appendChild(feedbackElement);
        
        if (type === 'error') {
            element.classList.add('input-error');
        } else {
            element.classList.remove('input-error');
        }
        
        // Store reference to feedback element
        feedbackElements[elementId] = feedbackElement;
    }

    // Clear validation feedback
    function clearValidationFeedback(elementId) {
        if (feedbackElements[elementId]) {
            feedbackElements[elementId].remove();
            delete feedbackElements[elementId];
            
            const element = document.getElementById(elementId);
            element.classList.remove('input-error');
        }
    }

    // Show toast message
    function showMessage(type, message) {
        // Check if message container exists, if not create one
        let messageContainer = document.querySelector('.message-container');
        
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.querySelector('.create-post-card').prepend(messageContainer);
        }
        
        // Clear existing messages
        messageContainer.innerHTML = '';
        
        // Create new message
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            <span>${message}</span>
            <button class="close-message"><i class="fas fa-times"></i></button>
        `;
        
        // Add to container
        messageContainer.appendChild(messageElement);
        
        // Auto hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                messageElement.classList.add('fade-out');
                setTimeout(() => {
                    messageElement.remove();
                }, 300);
            }, 5000);
        }
        
        // Add close button functionality
        const closeButton = messageElement.querySelector('.close-message');
        closeButton.addEventListener('click', () => {
            messageElement.classList.add('fade-out');
            setTimeout(() => {
                messageElement.remove();
            }, 300);
        });
        
        // Scroll to top to show message
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
});