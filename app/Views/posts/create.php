<?php
/**
 * Post creation view
 * Allows users to create posts with text, images, and location
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Create Post</h1>

            <form id="createPostForm" class="space-y-6">
                <!-- Post Content -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700">What's on your mind?</label>
                    <textarea
                        id="content"
                        name="content"
                        rows="4"
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    ></textarea>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Add Image</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/*"
                        class="mt-1 block w-full"
                    >
                    <div id="imagePreview" class="mt-2 hidden">
                        <img src="" alt="Preview" class="max-h-48 rounded-lg">
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label for="location-input" class="block text-sm font-medium text-gray-700">Add Location</label>
                    <div class="mt-1 flex space-x-2">
                        <input
                            type="text"
                            id="location-input"
                            class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Search for a location..."
                        >
                        <button
                            type="button"
                            onclick="clearLocation()"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 focus:outline-none"
                        >
                            Clear
                        </button>
                    </div>
                    <div id="map" class="mt-2 h-64 rounded-lg"></div>
                    <input type="hidden" id="location_lat" name="location_lat">
                    <input type="hidden" id="location_lng" name="location_lng">
                    <input type="hidden" id="location_name" name="location_name">
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Load Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Load Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Load Leaflet Control Geocoder -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<!-- Load custom maps script -->
<script src="/assets/js/maps.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    initMap();

    // Handle image preview
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = imagePreview.querySelector('img');

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.classList.add('hidden');
        }
    });

    // Handle form submission
    const form = document.getElementById('createPostForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/api/posts/create', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = '/feed';
            } else {
                alert(data.error || 'Failed to create post');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while creating the post');
        }
    });
});
</script> 