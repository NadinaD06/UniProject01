/**
 * Google Maps integration for location selection
 * Handles map initialization, location search, and marker placement
 */

let map;
let marker;
let searchBox;
let placesService;

// Initialize the map
function initMap() {
    // Create the map centered at a default location
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 0, lng: 0 },
        zoom: 2,
        mapTypeControl: false,
        streetViewControl: false
    });

    // Create the search box and link it to the UI element
    const input = document.getElementById('location-input');
    searchBox = new google.maps.places.SearchBox(input);

    // Create the places service
    placesService = new google.maps.places.PlacesService(map);

    // Bias the SearchBox results towards current map's viewport
    map.addListener('bounds_changed', () => {
        searchBox.setBounds(map.getBounds());
    });

    // Listen for the event fired when the user selects a prediction
    searchBox.addListener('places_changed', () => {
        const places = searchBox.getPlaces();

        if (places.length === 0) {
            return;
        }

        // Clear out the old markers
        if (marker) {
            marker.setMap(null);
        }

        // For each place, get the location and create a marker
        const place = places[0];
        if (!place.geometry || !place.geometry.location) {
            return;
        }

        // Create a marker for the selected place
        marker = new google.maps.Marker({
            map: map,
            position: place.geometry.location,
            animation: google.maps.Animation.DROP
        });

        // Center the map on the selected location
        map.setCenter(place.geometry.location);
        map.setZoom(15);

        // Update hidden input fields with location data
        document.getElementById('location_lat').value = place.geometry.location.lat();
        document.getElementById('location_lng').value = place.geometry.location.lng();
        document.getElementById('location_name').value = place.name;
    });
}

// Clear the selected location
function clearLocation() {
    if (marker) {
        marker.setMap(null);
        marker = null;
    }
    
    document.getElementById('location-input').value = '';
    document.getElementById('location_lat').value = '';
    document.getElementById('location_lng').value = '';
    document.getElementById('location_name').value = '';
    
    // Reset map to default view
    map.setCenter({ lat: 0, lng: 0 });
    map.setZoom(2);
}

// Display a location on the map
function displayLocation(lat, lng, name) {
    const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
    
    // Center the map on the location
    map.setCenter(position);
    map.setZoom(15);
    
    // Create a marker
    if (marker) {
        marker.setMap(null);
    }
    
    marker = new google.maps.Marker({
        map: map,
        position: position,
        title: name
    });
} 