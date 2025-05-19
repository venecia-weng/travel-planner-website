<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db_connect.php';
require_once 'currency_functions.php'; 
require_once 'includes/itinerary_functions.php';

// Include favorites functions if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'includes/favorites_functions.php';
}

// Get hotel ID from URL
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

if ($hotel_id <= 0) {
    // Invalid ID, redirect to hotels page
    header("Location: hotels.php");
    exit();
}

// Get hotel details
$sql = "SELECT * FROM hotels WHERE hotel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Hotel not found, redirect to hotels page
    header("Location: hotels.php");
    exit();
}

$hotel = $result->fetch_assoc();
$stmt->close(); // Close this statement but keep connection open

// Get rooms for this hotel
$sql = "SELECT * FROM rooms WHERE hotel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms_result = $stmt->get_result();

$rooms = array();
if ($rooms_result->num_rows > 0) {
    while ($row = $rooms_result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$stmt->close(); // Close this statement but keep connection open

// Page title
$page_title = htmlspecialchars($hotel['hotel_name']) . ' - Rooms';

// Include header
include 'header.php';
?>
<style>
    /* Custom styles */
    .room-card {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .room-card img {
        width: 100%;
        height: auto;
    }

    .room-details {
        padding: 15px;
    }

    .room-price {
        font-weight: bold;
        color: #0d6efd;
    }

    .reserve-button {
        background-color: #0d6efd;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
    }

    .reserve-button:hover {
        background-color: #0a58ca;
    }

    .amenity-icon {
        font-size: 18px;
        margin-right: 5px;
    }

    .best-price-badge {
        background-color:rgb(196, 71, 71);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
    }

    .discount-badge {
        background-color:rgb(196, 71, 71);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
    }

    .btn-add-to-cart {
        background-color: rgb(48, 132, 68);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-add-to-cart:hover {
        background-color: rgb(48, 132, 68);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>

<body>
    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="hotels.php">Hotels</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($hotel['hotel_name']); ?></li>
            </ol>
        </nav>
    </div>

    <div class="container mt-5">
        <?php if (!empty($rooms)): ?>
            <?php foreach ($rooms as $room): ?>
                <div class="row room-card">
                    <!-- Room Image -->
                    <div class="col-md-3">
                        <img src="<?= htmlspecialchars($room['main_img_url']) ?>" alt="<?= htmlspecialchars($room['room_type']) ?>">
                        <h2><?= htmlspecialchars($room['room_type']) ?></h2>
                        <p><strong><?= htmlspecialchars($room['description']) ?></strong></p>
                        <ul>
                            <li><i class="fas fa-map-marker-alt"></i> City View</li>
                            <li><i class="fas fa-smoking-ban"></i> Non-smoking</li>
                            <li><i class="fas fa-wifi"></i> Free Wi-Fi</li>
                            <li><i class="fas fa-air-conditioning"></i> Air conditioning</li>
                            <li><i class="fas fa-bath"></i> Private Bathroom</li>
                        </ul>
                        <a href="#" style="color: darkblue">Room Details</a>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="mt-2">
                                <?php 
                                $is_favorite = isInFavorites($conn, $_SESSION['user_id'], 'room', $room['room_id']);
                                echo renderFavoriteButton('room', $room['room_id'], $is_favorite, 'btn-outline-danger', 'sm', true);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Room Details -->
                    <div class="col-md-6">
                        <h5>Your Choices</h5>
                        <?php if ($room['discount']): ?>
                            <div class="best-price-badge">Today's best price!</div>
                        <?php endif; ?>
                        <ul>
                            <li><i class="fas fa-utensils amenity-icon"></i> Breakfast Included</li>
                            <li><i class="fas fa-shield-lock amenity-icon"></i> Non-refundable</li>
                            <li><i class="fas fa-lightning-bolt amenity-icon"></i> Instant Confirmation</li>
                            <li><i class="fas fa-money-check-alt amenity-icon"></i> Prepay online</li>
                        </ul>
                        <p><small><i class="fas fa-info-circle"></i></small></p>
                    </div>

                    <!-- Sleeps -->
                    <div class="col-md-1">
                        <h5>Sleeps</h5>
                        <span class="bi bi-people-fill"></span> <?= htmlspecialchars($room['num_adults']) ?> adults
                        <span class="fa-solid fa-child"></span> <?= htmlspecialchars($room['num_children']) ?> child
                    </div>

                    <!-- Price and Reserve Button -->
                    <div class="col-md-2">
                        <h5>Today's Price</h5>
                        <?php
                        $roomPrice = floatval($room['price']);
                        $originalCurrency = $hotel['currency'] ?? 'SGD'; 
                        $userCurrency = getCurrentCurrency();
                        
                        // Calculate final price with discount
                        $finalPrice = $roomPrice;
                        if ($room['discount']) {
                            echo '<div class="discount-badge">Special Discount ' . htmlspecialchars($room['discount']) . '%</div>';

                            $discountAmount = $roomPrice * ($room['discount'] / 100);
                            $finalPrice = $roomPrice - $discountAmount;

                            echo '<del>' . htmlspecialchars(displayConvertedPrice($roomPrice, $originalCurrency, $userCurrency)) . '</del>';
                            echo '<span class="room-price">' . htmlspecialchars(displayConvertedPrice($finalPrice, $originalCurrency, $userCurrency)) . '</span>';
                        } else {
                            echo '<span class="room-price">' . htmlspecialchars(displayConvertedPrice($finalPrice, $originalCurrency, $userCurrency)) . '</span>';
                        }
                        ?>
                        <p><small>Incl. taxes &amp; fees</small></p>
                        <div class="mt-3">
                            <!-- Add to Cart Button -->
                            <button class="btn-add-to-cart w-100" 
                                    onclick="addRoomToCart(<?= $room['room_id'] ?>, 
                                                         '<?= htmlspecialchars($hotel['hotel_name']) ?>', 
                                                         '<?= htmlspecialchars($room['room_type']) ?>', 
                                                         <?= $finalPrice ?>, 
                                                         '<?= htmlspecialchars($originalCurrency) ?>', 
                                                         '<?= htmlspecialchars($room['main_img_url']) ?>', 
                                                         <?= $room['num_adults'] ?>, 
                                                         <?= $room['num_children'] ?>)">
                                <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                            </button>
                            <!-- Add to Itinerary button -->
                            <div class="mt-2">
                                <?php 
                                $room_name = $hotel['hotel_name'] . ' - ' . $room['room_type'];
                                echo renderItineraryButton('room', $room['room_id'], $room_name);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No rooms available for this hotel.</p>
        <?php endif; ?>
    </div>

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize cart count when page loads
            updateCartCount();
        });
        
        // Add room to cart function
        function addRoomToCart(roomId, hotelName, roomType, price, currency, imageUrl, numAdults, numChildren) {
            // Get current date in YYYY-MM-DD format
            const today = new Date();
            const checkInDate = today.toISOString().split('T')[0];
            
            // Get checkout date (tomorrow)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const checkOutDate = tomorrow.toISOString().split('T')[0];
            
            // Create cart item object
            const cartItem = {
                id: 'room_' + roomId,
                type: 'room',
                name: hotelName + ' - ' + roomType,
                price: price,
                currency: currency,
                image: imageUrl,
                checkIn: checkInDate,
                checkOut: checkOutDate,
                numAdults: numAdults,
                numChildren: numChildren,
                nights: 1,
                guests: 1,
                subtotal: price
            };
            
            // Get existing cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Check if item already exists in cart
            const existingItemIndex = cart.findIndex(item => item.id === cartItem.id);
            
            if (existingItemIndex >= 0) {
                // Update existing item
                cart[existingItemIndex] = cartItem;
            } else {
                // Add new item
                cart.push(cartItem);
            }
            
            // Save updated cart back to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Show success message
            showAddToCartNotification(hotelName, roomType);
            
            // Update cart count in header
            updateCartCount();
        }
        
        // Function to update cart count in header
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = cart.length;
            
            const cartCountElements = document.querySelectorAll('.cart-count');
            if (cartCountElements.length > 0) {
                cartCountElements.forEach(element => {
                    element.textContent = cartCount;
                    element.style.display = cartCount > 0 ? 'inline-block' : 'none';
                });
            }
        }
        
        // Function to show "Added to Cart" notification
        function showAddToCartNotification(hotelName, roomType) {
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
                        <div style="font-weight: bold;">Added to Cart</div>
                        <div>${hotelName} - ${roomType}</div>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <a href="cart.php" style="color: white; text-decoration: underline;">View Cart</a>
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
    </script>
</body>

<?php if (isset($_SESSION['user_id'])): ?>
    <?php outputFavoriteScript(); ?>
<?php endif; ?>

<?php
outputItineraryScript();
$conn->close();
include 'footer.php';
?>