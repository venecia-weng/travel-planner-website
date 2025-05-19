<?php
/**
 * Favorites handling functions
 */

/**
 * Check if an item is in user's favorites
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $type Type of item ('hotel', 'destination', 'attraction')
 * @param int $item_id ID of the item
 * @return bool True if item is in favorites, false otherwise
 */
function isInFavorites($conn, $user_id, $type, $item_id) {
    if (!$user_id) return false;
    
    $column = $type . '_id';
    $sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND $column = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_favorite = ($result->num_rows > 0);
    $stmt->close();
    
    return $is_favorite;
}

/**
 * Renders a favorite button with the correct state
 * 
 * @param string $type Type of item ('hotel', 'destination', 'attraction')
 * @param int $item_id ID of the item
 * @param bool $is_favorite Whether item is already in favorites
 * @param string $button_class Additional CSS classes for the button
 * @param string $size Size of the button ('sm', 'md', 'lg')
 * @param bool $show_text Whether to show text next to the icon
 * @return string HTML for the favorite button
 */
function renderFavoriteButton($type, $item_id, $is_favorite, $button_class = '', $size = 'sm', $show_text = false) {
    $icon_class = $is_favorite ? 'fas text-danger' : 'far';
    $button_size = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
    $text = $show_text ? ($is_favorite ? ' Remove from Favorites' : ' Add to Favorites') : '';
    
    $html = '<button class="favorite-btn btn ' . $button_class . ' ' . $button_size . '" ';
    $html .= 'data-' . $type . '-id="' . $item_id . '" ';
    $html .= 'data-favorite="' . ($is_favorite ? '1' : '0') . '">';
    $html .= '<i class="' . $icon_class . ' fa-heart"></i>' . $text;
    $html .= '</button>';
    
    return $html;
}

/**
 * Outputs the JavaScript code for handling favorite buttons
 */
function outputFavoriteScript() {
    ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all favorite buttons
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                // Redirect to login if not logged in
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            // Get item data
            const formData = new FormData();
            
            // Check which type of item it is
            if (this.hasAttribute('data-hotel-id')) {
                formData.append('hotel_id', this.getAttribute('data-hotel-id'));
            } else if (this.hasAttribute('data-destination-id')) {
                formData.append('destination_id', this.getAttribute('data-destination-id'));
            } else if (this.hasAttribute('data-attraction-id')) {
                formData.append('attraction_id', this.getAttribute('data-attraction-id'));
            } else if (this.hasAttribute('data-room-id')) {
                formData.append('room_id', this.getAttribute('data-room-id'));
            }
            
            const isFavorite = this.getAttribute('data-favorite') === '1';
            const icon = this.querySelector('i');
            
            // Send AJAX request to toggle favorite status
            fetch('toggle_favorite.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update icon based on status
                    if (data.status === 'added') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        icon.classList.add('text-danger');
                        this.setAttribute('data-favorite', '1');
                        
                        // Update text if present
                        if (this.textContent !== '') {
                            this.textContent = ' Remove from Favorites';
                            this.prepend(icon);
                        }
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.remove('text-danger');
                        icon.classList.add('far');
                        this.setAttribute('data-favorite', '0');
                        
                        // Update text if present
                        if (this.textContent !== '') {
                            this.textContent = ' Add to Favorites';
                            this.prepend(icon);
                        }
                    }
                    
                    // Update favorite count in header if it exists
                    const favCountElement = document.querySelector('.favorites-count');
                    if (favCountElement && data.count !== undefined) {
                        favCountElement.textContent = data.count;
                    }
                } else {
                    console.error('Error:', data.message);
                    alert('Failed to update favorite: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        });
    });
});
</script>
    <?php
}
?>