<?php
/**
 * Post creation view
 * Allows users to create posts with text, images, and location
 */
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Create New Post</h4>
                </div>
                <div class="card-body">
                    <form action="/posts/create" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="content">Post Content</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Add Image</label>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Add Location</label>
                            <div id="map" style="height: 400px;"></div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('map').setView([51.505, -0.09], 13);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    let marker;
    
    // Handle map click
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Update hidden inputs
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Update marker
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([lat, lng]).addTo(map);
    });
    
    // Try to get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            map.setView([lat, lng], 13);
            
            // Add marker at user's location
            marker = L.marker([lat, lng]).addTo(map);
            
            // Update hidden inputs
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        });
    }
});
</script> 