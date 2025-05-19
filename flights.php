<?php
// Set page title for header.php
$page_title = "Flights - RoundTours";

// Include header FIRST (includes the HTML head and opening body tag)
include 'header.php';

require_once 'includes/db_connect.php';
require_once 'currency_functions.php';
require_once 'includes/itinerary_functions.php';
// Initialize variables to store search information
$origin = $destination = $departureDate = $returnDate = $passengerDetails = '';
$queried = false;
$flights = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tripType = isset($_GET['tripType']) ? $_GET['tripType'] : '';
    $origin = isset($_GET['origin']) ? $_GET['origin'] : '';
    $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
    $departureDate = isset($_GET['departureDate']) ? $_GET['departureDate'] : '';
    $returnDate = isset($_GET['returnDate']) ? $_GET['returnDate'] : '';
    $passengerDetails = isset($_GET['passengerDetails']) ? $_GET['passengerDetails'] : 'economy';

    // Prepare SQL query with placeholders
    $sql = "SELECT f.*, a.name AS airline_name, a.logo_url, a.currency FROM flights f 
        JOIN airlines a ON f.airline_id = a.airline_id WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($origin)) {
        $sql .= " AND f.origin LIKE ?";
        $params[] = '%' . $origin . '%'; // Add wildcards for partial match
        $types .= 's';
    }

    if (!empty($destination)) {
        $sql .= " AND f.destination LIKE ?";
        $params[] = '%' . $destination . '%'; // Add wildcards for partial match
        $types .= 's';
    }

    if (!empty($departureDate)) {
        $sql .= " AND DATE(departure_date_time) = ?";
        $params[] = $departureDate;
        $types .= 's';
    }

    if (!empty($returnDate)) {
        $sql .= " AND DATE(arrival_date_time) = ?";
        $params[] = $returnDate;
        $types .= 's';
    }

    // Validate and append the passengerDetails condition
    if (!empty($passengerDetails)) {
        // Whitelist of allowed column prefixes
        $allowedColumns = ['economy', 'premium_economy', 'business', 'first_class'];
        // Check if $passengerDetails is in the whitelist
        if (in_array($passengerDetails, $allowedColumns)) {
            // Safely append the condition
            $sql .= " AND {$passengerDetails}_seats_available > ?";
            $params[] = 0; // Add the value to the params array
            $types .= 'i'; // Integer type for the value
        } else {
            die("Invalid passenger details.");
        }
    }
    $sql .= " ORDER BY f.economy_price ASC";
    // Prepare and execute the query
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params); // Bind parameters dynamically
            $stmt->execute();
            $result = $stmt->get_result();

            // Fetch matching flights
            $flights = $result->fetch_all(MYSQLI_ASSOC);
            $queried = true;
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
}

function calculateFlightDuration($departureDateTime, $arrivalDateTime)
{
    // Convert datetime strings to Unix timestamps
    $departureTimestamp = strtotime($departureDateTime);
    $arrivalTimestamp = strtotime($arrivalDateTime);

    // Calculate the difference in seconds
    $durationInSeconds = $arrivalTimestamp - $departureTimestamp;

    // Convert seconds to hours and minutes
    $hours = floor($durationInSeconds / 3600); // Total hours
    $minutes = floor(($durationInSeconds % 3600) / 60); // Remaining minutes

    // Format as HH:MM
    return $minutes ? sprintf('%d hours %d minutes', $hours, $minutes) : sprintf('%d hours', $hours);
}
?>

<!-- Add styles in the proper location - after header.php but before your content -->
<style>
    /* Custom styles */
    .search-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 10px;
        margin-top: 30px;
        /* Add spacing after navbar */
    }

    .search-container label {
        margin-right: 10px;
    }

    .search-container input[type="text"],
    .search-container select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .search-container button {
        background-color: #0d6efd;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
    }

    .search-container button:hover {
        background-color: #0a58ca;
    }

    .swap-button {
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .swap-button i {
        font-size: 18px;
    }

    /* General card styling */
    .flight-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .flight-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Arrow styling for flight duration */
    .flight-duration .arrow {
        position: relative;
        width: 100px;
        height: 2px;
        background-color: #007bff;
    }

    .flight-duration .arrow::after {
        content: '';
        position: absolute;
        top: 50%;
        right: 0;
        transform: translateY(-50%);
        border: 6px solid transparent;
        border-left-color: #007bff;
    }

    /* Align icons and text */
    .fas {
        font-size: 14px;
        color: #007bff;
    }

    /* Add to cart button */
    .btn-add-to-cart {
        background-color: rgb(48, 132, 68);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-add-to-cart:hover {
        background-color: rgb(48, 132, 68);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Small screen adjustments */
    @media (max-width: 768px) {
        .flight-card {
            flex-direction: column;
            align-items: stretch;
        }

        .col-md-2 {
            text-align: center;
        }
    }
</style>

<div class="container mt-4"> <!-- Added margin-top for spacing -->
    <form method="GET" class="search-container">
        <!-- Origin and Destination -->
        <div class="row mt-4">
            <div class="col-md-4">
                <input type="text" class="form-control" name="origin" placeholder="From" value="<?= htmlspecialchars($origin) ?>" required>
                <small>All airports</small>
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="swap-button" aria-label="Swap">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="destination" placeholder="To" value="<?= htmlspecialchars($destination) ?>" required>
                <small>All airports</small>
            </div>
        </div>

        <!-- Dates -->
        <div class="row mt-4">
            <div class="col-md-4">
                <label for="passengerDetails">Departure Date</label>
                <input type="date" class="form-control" name="departureDate" aria-label="departureDate" value="<?= htmlspecialchars($departureDate) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="passengerDetails">Return Date</label>
                <input type="date" class="form-control" name="returnDate" aria-label="returnDate" value="<?= htmlspecialchars($returnDate) ?>">
            </div>
            <div class="col-md-4">
                <label for="passengerDetails">Passenger Class</label>
                <select class="form-select" name="passengerDetails" aria-label="Passenger Class">
                    <option value="economy" <?= $passengerDetails === 'economy' ? 'selected' : '' ?>>Economy</option>
                    <option value="premium_economy" <?= $passengerDetails === 'premium_economy' ? 'selected' : '' ?>>Premium Economy</option>
                    <option value="business" <?= $passengerDetails === 'business' ? 'selected' : '' ?>>Business</option>
                    <option value="first_class" <?= $passengerDetails === 'first_class' ? 'selected' : '' ?>>First Class</option>
                </select>
            </div>
        </div>

        <!-- Search Buttons -->
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        </div>
    </form>

    <?php if (!empty($flights)): ?>
        <?php foreach ($flights as $flight): ?>
            <div class="container flight-card d-flex align-items-center border rounded p-3 mt-3 mb-1" style="background-color: #f9f9f9;">
                <!-- Airline Information -->
                <div class="col-md-2 text-center">
                    <img src="<?= htmlspecialchars($flight['logo_url']) ?>" alt="<?= htmlspecialchars($flight['airline_name']) ?>" class="img-fluid" style="max-height: 50px;">
                    <span class="d-block mt-2"><?= htmlspecialchars($flight['airline_name']) ?></span>
                    <!-- Amenities -->
                    <div class="mt-2 small">
                        <i class="fas fa-bolt me-1"></i>In-flight Wi-Fi
                        <i class="fas fa-wifi ms-2 me-1"></i>Free Wi-Fi
                    </div>
                </div>

                <!-- Departure Details -->
                <div class="col-md-2 text-center">
                    <strong><?= date('H:i', strtotime($flight['departure_date_time'])) ?></strong><br>
                    <small class="text-muted"><?= date('Y-m-d', strtotime($flight['departure_date_time'])) ?></small><br>
                    <small><?= htmlspecialchars($flight['origin']) ?></small>
                </div>

                <!-- Flight Duration -->
                <div class="col-md-2 text-center">
                    <strong><?= calculateFlightDuration($flight['departure_date_time'], $flight['arrival_date_time']) ?></strong><br>
                    <div class="flight-duration d-flex justify-content-center">
                        <div class="arrow"></div>
                    </div>
                </div>

                <!-- Arrival Details -->
                <div class="col-md-2 text-center">
                    <strong><?= date('H:i', strtotime($flight['arrival_date_time'])) ?></strong><br>
                    <small class="text-muted"><?= date('Y-m-d', strtotime($flight['arrival_date_time'])) ?></small><br>
                    <small><?= htmlspecialchars($flight['destination']) ?></small>
                </div>

                <!-- Price -->
                <div class="col-md-2 text-center">
                    <?php
                    $flightPrice = floatval($flight[$passengerDetails . "_price"]);
                    $originalCurrency = $flight['currency'] ?? 'SGD';
                    $userCurrency = getCurrentCurrency();
                    echo '<strong>' . htmlspecialchars(displayConvertedPrice($flightPrice, $originalCurrency, $userCurrency)) . '</strong>';
                    ?>
                    <div class="mt-1 small"><?= ucfirst($passengerDetails) ?> Class</div>
                </div>

                <!-- Add to Cart Button -->
                <div class="col-md-2 text-end">
                    <button class="btn btn-add-to-cart w-100"
                        onclick="addFlightToCart(<?= $flight['flight_id'] ?>, 
                                                 '<?= htmlspecialchars($flight['airline_name']) ?> Flight', 
                                                 <?= $flightPrice ?>, 
                                                 '<?= htmlspecialchars($originalCurrency) ?>', 
                                                 '<?= htmlspecialchars($flight['logo_url']) ?>', 
                                                 '<?= htmlspecialchars($flight['origin']) ?>', 
                                                 '<?= htmlspecialchars($flight['destination']) ?>', 
                                                 '<?= htmlspecialchars(date('Y-m-d', strtotime($flight['departure_date_time']))) ?>', 
                                                 '<?= htmlspecialchars(date('H:i', strtotime($flight['departure_date_time']))) ?>', 
                                                 '<?= htmlspecialchars($passengerDetails) ?>')">
                        <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                    </button>
                    <!-- Add to Itinerary Button -->
                    <?php
                    $flight_name = $flight['airline_name'] . ' Flight: ' . $flight['origin'] . ' to ' . $flight['destination'];
                    echo str_replace('class="btn', 'class="btn w-100', renderItineraryButton('flight', $flight['flight_id'], $flight_name));
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php if ($queried): ?>
            <div class="alert alert-info mt-4">No flights found matching your search criteria.</div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- Add scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Swap button functionality
        document.querySelector('.swap-button').addEventListener('click', function() {
            const originInput = document.querySelector('input[name="origin"]');
            const destinationInput = document.querySelector('input[name="destination"]');
            const temp = originInput.value;
            originInput.value = destinationInput.value;
            destinationInput.value = temp;
        });
    });

    // Add flight to cart function
    function addFlightToCart(flightId, flightName, price, currency, imageUrl, origin, destination, date, time, seatClass) {
        // Create cart item object
        const cartItem = {
            id: 'flight_' + flightId,
            type: 'flight',
            name: flightName + ': ' + origin + ' to ' + destination,
            price: price,
            currency: currency,
            image: imageUrl,
            origin: origin,
            destination: destination,
            date: date,
            time: time,
            seatClass: seatClass,
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
        showAddToCartNotification(flightName, origin, destination);

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
    function showAddToCartNotification(flightName, origin, destination) {
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
                    <div>${flightName}: ${origin} to ${destination}</div>
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

    // Initialize cart count when page loads
    updateCartCount();
</script>

<?php
outputItineraryScript();
include 'footer.php';
?>