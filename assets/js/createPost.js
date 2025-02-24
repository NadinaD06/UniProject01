// create-post.js
let map, autocomplete;

document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    initializeImagePreview();
    initializePostForm();
});

function initializeMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: -34.397, lng: 150.644 },
        zoom: 8
    });

    autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('location')
    );

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.geometry) {
            map.setCenter(place.geometry.location);
            map.setZoom(15);
            new google.maps.Marker({
                map,
                position: place.geometry.location
            });
        }
    });
}

function initializeImagePreview() {
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('image-preview');

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            }
            reader.readAsDataURL(file);
        }
    });
}

function initializePostForm() {
    const form = document.getElementById('post-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('content', document.getElementById('content').value);
        formData.append('location', document.getElementById('location').value);
        formData.append('image', document.getElementById('image').files[0]);

        try {
            const response = await fetch('api/create_post.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.href = 'feed.php';
            }
        } catch (error) {
            console.error('Error creating post:', error);
        }
    });
}