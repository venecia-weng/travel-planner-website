<?php
// Start session
session_start();

require_once 'includes/db_connect.php';
require_once 'display_attraction_reviews.php';
require_once 'currency_functions.php'; 
require_once 'includes/favorites_functions.php';
require_once 'currency_functions.php';
require_once 'includes/itinerary_functions.php';

// Get attraction ID from URL
$attraction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($attraction_id <= 0) {
    // Invalid ID, redirect to destinations page
    header("Location: destinations.php");
    exit();
}

// Get attraction details
$sql = "SELECT a.*, d.location_name, d.country, d.city, d.currency
        FROM Attractions a
        JOIN Destinations d ON a.destination_id = d.destination_id
        WHERE a.attraction_id = ? AND a.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $attraction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Attraction not found, redirect to destinations page
    header("Location: destinations.php");
    exit();
}

$attraction = $result->fetch_assoc();
$destination_id = $attraction['destination_id'];

// Check if attraction is in user's favorites
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND attraction_id = ?";
    $fav_stmt = $conn->prepare($fav_sql);
    $fav_stmt->bind_param("ii", $user_id, $attraction_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    $is_favorited = ($fav_result->num_rows > 0);
    $fav_stmt->close();
}

// Parse JSON data from itinerary field if it exists
$itinerary_items = [];
if (!empty($attraction['itinerary'])) {
    $itinerary_items = json_decode($attraction['itinerary'], true);
}

// Parse JSON data from additional_info field if it exists
$additional_info = [];
if (!empty($attraction['additional_info'])) {
    $additional_info = json_decode($attraction['additional_info'], true);
}

// Parse JSON data from faqs field if it exists
$faqs = [];
if (!empty($attraction['faqs'])) {
    $faqs = json_decode($attraction['faqs'], true);
}

// Get transportation details if the field exists and is populated
$transportation_details = null;
if (isset($attraction['transportation_details']) && !empty($attraction['transportation_details'])) {
    $transportation_details = json_decode($attraction['transportation_details'], true);
}

// Determine if attraction is bookable (has a fee > 0)
$is_bookable = (isset($attraction['entrance_fee']) && floatval($attraction['entrance_fee']) > 0);

// Get similar attractions
$sql = "SELECT * FROM Attractions 
        WHERE destination_id = ? 
        AND attraction_id != ? 
        AND status = 'active'
        ORDER BY RAND() 
        LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $destination_id, $attraction_id);
$stmt->execute();
$similar_attractions_result = $stmt->get_result();

$similar_attractions = array();
if ($similar_attractions_result->num_rows > 0) {
    while($row = $similar_attractions_result->fetch_assoc()) {
        $similar_attractions[] = $row;
    }
}

// Get reviews for this attraction
$reviews = getAttractionReviews($conn, $attraction_id, 3);
$reviewStats = getAttractionReviewStats($conn, $attraction_id);

// Page title
$page_title = htmlspecialchars($attraction['name']) . ' - ' . htmlspecialchars($attraction['location_name']);

// Include header
include 'header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="destinations.php">Destinations</a></li>
            <li class="breadcrumb-item"><a href="destination-detail.php?id=<?php echo $destination_id; ?>"><?php echo htmlspecialchars($attraction['location_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($attraction['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <!-- Attraction Header -->
            <h1 class="mb-2"><?php echo htmlspecialchars($attraction['name']); ?></h1>
            <p class="text-muted mb-4">
                <i class="fas fa-map-marker-alt mr-1"></i> 
                <?php echo htmlspecialchars($attraction['address'] ?? $attraction['city'] . ', ' . $attraction['country']); ?>
            </p>

            <!-- Attraction Image -->
            <div class="mb-4 rounded overflow-hidden" style="height: 400px;">
                <?php if(!empty($attraction['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($attraction['image_url']); ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo htmlspecialchars($attraction['name']); ?>">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted">No image available</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Attraction Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">About <?php echo htmlspecialchars($attraction['name']); ?></h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($attraction['description'] ?? 'No description available.')); ?></p>
                    
                    <?php if(!empty($attraction['category'])): ?>
                    <div class="mt-3">
                        <span class="badge bg-info text-white"><?php echo htmlspecialchars($attraction['category']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Departure & return -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Departure & return</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($transportation_details)): ?>
                    <div class="mb-3">
                        <h6>Departure point(s)</h6>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($transportation_details['departure_point'] ?? ''); ?></p>
                        <p class="text-muted small">
                            <?php echo htmlspecialchars($transportation_details['departure_address'] ?? ''); ?>
                        </p>
                    </div>
                    
                    <div>
                        <h6>Return point(s)</h6>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($transportation_details['return_point'] ?? ''); ?></p>
                        <p class="text-muted small">
                            <?php echo htmlspecialchars($transportation_details['return_address'] ?? ''); ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <h6>Departure point(s)</h6>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($attraction['address'] ?? ''); ?></p>
                        <p class="text-muted small">The main entrance of the attraction</p>
                    </div>
                    
                    <div>
                        <h6>Return point(s)</h6>
                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($attraction['address'] ?? ''); ?></p>
                        <p class="text-muted small">Returns to the original departure point</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Itinerary -->
            <?php if(!empty($itinerary_items)): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">What to expect</h5>
                </div>
                <div class="card-body">
                    <h6>Itinerary</h6>
                    <div class="itinerary-timeline">
                        <?php foreach($itinerary_items as $index => $item): ?>
                        <div class="itinerary-item d-flex mb-4">
                            <div class="timeline-icon me-3">
                                <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <?php if($index < count($itinerary_items) - 1): ?>
                                <div class="timeline-line" style="width: 2px; height: 100%; margin-left: 14px; background-color: #e9ecef;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="itinerary-content">
                                <h6 class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <p class="text-muted small">Duration: <?php echo htmlspecialchars($item['duration']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Additional Info -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Additional info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php if(!empty($attraction['public_transportation_info'])): ?>
                        <li class="d-flex align-items-start mb-3">
                            <i class="fas fa-bus text-primary me-3 mt-1" style="min-width: 20px;"></i>
                            <span><?php echo htmlspecialchars($attraction['public_transportation_info']); ?></span>
                        </li>
                        <?php else: ?>
                        <li class="d-flex align-items-start mb-3">
                            <i class="fas fa-bus text-primary me-3 mt-1" style="min-width: 20px;"></i>
                            <span>Public transportation options are available nearby</span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if(!empty($attraction['physical_requirements'])): ?>
                        <li class="d-flex align-items-start mb-3">
                            <i class="fas fa-walking text-primary me-3 mt-1" style="min-width: 20px;"></i>
                            <span><?php echo htmlspecialchars($attraction['physical_requirements']); ?></span>
                        </li>
                        <?php else: ?>
                        <li class="d-flex align-items-start mb-3">
                            <i class="fas fa-walking text-primary me-3 mt-1" style="min-width: 20px;"></i>
                            <span>Suitable for all physical fitness levels</span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if(!empty($additional_info)): 
                            foreach($additional_info as $info): 
                        ?>
                            <li class="d-flex align-items-start mb-3">
                                <i class="fas fa-<?php echo htmlspecialchars($info['icon']); ?> text-<?php echo htmlspecialchars($info['color'] ?? 'primary'); ?> me-3 mt-1" style="min-width: 20px;"></i>
                                <span><?php echo htmlspecialchars($info['content']); ?></span>
                            </li>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </ul>
                </div>
            </div>

            <!-- Cancellation Policy -->
            <?php if($is_bookable || !empty($attraction['cancellation_policy'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Cancellation policy</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($attraction['cancellation_policy'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($attraction['cancellation_policy'])); ?></p>
                    <?php else: ?>
                        <p>All sales are final and incur 100% cancellation penalties</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Frequently Asked Questions -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Frequently asked questions</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="accordionFAQ">
                        <?php if(!empty($faqs)): 
                            foreach($faqs as $index => $faq): 
                        ?>
                            <div class="accordion-item mb-2 border">
                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $index; ?>">
                                        <?php echo htmlspecialchars($faq['question']); ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#accordionFAQ">
                                    <div class="accordion-body">
                                        <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        How do I get to <?php echo htmlspecialchars($attraction['name']); ?>?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionFAQ">
                                    <div class="accordion-body">
                                        Please check the location information and public transportation details above. You can also use popular map applications to get directions.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        <?php if($is_bookable): ?>
                                            Who and when do I pay?
                                        <?php else: ?>
                                            Is there an entrance fee?
                                        <?php endif; ?>
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionFAQ">
                                    <div class="accordion-body">
                                        <?php if($is_bookable): ?>
                                            Payment is made through our secure payment system during the booking process.
                                        <?php else: ?>
                                            This attraction is free to visit. No entrance fee is required.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($is_bookable): ?>
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Where can I find my Activity booking?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionFAQ">
                                    <div class="accordion-body">
                                        You can find your booking details in your account under "My Bookings" after logging in.
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Accessibility Information -->
            <?php if(!empty($attraction['accessibility_info'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Accessibility</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($attraction['accessibility_info'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Visitor Reviews -->
            <?php if(count($reviews) > 0 || $reviewStats['count'] > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Visitor Reviews (<?php echo $reviewStats['count']; ?>)</h5>
                    <div>
                        <span class="text-warning mr-2">
                            <?php
                                $rating = $reviewStats['average'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                            ?>
                            <strong><?php echo $reviewStats['average']; ?></strong>
                        </span>
                        <a href="#" class="btn btn-sm btn-outline-primary">See All Reviews</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    // Display reviews using the function
                    echo displayAttractionReviews($reviews);
                    ?>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="mt-3">
                        <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">Write a Review</a>
                    </div>
                    <?php else: ?>
                    <div class="mt-3">
                        <p class="text-muted small">Please <a href="login.php">log in</a> to write a review.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <?php if($is_bookable): ?>
            <!-- Book Now Card - Only shown for attractions with entrance fee > 0 -->
            <div class="card mb-4 sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-body">
                    <!-- Converted Price Display -->
                    <h5 class="card-title">
                        From <?php 
                            $entrance_fee = floatval($attraction['entrance_fee']);  
                            $currency = htmlspecialchars($attraction['currency']); 
                            $userCurrency = getCurrentCurrency();
                            echo displayConvertedPrice($entrance_fee, $currency, $userCurrency);  
                        ?>
                    </h5>
                    <p class="text-muted small mb-3">per person</p>
                    
                    <div class="form-group mb-3">
                        <label for="booking-date" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="booking-date">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="booking-time" class="form-label">Select Time</label>
                        <select class="form-control" id="booking-time">
                            <?php
                            // Parse opening hours to generate time slots
                            $opening_hours = $attraction['opening_hours'] ?? '9:00 AM - 5:00 PM';
                            
                            // Extract opening and closing times
                            if (preg_match('/(\d+:\d+\s*[AP]M)\s*-\s*(\d+:\d+\s*[AP]M)/i', $opening_hours, $matches)) {
                                $opening_time = date_create_from_format('g:i A', trim($matches[1]));
                                $closing_time = date_create_from_format('g:i A', trim($matches[2]));
                                
                                // If parsing failed, use defaults
                                if (!$opening_time || !$closing_time) {
                                    $opening_time = date_create_from_format('g:i A', '9:00 AM');
                                    $closing_time = date_create_from_format('g:i A', '5:00 PM');
                                }
                                
                                // Set the latest start time to 1 hour before closing
                                $latest_start = clone $closing_time;
                                date_modify($latest_start, "-1 hour");
                                
                                // Generate time slots at 1-hour intervals
                                $current_time = clone $opening_time;
                                
                                while ($current_time <= $latest_start) {
                                    $slot_time = $current_time->format('g:i A');
                                    echo "<option value=\"{$slot_time}\">{$slot_time}</option>\n";
                                    date_modify($current_time, '+1 hour');
                                }
                            } else {
                                // Fallback options if opening hours format is not recognized
                                echo "<option>Morning (9:00 AM)</option>\n";
                                echo "<option>Afternoon (1:00 PM)</option>\n";
                                echo "<option>Evening (5:00 PM)</option>\n";
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">Opening hours: <?php echo htmlspecialchars($opening_hours); ?></small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="booking-guests" class="form-label">Number of Guests</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="decrease-guests">-</button>
                            <input type="number" class="form-control text-center" id="booking-guests" value="2" min="1">
                            <button class="btn btn-outline-secondary" type="button" id="increase-guests">+</button>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary w-100 btn-lg mt-4" id="add-to-cart-btn">Book Now</button>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                    <?php 
                    $is_favorite = isset($_SESSION['user_id']) ? 
                        isInFavorites($conn, $_SESSION['user_id'], 'attraction', $attraction_id) : 
                        false;

                    echo renderFavoriteButton(
                        'attraction', 
                        $attraction_id, 
                        $is_favorite, 
                        $is_favorite ? 'btn-danger' : 'btn-outline-secondary', 
                        'sm', 
                        true
                    );
                    ?>
                        <button class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Information Card - For free attractions -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title text-success mb-3">Free Entry</h5>
                    <p>This attraction is free to visit. No booking is required.</p>
                    <?php 
                    $is_favorite = isset($_SESSION['user_id']) ? 
                        isInFavorites($conn, $_SESSION['user_id'], 'attraction', $attraction_id) : 
                        false;

                    echo renderFavoriteButton(
                        'attraction', 
                        $attraction_id, 
                        $is_favorite, 
                        $is_favorite ? 'btn-danger' : 'btn-outline-secondary', 
                        'sm', 
                        true
                    );
                    ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        
                        <button class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Visit Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Plan Your Visit</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php if(!empty($attraction['opening_hours'])): ?>
                        <li class="d-flex mb-3">
                            <i class="far fa-clock text-primary me-3" style="font-size: 1.2rem;"></i>
                            <div>
                                <strong>Opening Hours</strong><br>
                                <?php echo htmlspecialchars($attraction['opening_hours']); ?>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li class="d-flex mb-3">
                            <i class="fas fa-ticket-alt text-primary me-3" style="font-size: 1.2rem;"></i>
                            <div>
                                <strong>Admission</strong><br>
                                <?php 
                                $entrance_fee = floatval($attraction['entrance_fee']);
                                $currency = htmlspecialchars($attraction['currency']);

                                if ($entrance_fee > 0) {
                                    $userCurrency = getCurrentCurrency();
                                    echo displayConvertedPrice($entrance_fee, $currency, $userCurrency);
                                } else {
                                    echo 'Free';
                                }
                                ?>
                            </div>
                        </li>
                        
                        <?php if(!empty($attraction['estimated_time_minutes'])): ?>
                        <li class="d-flex mb-3">
                            <i class="far fa-hourglass text-primary me-3" style="font-size: 1.2rem;"></i>
                            <div>
                                <strong>Suggested Duration</strong><br>
                                <?php
                                    $hours = floor($attraction['estimated_time_minutes'] / 60);
                                    $minutes = $attraction['estimated_time_minutes'] % 60;
                                    
                                    if ($hours > 0) {
                                        echo $hours . ' hour' . ($hours > 1 ? 's' : '');
                                        if ($minutes > 0) {
                                            echo ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                        }
                                    } else {
                                        echo $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                    }
                                ?>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php 
                    $attraction_name = $attraction['name'];
                    echo renderItineraryButton('attraction', $attraction_id, $attraction_name, 'btn-outline-primary w-100');
                    ?>
                </div>
            </div>
            
            <!-- Similar Attractions -->
            <?php if(count($similar_attractions) > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Similar Attractions</h5>
                </div>
                <div class="card-body">
                    <?php foreach($similar_attractions as $similar): ?>
                    <div class="d-flex mb-3">
                        <?php if(!empty($similar['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($similar['image_url']); ?>" class="rounded me-3" style="width: 70px; height: 70px; object-fit: cover;" alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        <?php else: ?>
                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-1"><a href="attraction-detail.php?id=<?php echo $similar['attraction_id']; ?>"><?php echo htmlspecialchars($similar['name']); ?></a></h6>
                            <?php if(!empty($similar['category'])): ?>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($similar['category']); ?></span>
                            <?php endif; ?>
                            <?php if(!empty($similar['entrance_fee'])): ?>
                                <div class="small text-muted mt-1">
                                    <?php if(floatval($similar['entrance_fee']) > 0): ?>
                                        <?php 
                                            $similar_fee = floatval($similar['entrance_fee']);
                                            echo displayConvertedPrice($similar_fee, $currency, $userCurrency);
                                        ?>
                                    <?php else: ?>
                                        Free
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Back to Destination -->
            <div class="card mb-4">
                <div class="card-body">
                    <a href="destination-detail.php?id=<?php echo $destination_id; ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-arrow-left me-2"></i> Back to <?php echo htmlspecialchars($attraction['location_name']); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<?php if(isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="submit_review.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="attraction_id" value="<?php echo $attraction_id; ?>">
                    
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="rating">
                            <input type="radio" name="rating" value="5" id="rating-5"><label for="rating-5"></label>
                            <input type="radio" name="rating" value="4" id="rating-4"><label for="rating-4"></label>
                            <input type="radio" name="rating" value="3" id="rating-3"><label for="rating-3"></label>
                            <input type="radio" name="rating" value="2" id="rating-2"><label for="rating-2"></label>
                            <input type="radio" name="rating" value="1" id="rating-1"><label for="rating-1"></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review-title">Title</label>
                        <input type="text" class="form-control" id="review-title" name="title" placeholder="Summarize your experience">
                    </div>
                    
                    <div class="form-group">
                        <label for="review-comment">Your Review</label>
                        <textarea class="form-control" id="review-comment" name="comment" rows="4" placeholder="Tell others about your experience"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="visit-date">When did you visit?</label>
                        <input type="month" class="form-control" id="visit-date" name="visit_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom styles for star rating -->
<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.rating > input {
    display: none;
}
.rating > label {
    position: relative;
    width: 1.1em;
    font-size: 2rem;
    color: #FFD700;
    cursor: pointer;
}
.rating > label::before {
    content: "\2605";
    position: absolute;
    opacity: 0;
}
.rating > label:hover:before,
.rating > label:hover ~ label:before {
    opacity: 1 !important;
}
.rating > input:checked ~ label:before {
    opacity: 1;
}
.rating:hover > input:checked ~ label:before {
    opacity: 0.4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            // Get booking details
            const bookingDate = document.getElementById('booking-date').value;
            const bookingTime = document.getElementById('booking-time').value;
            const guestCount = parseInt(document.getElementById('booking-guests').value);
                    
            // Get attraction details
            const attractionId = <?php echo $attraction_id; ?>;
            const attractionName = "<?php echo addslashes($attraction['name']); ?>";
            const attractionPrice = <?php echo floatval($attraction['entrance_fee']); ?>;
            const attractionCurrency = "<?php echo addslashes($attraction['currency'] ?? 'THB'); ?>";
            const attractionImage = "<?php echo addslashes($attraction['image_url'] ?? 'assets/images/placeholder.jpg'); ?>";
            const openingHours = "<?php echo isset($opening_hours) ? addslashes($opening_hours) : ''; ?>";
                    
            // Create cart item object
            const cartItem = {
                id: attractionId,
                type: 'attraction',
                name: attractionName,
                price: attractionPrice,
                currency: attractionCurrency,
                image: attractionImage,
                openingHours: openingHours,
                date: bookingDate,
                time: bookingTime,
                guests: guestCount,
                subtotal: attractionPrice * guestCount
            };
                    
            // Get existing cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
                    
            // Check if item already exists in cart
            const existingItemIndex = cart.findIndex(item => item.id === attractionId);
                    
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
            const cartCount = cart.length;
            
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
                        <div>${attractionName} - ${guestCount} guest(s)</div>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <a href="cart.php" style="color: white; text-decoration: underline;">View Cart (${cartCount})</a>
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
            
            // Optionally update any cart indicator in the header
            if (document.querySelector('.cart-count')) {
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = cartCount;
                });
            }
        });
    }
    
    // Set minimum date to today if booking date element exists
    const bookingDateEl = document.getElementById('booking-date');
    if (bookingDateEl) {
        const today = new Date().toISOString().split('T')[0];
        bookingDateEl.min = today;
        bookingDateEl.value = today;
    }
    
    // Guest counter functionality
    const guestInput = document.getElementById('booking-guests');
    const decreaseBtn = document.getElementById('decrease-guests');
    const increaseBtn = document.getElementById('increase-guests');
    
    if (guestInput && decreaseBtn && increaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(guestInput.value);
            if (currentValue > 1) {
                guestInput.value = currentValue - 1;
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(guestInput.value);
            guestInput.value = currentValue + 1;
        });
    }
});
</script>
<?php outputItineraryScript(); ?>
<?php outputFavoriteScript(); ?>
<?php include 'footer.php'; ?>