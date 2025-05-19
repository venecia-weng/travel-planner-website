<?php
/**
 * Itinerary handling functions
 */

/**
 * Renders an "Add to Itinerary" button for different item types (attractions, flights, rooms)
 * 
 * @param string $type Type of item ('attraction', 'flight', 'room')
 * @param int $item_id ID of the item
 * @param string $item_name Name of the item to display in notifications
 * @param string $button_class Additional CSS classes for the button
 * @return string HTML for the itinerary button
 */
function renderItineraryButton($type, $item_id, $item_name, $button_class = 'btn-outline-primary') {
    $html = '<button class="add-to-itinerary-btn btn ' . $button_class . ' w-100" ';
    $html .= 'data-' . $type . '-id="' . $item_id . '" ';
    $html .= 'data-item-name="' . htmlspecialchars($item_name, ENT_QUOTES) . '">';
    $html .= '<i class="fas fa-calendar-plus me-2"></i> Add to Itinerary';
    $html .= '</button>';
    
    return $html;
}

/**
 * Outputs the JavaScript code for handling "Add to Itinerary" buttons
 */
function outputItineraryScript() {
    ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all Add to Itinerary buttons
    const addToItineraryBtns = document.querySelectorAll('.add-to-itinerary-btn');

    if (addToItineraryBtns.length > 0) {
        // Attach click event to each button
        addToItineraryBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the data attributes
                const attractionId = this.getAttribute('data-attraction-id');
                const flightId = this.getAttribute('data-flight-id');
                const roomId = this.getAttribute('data-room-id');
                const itemName = this.getAttribute('data-item-name');
                
                // Build the URL based on the item type
                let url = 'add-to-itinerary.php?ajax=1';
                if (attractionId) {
                    url += '&attraction_id=' + attractionId;
                } else if (flightId) {
                    url += '&flight_id=' + flightId;
                } else if (roomId) {
                    url += '&room_id=' + roomId;
                }
                
                // Always show notification immediately
                showItineraryNotification(itemName);
                
                // Send AJAX request to add to itinerary in the background
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Itinerary response:', data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }

    // Function to show notification
    function showItineraryNotification(itemName) {
        // Create a toast-like notification
        const notification = document.createElement('div');
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.backgroundColor = '#28a745';
        notification.style.color = 'white';
        notification.style.padding = '15px 25px';
        notification.style.borderRadius = '5px';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        notification.style.zIndex = '1000';
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold;">Added to Itinerary</div>
                    <div>${itemName}</div>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <a href="itinerary.php" style="color: white; text-decoration: underline;">View Itinerary</a>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 5000);
    }
});
</script>
    <?php
}
?>