<?php
// Start session
session_start();

$page_title = 'My Favorites - RoundTours';
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login
    header('Location: login.php');
    exit;
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once 'includes/db_connect.php';
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get favorite attractions
$attractions = [];
$sql = "SELECT a.*, d.location_name, d.country, d.city, d.currency
        FROM favorites f
        JOIN attractions a ON f.attraction_id = a.attraction_id
        JOIN destinations d ON a.destination_id = d.destination_id
        WHERE f.user_id = ? AND f.attraction_id IS NOT NULL
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $attractions[] = $row;
    }
}
$stmt->close();

// Get favorite destinations
$destinations = [];
$sql = "SELECT d.*
        FROM favorites f
        JOIN destinations d ON f.destination_id = d.destination_id
        WHERE f.user_id = ? AND f.destination_id IS NOT NULL
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}
$stmt->close();

// Get favorite hotels
$hotels = [];
$sql = "SELECT h.*
        FROM favorites f
        JOIN hotels h ON f.hotel_id = h.hotel_id
        WHERE f.user_id = ? AND f.hotel_id IS NOT NULL
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}
$stmt->close();

// Get favorite rooms
$rooms = [];
$sql = "SELECT r.*, h.hotel_name, h.currency
        FROM favorites f
        JOIN rooms r ON f.room_id = r.room_id
        JOIN hotels h ON r.hotel_id = h.hotel_id
        WHERE f.user_id = ? AND f.room_id IS NOT NULL
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$stmt->close();

// Count total favorites
$total_favorites = count($attractions) + count($destinations) + count($hotels) + count($rooms);
?>

<div class="container mt-4" id="favorites-container">
    <!-- Page Header (always shown) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Favorites</h1>
        <div>
            <a href="profile.php" class="btn btn-outline-secondary">
                <i class="fas fa-user me-2"></i> Back to Profile
            </a>
        </div>
    </div>
    
    <div id="favorites-content">
        <?php if ($total_favorites === 0): ?>
            <!-- Empty state - shown when no favorites exist -->
            <div class="empty-state">
                <div class="alert alert-info">
                    <p class="mb-0">You haven't saved any favorites yet. Explore destinations and attractions to add them to your favorites.</p>
                </div>
                <div class="text-center mt-4">
                    <a href="destinations.php" class="btn btn-primary">Explore Destinations</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Favorite Tabs - only shown when favorites exist -->
            <div class="favorites-tabs">
                <ul class="nav nav-tabs mb-4" id="favoritesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="attractions-tab" data-bs-toggle="tab" data-bs-target="#attractions" type="button" role="tab" aria-controls="attractions" aria-selected="true">
                            Attractions (<span class="attractions-count"><?php echo count($attractions); ?></span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="destinations-tab" data-bs-toggle="tab" data-bs-target="#destinations" type="button" role="tab" aria-controls="destinations" aria-selected="false">
                            Destinations (<span class="destinations-count"><?php echo count($destinations); ?></span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="hotels-tab" data-bs-toggle="tab" data-bs-target="#hotels" type="button" role="tab" aria-controls="hotels" aria-selected="false">
                            Hotels (<span class="hotels-count"><?php echo count($hotels); ?></span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false">
                            Rooms (<span class="rooms-count"><?php echo count($rooms); ?></span>)
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="favoritesTabContent">
                    <!-- Attractions Tab -->
                    <div class="tab-pane fade show active" id="attractions" role="tabpanel" aria-labelledby="attractions-tab">
                        <?php if (count($attractions) === 0): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't saved any attractions to your favorites yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row attractions-container">
                                <?php foreach ($attractions as $attraction): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <?php if (!empty($attraction['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($attraction['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($attraction['name']); ?>">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($attraction['name']); ?></h5>
                                                <p class="card-text text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i> 
                                                    <?php echo htmlspecialchars($attraction['location_name']); ?>, <?php echo htmlspecialchars($attraction['country']); ?>
                                                </p>
                                                
                                                <?php if (!empty($attraction['category'])): ?>
                                                    <span class="badge bg-info text-white mb-2"><?php echo htmlspecialchars($attraction['category']); ?></span>
                                                <?php endif; ?>
                                                
                                                <p class="card-text small mb-3">
                                                    <?php 
                                                        $desc = $attraction['description'] ?? '';
                                                        echo htmlspecialchars(substr($desc, 0, 100) . (strlen($desc) > 100 ? '...' : ''));
                                                    ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span>
                                                        <?php if (floatval($attraction['entrance_fee']) > 0): ?>
                                                            <strong><?php echo htmlspecialchars($attraction['entrance_fee']); ?> <?php echo htmlspecialchars($attraction['currency'] ?? 'THB'); ?></strong>
                                                        <?php else: ?>
                                                            <span class="text-success">Free</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <a href="attraction-detail.php?id=<?php echo $attraction['attraction_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer bg-white">
                                                <button class="btn btn-outline-danger btn-sm remove-favorite w-100" data-attraction-id="<?php echo $attraction['attraction_id']; ?>">
                                                    <i class="fas fa-heart"></i> Remove from Favorites
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Destinations Tab -->
                    <div class="tab-pane fade" id="destinations" role="tabpanel" aria-labelledby="destinations-tab">
                        <?php if (count($destinations) === 0): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't saved any destinations to your favorites yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row destinations-container">
                                <?php foreach ($destinations as $destination): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <?php if (!empty($destination['main_image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($destination['main_image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($destination['location_name']); ?>">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($destination['location_name']); ?></h5>
                                                <p class="card-text text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i> 
                                                    <?php echo htmlspecialchars($destination['country']); ?>
                                                    <?php if (!empty($destination['city'])): ?>
                                                        , <?php echo htmlspecialchars($destination['city']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <?php if (!empty($destination['climate'])): ?>
                                                    <p class="card-text small mb-2">
                                                        <i class="fas fa-temperature-high me-1"></i> 
                                                        <?php echo htmlspecialchars($destination['climate']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($destination['best_time_to_visit'])): ?>
                                                    <p class="card-text small mb-2">
                                                        <i class="far fa-calendar-alt me-1"></i> 
                                                        Best time to visit: <?php echo htmlspecialchars($destination['best_time_to_visit']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-end align-items-center mt-2">
                                                    <a href="destination-detail.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer bg-white">
                                                <button class="btn btn-outline-danger btn-sm remove-favorite w-100" data-destination-id="<?php echo $destination['destination_id']; ?>">
                                                    <i class="fas fa-heart"></i> Remove from Favorites
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hotels Tab -->
                    <div class="tab-pane fade" id="hotels" role="tabpanel" aria-labelledby="hotels-tab">
                        <?php if (count($hotels) === 0): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't saved any hotels to your favorites yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row hotels-container">
                                <?php foreach ($hotels as $hotel): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <?php if (!empty($hotel['main_img_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($hotel['main_img_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h5>
                                                <p class="card-text text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i> 
                                                    <?php echo htmlspecialchars($hotel['city'] ?? $hotel['country']); ?>
                                                </p>
                                                
                                                <?php if (!empty($hotel['description'])): ?>
                                                    <p class="card-text small mb-3">
                                                        <?php 
                                                            $desc = $hotel['description'];
                                                            echo htmlspecialchars(substr($desc, 0, 100) . (strlen($desc) > 100 ? '...' : ''));
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <!-- Get the lowest room price for this hotel -->
                                                <?php
                                                $price_sql = "SELECT MIN(price) AS lowest_price FROM rooms WHERE hotel_id = ?";
                                                $price_stmt = $conn->prepare($price_sql);
                                                $price_stmt->bind_param("i", $hotel['hotel_id']);
                                                $price_stmt->execute();
                                                $price_result = $price_stmt->get_result();
                                                $lowest_price = $price_result->fetch_assoc()['lowest_price'] ?? 0;
                                                $price_stmt->close();
                                                ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span>
                                                        <?php if ($lowest_price > 0): ?>
                                                            <strong>From <?php echo htmlspecialchars($hotel['currency']); ?> <?php echo htmlspecialchars($lowest_price); ?></strong>
                                                        <?php else: ?>
                                                            <span class="text-muted">Price unavailable</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <a href="rooms.php?hotel_id=<?php echo $hotel['hotel_id']; ?>" class="btn btn-sm btn-outline-primary">View Rooms</a>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer bg-white">
                                                <button class="btn btn-outline-danger btn-sm remove-favorite w-100" data-hotel-id="<?php echo $hotel['hotel_id']; ?>">
                                                    <i class="fas fa-heart"></i> Remove from Favorites
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rooms Tab -->
                    <div class="tab-pane fade" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
                        <?php if (count($rooms) === 0): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't saved any rooms to your favorites yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row rooms-container">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <?php if (!empty($room['main_img_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($room['main_img_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?></h5>
                                                <p class="card-text text-muted mb-2">
                                                    <i class="fas fa-hotel me-1"></i> 
                                                    <?php echo htmlspecialchars($room['hotel_name']); ?>
                                                </p>
                                                
                                                <?php if (!empty($room['description'])): ?>
                                                    <p class="card-text small mb-3">
                                                        <?php 
                                                            $desc = $room['description'];
                                                            echo htmlspecialchars(substr($desc, 0, 100) . (strlen($desc) > 100 ? '...' : ''));
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span>
                                                        <strong><?php echo htmlspecialchars($room['price']); ?> <?php echo htmlspecialchars($room['currency']); ?></strong>
                                                        <?php if ($room['discount'] > 0): ?>
                                                            <span class="badge bg-danger ms-1">-<?php echo htmlspecialchars($room['discount']); ?>%</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <a href="rooms.php?hotel_id=<?php echo $room['hotel_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer bg-white">
                                                <button class="btn btn-outline-danger btn-sm remove-favorite w-100" data-room-id="<?php echo $room['room_id']; ?>">
                                                    <i class="fas fa-heart"></i> Remove from Favorites
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>             

<!-- JavaScript for removing favorites -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all remove favorite buttons
    const removeButtons = document.querySelectorAll('.remove-favorite');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const attractionId = this.getAttribute('data-attraction-id');
            const destinationId = this.getAttribute('data-destination-id');
            const hotelId = this.getAttribute('data-hotel-id');
            const roomId = this.getAttribute('data-room-id');
            
            // Confirm removal
            if (!confirm('Are you sure you want to remove this from your favorites?')) {
                return;
            }
            
            // Create form data based on type
            const formData = new FormData();
            if (attractionId) {
                formData.append('attraction_id', attractionId);
            } else if (destinationId) {
                formData.append('destination_id', destinationId);
            } else if (hotelId) {
                formData.append('hotel_id', hotelId);
            } else if (roomId) {
                formData.append('room_id', roomId);
            }
            
            // Send AJAX request to toggle (remove) favorite
            fetch('toggle_favorite.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the card from the UI
                    const card = this.closest('.col-md-6');
                    card.style.transition = 'opacity 0.3s ease';
                    card.style.opacity = '0';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Update counters and check content
                        let attractionsCount = document.querySelectorAll('.attractions-container .col-md-6').length;
                        let destinationsCount = document.querySelectorAll('.destinations-container .col-md-6').length;
                        let hotelsCount = document.querySelectorAll('.hotels-container .col-md-6').length;
                        let roomsCount = document.querySelectorAll('.rooms-container .col-md-6').length;
                        
                        // Update the counter displays
                        document.querySelector('.attractions-count').textContent = attractionsCount;
                        document.querySelector('.destinations-count').textContent = destinationsCount;
                        document.querySelector('.hotels-count').textContent = hotelsCount;
                        document.querySelector('.rooms-count').textContent = roomsCount;
                        
                        // Handle empty states
                        if (attractionId && attractionsCount === 0) {
                            document.querySelector('#attractions').innerHTML = 
                                '<div class="alert alert-info"><p class="mb-0">You haven\'t saved any attractions to your favorites yet.</p></div>';
                        }
                        
                        if (destinationId && destinationsCount === 0) {
                            document.querySelector('#destinations').innerHTML = 
                                '<div class="alert alert-info"><p class="mb-0">You haven\'t saved any destinations to your favorites yet.</p></div>';
                        }

                        if (hotelId && hotelsCount === 0) {
                            document.querySelector('#hotels').innerHTML = 
                                '<div class="alert alert-info"><p class="mb-0">You haven\'t saved any hotels to your favorites yet.</p></div>';
                        }
                        
                        if (roomId && roomsCount === 0) {
                            document.querySelector('#rooms').innerHTML = 
                                '<div class="alert alert-info"><p class="mb-0">You haven\'t saved any rooms to your favorites yet.</p></div>';
                        }
                        
                        // If all are empty, show the main empty state
                        if (attractionsCount === 0 && destinationsCount === 0 && hotelsCount === 0 && roomsCount === 0) {
                            document.getElementById('favorites-content').innerHTML = `
                                <div class="empty-state">
                                    <div class="alert alert-info">
                                        <p class="mb-0">You haven't saved any favorites yet. Explore destinations and attractions to add them to your favorites.</p>
                                    </div>
                                    <div class="text-center mt-4">
                                        <a href="destinations.php" class="btn btn-primary">Explore Destinations</a>
                                    </div>
                                </div>
                            `;
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing this item from favorites.');
            });
        });
    });
});
</script>

<?php include 'footer.php'; ?>