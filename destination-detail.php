<?php
// Start session
session_start(); 

require_once 'includes/db_connect.php';
require_once 'currency_functions.php'; 
require_once 'includes/favorites_functions.php';

// Get destination ID from URL
$destination_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($destination_id <= 0) {
    // Invalid ID, redirect to destinations page
    header("Location: destinations.php");
    exit();
}

// Get destination details
$sql = "SELECT * FROM Destinations WHERE destination_id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Destination not found, redirect to destinations page
    header("Location: destinations.php");
    exit();
}

$destination = $result->fetch_assoc();

// Get attractions for this destination
$sql = "SELECT * FROM Attractions WHERE destination_id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$attractions_result = $stmt->get_result();

$attractions = array();
if ($attractions_result->num_rows > 0) {
    while($row = $attractions_result->fetch_assoc()) {
        $attractions[] = $row;
    }
}

$images = array();
// Add main image to images array
if (!empty($destination['main_image_url'])) {
    $images[] = [
        'image_url' => $destination['main_image_url'],
        'caption' => $destination['location_name'],
        'is_main' => 1
    ];

     // Add some additional images from the assets folder based on destination ID
    // This is a fallback if you don't have a Destination_Images table
    $assetPath = "assets/images/destinations/{$destination_id}/";
    
    // Check if directory exists
    if (is_dir($assetPath)) {
        // Get all jpg, jpeg, png files from the directory
        $imageFiles = glob($assetPath . "*.{jpg,jpeg,png}", GLOB_BRACE);
        
        foreach ($imageFiles as $image) {
            // Don't add main image again if it's in the array
            if (strpos($image, basename($destination['main_image_url'])) === false) {
                $images[] = [
                    'image_url' => $image,
                    'caption' => $destination['location_name'] . ' - ' . basename($image),
                    'is_main' => 0
                ];
            }
        }
    }
}
    
// Get travel tips for this destination
$sql = "SELECT * FROM Travel_Tips WHERE destination_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $destination_id);
$stmt->execute();
$tips_result = $stmt->get_result();

$tips = array();
if ($tips_result->num_rows > 0) {
    while($row = $tips_result->fetch_assoc()) {
        $tips[] = $row;
    }
}

// Get blogs related to this destination
$blogs = array();
// First check if the Blogs table exists
try {
    // Check if Blogs table exists before attempting to query it
    $table_check = $conn->query("SHOW TABLES LIKE 'Blogs'");
    
    if ($table_check->num_rows > 0) {
        // The table exists, now check what columns it has
        $column_check = $conn->query("SHOW COLUMNS FROM Blogs");
        $columns = [];
        while ($col = $column_check->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        // Build query based on available columns
        if (in_array('destination_id', $columns) && in_array('content', $columns)) {
            try {
                $sql = "SELECT * FROM Blogs WHERE destination_id = ? OR content LIKE ? LIMIT 2";
                $stmt = $conn->prepare($sql);
                $destination_name = '%' . $destination['location_name'] . '%';
                $stmt->bind_param("is", $destination_id, $destination_name);
                $stmt->execute();
                $blogs_result = $stmt->get_result();
                
                if ($blogs_result->num_rows > 0) {
                    while($row = $blogs_result->fetch_assoc()) {
                        $blogs[] = $row;
                    }
                }
            } catch (Exception $e) {
                // Ignore errors in blog query
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist or other error, continue with empty blogs array
}

// Get FAQs for this destination
$faqs = array();
try {
    $sql = "SELECT * FROM FAQs WHERE destination_id = ? ORDER BY display_order";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $faqs_result = $stmt->get_result();

    if ($faqs_result->num_rows > 0) {
        while($row = $faqs_result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }
} catch (Exception $e) {
    // If error occurs, continue with empty faqs array
}

// Get recommended stay duration
$recommended_duration = 5; // Default value
try {
    $sql = "SELECT recommended_duration FROM Destination_Details WHERE destination_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $duration_result = $stmt->get_result();
    $duration_info = $duration_result->fetch_assoc();

    if ($duration_result->num_rows > 0) {
        $recommended_duration = intval($duration_info['recommended_duration'] ?? 5);
    }
} catch (Exception $e) {
    // If error occurs, keep default value
}

// Get average cost per day
$avg_cost = 0;
$cost_currency = $destination['currency'] ?? 'USD';
try {
    $sql = "SELECT avg_cost_per_day, currency FROM Destination_Details WHERE destination_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $cost_result = $stmt->get_result();
    
    if ($cost_result->num_rows > 0) {
        $cost_info = $cost_result->fetch_assoc();
        $avg_cost = floatval($cost_info['avg_cost_per_day'] ?? 0);
        $cost_currency = $cost_info['currency'] ?? $destination['currency'] ?? 'USD';
    }
} catch (Exception $e) {
    // If error occurs, keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($destination['location_name']); ?> - Travel Itinerary Planner</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Base styles */
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
        color: #333;
    }

    /* Typography and text elements */
    .destination-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .destination-subtitle, .blog-meta {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }

    .attraction-title, .blog-title, .faq-question {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    /* Layout components */
    .breadcrumb {
        background-color: transparent;
        padding: 0.5rem 0;
        font-size: 0.9rem;
    }

    .destination-header {
        position: relative;
        padding: 1.5rem 0;
    }

    /* Common card styling */
    .card-container {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .attraction-card {
        border: none;
        height: 100%;
    }

    .faq-section {
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Images */
    .hero-image-container {
        position: relative;
        height: 400px;
        overflow: hidden;
        border-radius: 8px;
        margin-bottom: 2rem;
    }

    .hero-image, .attraction-img {
        width: 100%;
        object-fit: cover;
    }

    .hero-image {
        height: 100%;
    }

    .attraction-img {
        height: 180px;
    }

    .blog-img {
        width: 200px;
        object-fit: cover;
    }

    /* UI Components */
    .info-badge {
        background-color: #e9ecef;
        border-radius: 50px;
        padding: 0.25rem 0.75rem;
        margin-right: 0.5rem;
        font-size: 0.9rem;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .rating {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .star-rating {
        color: #ffc107;
        margin-right: 0.5rem;
    }

    /* Blog specific */
    .blog-card {
        display: flex;
    }

    .blog-content {
        padding: 1rem;
    }

    /* FAQ specific */
    .faq-item {
        border-bottom: 1px solid #dee2e6;
        padding: 1rem 0;
    }

    .faq-question {
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Filter section */
    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .filter-btn, .price-btn {
        padding: 6px 12px;
        min-width: 80px;
        text-align: center;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .blog-card {
            flex-direction: column;
        }
        
        .blog-img {
            width: 100%;
            height: 180px;
        }

        .filter-btn, .price-btn {
            min-width: auto;
            flex: 1 0 auto;
            margin-bottom: 5px;
        }
    }
    </style>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="destinations.php">Destinations</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($destination['location_name']); ?></li>
            </ol>
        </nav>

        <!-- Destination Header -->
        <div class="destination-header">
            <h1 class="destination-title"><?php echo htmlspecialchars($destination['location_name']); ?></h1>
            <p class="destination-subtitle">
                <?php 
                    echo htmlspecialchars($destination['country']);
                    if(!empty($destination['city'])) {
                        echo ' - ' . htmlspecialchars($destination['city']);
                    }
                ?>
            </p>
            
            <!-- Info Badges -->
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <?php if(!empty($destination['best_time_to_visit'])): ?>
                        <span class="info-badge">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Best time: <?php echo htmlspecialchars($destination['best_time_to_visit']); ?>
                        </span>
                    <?php endif; ?>
                
                    <?php if(!empty($destination['climate'])): ?>
                        <span class="info-badge">
                            <i class="fas fa-cloud-sun mr-1"></i>
                            <?php echo htmlspecialchars($destination['climate']); ?>
                        </span>
                    <?php endif; ?>
                
                    <?php if(!empty($recommended_duration)): ?>
                        <span class="info-badge">
                            <i class="fas fa-clock mr-1"></i>
                            Recommended: <?php echo $recommended_duration; ?> days
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Favorite button -->
                <?php
                $is_favorite = isset($_SESSION['user_id']) ?
                    isInFavorites($conn, $_SESSION['user_id'], 'destination', $destination['destination_id']) :
                    false;
                
                echo renderFavoriteButton(
                    'destination',
                    $destination['destination_id'],
                    $is_favorite,
                    'btn-outline-danger',
                    'md',
                    true
                );
                ?>
</div>

        <!-- Hero Image -->
        <div class="hero-image-container">
            <img src="<?php echo !empty($destination['main_image_url']) ? $destination['main_image_url'] : 'images/placeholder.jpg'; ?>" class="hero-image" alt="<?php echo htmlspecialchars($destination['location_name']); ?>">
            <button class="btn btn-sm btn-dark position-absolute" style="bottom: 15px; right: 15px;" data-toggle="modal" data-target="#galleryModal">
                <i class="fas fa-camera mr-1"></i> Gallery
            </button>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Description -->
                <div class="card mb-4">
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($destination['description'])); ?></p>
                    </div>
                </div>

                <!-- The Best of Section -->
                <h2 class="section-title">The best of <?php echo htmlspecialchars($destination['location_name']); ?></h2>
                <div class="row mb-4">
                    <?php 
                    $top_attractions = array_slice($attractions, 0, 3); // Get top 3 attractions
                    foreach($top_attractions as $attraction): 
                    ?>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="card h-100">
                                <?php if(!empty($attraction['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($attraction['image_url']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($attraction['name']); ?>">
                                <?php else: ?>
                                    <div class="bg-light" style="height: 150px;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- All Things to Do -->
                <h2 class="section-title">All things to do in <?php echo htmlspecialchars($destination['location_name']); ?></h2>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <h6>Filter by Category</h6>
                    <div class="d-flex flex-wrap mb-3" id="categoryFilter">
                        <button class="btn btn-sm btn-primary mr-1 mb-1" data-filter="all">All</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Religious">Temples</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Wildlife">Wildlife</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Beach">Beaches</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Historical">Historical</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Cultural">Cultural</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Shopping">Shopping</button>
                        <button class="btn btn-sm btn-outline-primary mr-1 mb-1" data-filter="Nature">Nature</button>
                    </div>
                    
                    <h6>Price Range</h6>
                    <div class="d-flex flex-wrap mb-3" id="priceFilter">
                        <button class="btn btn-sm btn-success mr-1 mb-1" data-filter="all">All Prices</button>
                        <button class="btn btn-sm btn-outline-success mr-1 mb-1" data-filter="free">Free</button>
                        <button class="btn btn-sm btn-outline-success mr-1 mb-1" data-filter="paid">Paid</button>
                    </div>
                    
                    <button id="resetFilters" class="btn btn-sm btn-secondary">
                        <i class="fas fa-undo-alt mr-1"></i> Reset All Filters
                    </button>
                </div>
                
                <!-- Attractions Grid -->
                <div class="row">
                    <?php foreach($attractions as $attraction): ?>
                        <!-- Make sure each attraction in your attractions grid follows this structure -->
                        <div class="col-md-4 mb-4">
                            <div class="attraction-card">
                                <img src="<?php echo htmlspecialchars($attraction['image_url']); ?>" class="attraction-img w-100" alt="<?php echo htmlspecialchars($attraction['name']); ?>">
                                <div class="card-body">
                                    <h5 class="attraction-title"><?php echo htmlspecialchars($attraction['name']); ?></h5>
                                    <span class="attraction-category"><?php echo htmlspecialchars($attraction['category']); ?></span>
                                        <?php echo htmlspecialchars($attraction['category']); ?>
                                    </span>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($attraction['description'], 0, 100))); ?>...</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-success font-weight-bold">
                                        <?php 
                                            $entrance_fee = floatval($attraction['entrance_fee']);  
                                            $currency = htmlspecialchars($destination['currency']); 

                                            if ($entrance_fee > 0) {
                                                $userCurrency = getCurrentCurrency();
                                                echo displayConvertedPrice($entrance_fee, $currency, $userCurrency);
                                            } else {
                                                echo 'Free';
                                            }
                                        ?>
                                        </span>
                                        <a href="attraction-detail.php?id=<?php echo $attraction['attraction_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Travel Blogs and Guides -->
                <h2 class="section-title">Travel Blogs and guides for <?php echo htmlspecialchars($destination['location_name']); ?></h2>
                
                <?php if(count($blogs) > 0): ?>
                    <?php foreach($blogs as $blog): ?>
                        <div class="blog-card">
                            <?php if(!empty($blog['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" class="blog-img" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                            <?php else: ?>
                                <div class="bg-light" style="width: 200px;"></div>
                            <?php endif; ?>
                            <div class="blog-content">
                                <h5 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h5>
                                <div class="blog-meta">
                                    <span><?php echo htmlspecialchars($blog['category_name'] ?? 'Travel'); ?></span>
                                    <?php 
                                        $tags = !empty($blog['tags']) ? explode(',', $blog['tags']) : [];
                                        foreach($tags as $tag):
                                    ?>
                                        • <span><?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <p><?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 100)); ?>...</p>
                                <div class="d-flex align-items-center">
                                    <?php if(!empty($blog['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($blog['profile_image']); ?>" class="rounded-circle" style="width: 30px; height: 30px; object-fit: cover;" alt="Author">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="ml-2"><?php echo htmlspecialchars($blog['username'] ?? 'Travel Expert'); ?></span>
                                    <a href="blog-detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn btn-sm btn-link ml-auto">Read more</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No blog posts available for this destination yet.</p>
                <?php endif; ?>
                
                <!-- FAQs Section -->
                <div class="faq-section">
                    <h2 class="section-title">FAQs about <?php echo htmlspecialchars($destination['location_name']); ?></h2>
                    
                    <?php if(count($faqs) > 0): ?>
                        <?php foreach($faqs as $index => $faq): ?>
                            <div class="faq-item">
                                <div class="faq-question" data-toggle="collapse" data-target="#faq<?php echo $index; ?>">
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="collapse" id="faq<?php echo $index; ?>">
                                    <div class="pt-3">
                                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default FAQs when none are in the database -->
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq1">
                                <span>What is <?php echo htmlspecialchars($destination['location_name']); ?> Best Known for?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="collapse" id="faq1">
                                <div class="pt-3">
                                    <p><?php echo htmlspecialchars($destination['description'] ? substr($destination['description'], 0, 200) : $destination['location_name'] . ' is a popular travel destination.'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" data-toggle="collapse" data-target="#faq2">
                                <span>When is the Best Time to Visit <?php echo htmlspecialchars($destination['location_name']); ?>?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="collapse" id="faq2">
                                <div class="pt-3">
                                    <p>The best time to visit <?php echo htmlspecialchars($destination['location_name']); ?> is during <?php echo htmlspecialchars($destination['best_time_to_visit'] ?? 'the dry season'); ?> when the weather is most favorable for outdoor activities.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Info Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Best time to visit</h5>
                        <p class="mb-3"><?php echo htmlspecialchars($destination['best_time_to_visit'] ?? 'Year-round'); ?></p>
                        
                        <h5 class="card-title">Recommended stay duration</h5>
                        <p class="mb-3"><?php echo $recommended_duration; ?> days</p>
                        
                        <h5 class="card-title">Avg cost per day</h5>
                        <p class="font-weight-bold">
                            <?php 
                                if($avg_cost > 0) {
                                    echo number_format($avg_cost, 2) . ' ' . htmlspecialchars($cost_currency);
                                } else {
                                    echo 'Contact for pricing';
                                }
                            ?>
                        </p>
                        <p class="text-muted small">Per person, per night excl. flights</p>
                        
                        <button class="btn btn-primary btn-block mt-4" data-toggle="modal" data-target="#galleryModal">
                            <i class="fas fa-map-marker-alt mr-2"></i> Gallery
                        </button>
                    </div>
                </div>
                
                <!-- Travel Tips -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Travel Tips</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($tips) > 0): ?>
                            <div class="accordion" id="travelTipsAccordion">
                                <?php foreach($tips as $index => $tip): ?>
                                    <div class="mb-3">
                                        <h6 class="font-weight-bold">
                                            <i class="fas fa-<?php 
                                                switch($tip['tip_type']) {
                                                    case 'safety': echo 'shield-alt'; break;
                                                    case 'transportation': echo 'bus'; break;
                                                    case 'food': echo 'utensils'; break;
                                                    case 'culture': echo 'landmark'; break;
                                                    case 'budget': echo 'money-bill-wave'; break;
                                                    default: echo 'info-circle';
                                                }
                                            ?> mr-2"></i>
                                            <?php echo htmlspecialchars($tip['title']); ?>
                                        </h6>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars(substr($tip['content'], 0, 150))); ?>
                                        <?php if(strlen($tip['content']) > 150): ?>
                                            ... <a href="#" data-toggle="collapse" data-target="#tipCollapse<?php echo $index; ?>" aria-expanded="false">Read more</a>
                                            <div class="collapse" id="tipCollapse<?php echo $index; ?>">
                                                <?php echo nl2br(htmlspecialchars(substr($tip['content'], 150))); ?>
                                            </div>
                                        <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No travel tips have been added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

    <!-- Gallery Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars($destination['location_name']); ?> Gallery</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php if(count($images) > 0): ?>
                            <?php foreach($images as $image): ?>
                                <div class="col-md-4 col-6 mb-3">
                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" class="img-fluid rounded cursor-pointer" style="height: 180px; width: 100%; object-fit: cover;" data-toggle="modal" data-target="#imageModal" data-img="<?php echo htmlspecialchars($image['image_url']); ?>" data-caption="<?php echo htmlspecialchars($image['caption'] ?? $destination['location_name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center">No gallery images available for this destination.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalCaption"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Destination Image">
                </div>
            </div>
        </div>
    </div>
</div>
    <?php outputFavoriteScript(); ?>
    <?php include 'footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Image modal script
        $('#imageModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var imageUrl = button.data('img');
            var imageCaption = button.data('caption');
            
            var modal = $(this);
            modal.find('#modalImage').attr('src', imageUrl);
            modal.find('#imageModalCaption').text(imageCaption);
        });

        // FAQ accordion functionality
        $('.faq-question').on('click', function() {
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Get all attraction containers (adjust selector as needed based on your HTML structure)
            const attractions = document.querySelectorAll('.col-md-4.mb-4');
            
            // If no attractions found with that selector, try alternatives
            if (attractions.length === 0) {
                // Try alternative selectors for attraction containers
                const attractionsAlt = document.querySelectorAll('.attraction-card, .card, [class*="col-"]:has(img)');
                if (attractionsAlt.length > 0) {
                    // Use this selector if found
                    attractions = attractionsAlt;
                }
            }
            
            if (!attractions || attractions.length === 0) {
                console.log('No attraction elements found to filter');
                return; // Exit if no attractions found
            }
            
            // Category filter buttons
            const categoryButtons = document.querySelectorAll('#categoryFilter button');
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active state
                    categoryButtons.forEach(btn => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    });
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                    
                    // Apply filters
                    filterAttractions();
                });
            });
            
            // Price filter buttons
            const priceButtons = document.querySelectorAll('#priceFilter button');
            priceButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active state
                    priceButtons.forEach(btn => {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-success');
                    });
                    this.classList.remove('btn-outline-success');
                    this.classList.add('btn-success');
                    
                    // Apply filters
                    filterAttractions();
                });
            });
            
            // Reset filters button
            document.getElementById('resetFilters')?.addEventListener('click', function() {
                // Reset category buttons
                categoryButtons.forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });
                categoryButtons[0].classList.remove('btn-outline-primary');
                categoryButtons[0].classList.add('btn-primary');
                
                // Reset price buttons
                priceButtons.forEach(btn => {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-success');
                });
                priceButtons[0].classList.remove('btn-outline-success');
                priceButtons[0].classList.add('btn-success');
                
                // Show all attractions
                attractions.forEach(item => {
                    item.style.display = '';
                });
                
                // Remove any "no results" message
                const noResults = document.getElementById('noResultsMessage');
                if (noResults) noResults.remove();
            });
            
            // Function to filter attractions based on active buttons
            function filterAttractions() {
                const selectedCategory = document.querySelector('#categoryFilter .btn-primary')?.getAttribute('data-filter') || 'all';
                const selectedPrice = document.querySelector('#priceFilter .btn-success')?.getAttribute('data-filter') || 'all';
                
                let visibleCount = 0;
                
                attractions.forEach(attraction => {
                    // Try to find category text
                    let categoryText = '';
                    // Look for category in various elements
                    const categoryEl = attraction.querySelector('.category') || 
                                    attraction.querySelector('[class*="category"]') ||
                                    attraction.querySelector('span:not(:empty)');
                    
                    if (categoryEl) {
                        categoryText = categoryEl.textContent.trim();
                    } else {
                        // Try to extract from the general text content
                        categoryText = attraction.textContent;
                    }
                    
                    // Try to find price text
                    let priceText = '';
                    const priceEl = attraction.querySelector('.price') || 
                                    attraction.querySelector('[class*="price"]') ||
                                    attraction.querySelector('.text-success') ||
                                    attraction.querySelector('*:contains("SGD")') ||
                                    attraction.querySelector('*:contains("Free")');
                    
                    if (priceEl) {
                        priceText = priceEl.textContent.trim();
                    } else {
                        // Try to extract from the general text content
                        priceText = attraction.textContent;
                    }
                    
                    // Check if it matches selected category
                    const matchesCategory = 
                        selectedCategory === 'all' || 
                        categoryText.includes(selectedCategory);
                    
                    // Check if it matches selected price
                    const isFree = priceText.toLowerCase().includes('free');
                    const matchesPrice = 
                        selectedPrice === 'all' || 
                        (selectedPrice === 'free' && isFree) || 
                        (selectedPrice === 'paid' && !isFree && (priceText.includes('SGD') || priceText.includes('$')));
                    
                    // Show or hide based on filters
                    if (matchesCategory && matchesPrice) {
                        attraction.style.display = '';
                        visibleCount++;
                    } else {
                        attraction.style.display = 'none';
                    }
                });
                
                // Show a message if no results
                let noResultsEl = document.getElementById('noResultsMessage');
                if (visibleCount === 0) {
                    if (!noResultsEl) {
                        noResultsEl = document.createElement('div');
                        noResultsEl.id = 'noResultsMessage';
                        noResultsEl.className = 'alert alert-info col-12 text-center my-3';
                        noResultsEl.textContent = 'No attractions match your selected filters.';
                        
                        // Find a good spot to insert the message
                        const parentContainer = attractions[0].parentNode;
                        parentContainer.appendChild(noResultsEl);
                    }
                } else if (noResultsEl) {
                    noResultsEl.remove();
                }
            }
});  
        </script>
</body>
</html>
