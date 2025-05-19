<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'header.php';
require_once 'includes/db_connect.php';
require_once 'currency_functions.php';
require_once 'includes/favorites_functions.php';

// Get the current currency from session (or use default)
$current_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'SGD';

// Get any filter parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 2;
$rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : 1;
// Build the SQL query with filters
$sql = "SELECT h.*, MIN(r.price) AS lowest_price 
        FROM hotels h 
        LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
        WHERE 1=1";

// Add filters if they exist
if (!empty($location)) {
    $location = '%' . $conn->real_escape_string($location) . '%';
    $sql .= " AND (h.city LIKE '$location' OR h.country LIKE '$location' OR h.hotel_name LIKE '$location')";
}
if ($min_price > 0 || $max_price < 1000) {
    $sql .= " AND EXISTS (SELECT 1 FROM rooms r2 WHERE r2.hotel_id = h.hotel_id AND r2.price BETWEEN $min_price AND $max_price)";
}

$sql .= " GROUP BY h.hotel_id";
// Add sorting
switch ($sort_by) {
    case 'price_desc':
        $sql .= " ORDER BY lowest_price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY h.hotel_name ASC";
        break;
    case 'price_asc':
    default:
        $sql .= " ORDER BY lowest_price ASC";
        break;
}

// Execute the query
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
// Get all hotels
$hotels = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

$stmt->close();
// Get unique cities for filter dropdown
$location_sql = "SELECT DISTINCT city, country FROM hotels WHERE city IS NOT NULL ORDER BY country, city";
$location_result = $conn->query($location_sql);
$locations = [];
if ($location_result && $location_result->num_rows > 0) {
    while ($row = $location_result->fetch_assoc()) {
        $locations[] = [
            'city' => $row['city'],
            'country' => $row['country'],
            'display' => $row['city'] . ', ' . $row['country']
        ];
    }
}

// Get min and max prices for range slider
$price_sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM rooms";
$price_result = $conn->query($price_sql);
$price_range = $price_result->fetch_assoc();
$min_available_price = $price_range['min_price'] ?? 0;
$max_available_price = $price_range['max_price'] ?? 1000;

// Function to display star rating
function displayStarRating($rating)
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $html;
}

// Get user's favorite hotels
$favorite_hotels = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_sql = "SELECT hotel_id FROM favorites WHERE user_id = ? AND hotel_id IS NOT NULL";
    $fav_stmt = $conn->prepare($fav_sql);
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $favorite_hotels[] = $row['hotel_id'];
    }
    $fav_stmt->close();
}
?>

<!-- Hero Section -->
<div class="hotels-hero py-5 mb-4 position-relative bg-dark text-white">
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold">Discover Amazing Hotels</h1>
                <p class="lead">Find the perfect place to stay for your next adventure</p>
                <!-- Search Form -->
                <form action="hotels.php" method="GET" class="mt-4 p-4 bg-white rounded text-dark">
                    <div class="mb-3">
                        <label for="location" class="form-label">Destination</label>
                        <input type="text" class="form-control" id="location" name="location"
                            placeholder="City or Country" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="check-in" class="form-label">Check-in</label>
                            <input type="date" class="form-control" id="check-in" name="check_in"
                                min="<?php echo date('Y-m-d'); ?>" value="<?php echo $check_in; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="check-out" class="form-label">Check-out</label>
                            <input type="date" class="form-control" id="check-out" name="check_out"
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                value="<?php echo $check_out; ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="guests" class="form-label">Guests</label>
                            <select class="form-select" id="guests" name="guests">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="rooms" class="form-label">Rooms</label>
                            <select class="form-select" id="rooms" name="rooms">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $rooms == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Room<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Search Hotels</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Hero Background Image -->
    <div class="hotels-hero-bg"></div>
</div>
<!-- Breadcrumb -->
<div class="container mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hotels</li>
        </ol>
    </nav>
</div>
<!-- Main Content -->
<div class="container">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form action="hotels.php" method="GET" id="filter-form">
                        <!-- Hidden inputs to maintain search parameters -->
                        <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
                        <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
                        <input type="hidden" name="guests" value="<?php echo htmlspecialchars($guests); ?>">
                        <input type="hidden" name="rooms" value="<?php echo htmlspecialchars($rooms); ?>">
                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6>Price Range</h6>
                            <div class="d-flex justify-content-between">
                                <span id="min-price-display"><?php echo $current_currency; ?> <?php echo $min_price; ?></span>
                                <span id="max-price-display"><?php echo $current_currency; ?> <?php echo $max_price; ?></span>
                            </div>
                            <div class="price-slider-container mt-2 mb-3">
                                <div class="slider-track"></div>
                                <input type="range" id="min-price-slider" name="min_price"
                                    min="<?php echo $min_available_price; ?>"
                                    max="<?php echo $max_available_price; ?>"
                                    value="<?php echo $min_price; ?>" class="slider" aria-label="min_price">
                                <input type="range" id="max-price-slider" name="max_price"
                                    min="<?php echo $min_available_price; ?>"
                                    max="<?php echo $max_available_price; ?>"
                                    value="<?php echo $max_price; ?>" class="slider" aria-label="max_price">
                            </div>
                        </div>
                        <!-- Location Selection -->
                        <div class="mb-4">
                            <h6>Destination</h6>
                            <select class="form-select" name="location" id="location-select" aria-label="location">
                                <option value="">All Destinations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc['city']); ?>"
                                        <?php echo $location === $loc['city'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['display']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Sort By -->
                        <div class="mb-4">
                            <h6>Sort By</h6>
                            <select class="form-select" name="sort" id="sort-select" aria-label="sort">
                                <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>
                                    Price: Low to High
                                </option>
                                <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>
                                    Price: High to Low
                                </option>
                                <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>
                                    Name: A to Z
                                </option>
                            </select>
                        </div>
                        <!-- Star Rating -->
                        <div class="mb-4">
                            <h6>Star Rating</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rating5" aria-label="rating5">
                                <label class="form-check-label" for="rating5">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rating4" aria-label="rating4">
                                <label class="form-check-label" for="rating4">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="far fa-star text-warning"></i>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rating3" aria-label="rating3">
                                <label class="form-check-label" for="rating3">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="far fa-star text-warning"></i>
                                    <i class="far fa-star text-warning"></i>
                                    & up
                                </label>
                            </div>
                        </div>
                        <!-- Amenities -->
                        <div class="mb-4">
                            <h6>Amenities</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wifi">
                                <label class="form-check-label" for="wifi">
                                    <i class="fas fa-wifi me-2"></i>Free WiFi
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pool">
                                <label class="form-check-label" for="pool">
                                    <i class="fas fa-swimming-pool me-2"></i>Pool
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="restaurant">
                                <label class="form-check-label" for="restaurant">
                                    <i class="fas fa-utensils me-2"></i>Restaurant
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="parking">
                                <label class="form-check-label" for="parking">
                                    <i class="fas fa-parking me-2"></i>Free Parking
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Hotel Listings -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Hotels and Accommodations</h2>
                <p class="mb-0"><?php echo count($hotels); ?> properties found</p>
            </div>
            <!-- Hotel Cards -->
            <div class="row">
                <?php if (!empty($hotels)): ?>
                    <?php foreach ($hotels as $hotel): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 hotel-card">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($hotel['main_img_url']); ?>" class="card-img-top hotel-image" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>">
                                    <?php 
                                    $is_favorite = isset($_SESSION['user_id']) ? 
                                        isInFavorites($conn, $_SESSION['user_id'], 'hotel', $hotel['hotel_id']) : 
                                        false;

                                    echo renderFavoriteButton(
                                        'hotel', 
                                        $hotel['hotel_id'], 
                                        $is_favorite, 
                                        'btn-light position-absolute top-0 end-0 m-2 rounded-circle', 
                                        'sm', 
                                        false
                                    );
                                    ?>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h5>
                                        <div class="hotel-rating">
                                            <?php
                                            // Assign a default rating if none exists
                                            $rating = 4;
                                            echo displayStarRating($rating);
                                            ?>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($hotel['city'] ?? $hotel['country']); ?>
                                    </p>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($hotel['description'] ?? 'Experience a comfortable stay at this welcoming hotel.', 0, 100)); ?>...</p>
                                    <!-- Amenities Icons -->
                                    <div class="hotel-amenities mb-3">
                                            <i class="fas fa-wifi me-2" data-bs-toggle="tooltip" title="Free WiFi" aria-hidden="true"></i>
                                            <i class="fas fa-utensils me-2" data-bs-toggle="tooltip" title="Restaurant" aria-hidden="true"></i>
                                            <i class="fas fa-swimming-pool me-2" data-bs-toggle="tooltip" title="Swimming Pool" aria-hidden="true"></i>
                                            <i class="fas fa-parking me-2" data-bs-toggle="tooltip" title="Free Parking" aria-hidden="true"></i>
                                    </div>
                                    <div class="text-end mt-3">
                                        <p class="mb-0"><small>From</small></p>
                                        <h5 class="text-primary mb-0">
                                            <?php
                                            if (function_exists('displayConvertedPrice') && function_exists('getCurrentCurrency')) {
                                                $userCurrency = getCurrentCurrency();
                                                echo displayConvertedPrice($hotel['lowest_price'] ?? 100, $hotel['currency'], $userCurrency);
                                            } else {
                                                echo htmlspecialchars($hotel['currency']) . ' ' . htmlspecialchars($hotel['lowest_price'] ?? 100);
                                            }
                                            ?>
                                        </h5>
                                        <p class="text-muted"><small>per night</small></p>
                                        <a href="rooms.php?hotel_id=<?php echo $hotel['hotel_id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip">View Rooms</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <p class="mb-0">No hotels found matching your criteria. Please try adjusting your filters.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hero Section */
    .hotels-hero {
        position: relative;
        padding: 60px 0;
        margin-bottom: 30px;
        overflow: hidden;
    }
    .hotels-hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url('assets/images/destinations/thailand.jpg');
        background-size: cover;
        background-position: center;
        opacity: 0.7;
        z-index: -1;
    }
    /* Hotel Cards */
    .hotel-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 8px;
        overflow: hidden;
    }
    .hotel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .hotel-image {
        height: 200px;
        object-fit: cover;
    }
    .favorite-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .favorite-btn i.fas {
        color: #dc3545;
    }
    /* Price Range Slider */
    .price-slider-container {
        position: relative;
        width: 100%;
        height: 30px;
    }
    .slider-track {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 100%;
        height: 4px;
        background-color: #ddd;
        border-radius: 4px;
    }
    .slider {
        position: absolute;
        top: 0;
        width: 100%;
        -webkit-appearance: none;
        appearance: none;
        background: transparent;
        cursor: pointer;
        height: 30px;
        margin: 0;
        z-index: 10;
    }
    .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #007bff;
        cursor: pointer;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }
    .slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #007bff;
        cursor: pointer;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }
    /* Amenities Icons */
    .hotel-amenities i {
        color: #6c757d;
        font-size: 1.1rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Price range sliders
        const minSlider = document.getElementById('min-price-slider');
        const maxSlider = document.getElementById('max-price-slider');
        const minDisplay = document.getElementById('min-price-display');
        const maxDisplay = document.getElementById('max-price-display');
        if (minSlider && maxSlider) {
            // Update display when min slider changes
            minSlider.addEventListener('input', function() {
                minDisplay.textContent = '<?php echo $current_currency; ?> ' + this.value;
                // Make sure min doesn't exceed max
                if (parseInt(this.value) > parseInt(maxSlider.value)) {
                    maxSlider.value = this.value;
                    maxDisplay.textContent = '<?php echo $current_currency; ?> ' + this.value;
                }
            });
            // Update display when max slider changes
            maxSlider.addEventListener('input', function() {
                maxDisplay.textContent = '<?php echo $current_currency; ?> ' + this.value;
                // Make sure max doesn't go below min
                if (parseInt(this.value) < parseInt(minSlider.value)) {
                    minSlider.value = this.value;
                    minDisplay.textContent = '<?php echo $current_currency; ?> ' + this.value;
                }
            });
        }
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (typeof bootstrap !== 'undefined') {
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        // Auto-submit form when select elements change
        const autoSubmitSelects = document.querySelectorAll('#sort-select, #location-select');
        autoSubmitSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });
        // Date validation
        const checkInDate = document.getElementById('check-in');
        const checkOutDate = document.getElementById('check-out');
        if (checkInDate && checkOutDate) {
            checkInDate.addEventListener('change', function() {
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                const formattedDate = nextDay.toISOString().split('T')[0];
                checkOutDate.min = formattedDate;
                if (checkOutDate.value < formattedDate) {
                    checkOutDate.value = formattedDate;
                }
            });
        }
    });
</script>
<?php outputFavoriteScript(); ?>
<?php include 'footer.php'; ?>