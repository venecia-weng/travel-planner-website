<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$country_filter = isset($_GET['country']) ? trim($_GET['country']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build the query based on filters
$query = "SELECT * FROM Destinations WHERE status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (location_name LIKE ? OR country LIKE ? OR city LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($country_filter)) {
    $query .= " AND country = ?";
    $params[] = $country_filter;
    $types .= "s";
}

// Get available countries for filter
$countries_query = "SELECT DISTINCT country FROM Destinations ORDER BY country";
$countries_result = $conn->query($countries_query);
$countries = [];
if ($countries_result && $countries_result->num_rows > 0) {
    while ($row = $countries_result->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}

// Execute the main query with filters
$query .= " ORDER BY country, city";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$destinations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Function to get attractions count for a destination
function getAttractionsCount($conn, $destination_id) {
    $sql = "SELECT COUNT(*) as count FROM Attractions WHERE destination_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'] ?? 0;
}

// Fallback image mapping
$image_map = [
    'Thailand' => 'destination01.jpg',
    'Phuket' => 'destination02.jpg',
    'Bangkok' => 'destination03.jpg',
    'Chiang Mai' => 'destination04.jpg',
    'Default' => 'destination05.jpg'
];

// Page title
$page_title = 'Destinations - Travel Itinerary Planner';

// Include header
include 'header.php';
?>

<!-- Hero Section -->
<div class="hero-banner py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4">Explore Destinations</h1>
                <p class="lead">Discover amazing places and plan your next adventure</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<section class="py-4 bg-white border-bottom">
    <div class="container">
        <form method="get" action="destinations.php" class="row align-items-end">
            <div class="col-md-4 mb-3 mb-md-0">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search destinations..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="country" class="form-label">Country</label>
                <select class="form-control" id="country" name="country">
                    <option value="">All Countries</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo htmlspecialchars($country); ?>" <?php echo ($country === $country_filter) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($country); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label for="category" class="form-label">Category</label>
                <select class="form-control" id="category" name="category">
                    <option value="">All Categories</option>
                    <option value="Beach" <?php echo ($category_filter === 'Beach') ? 'selected' : ''; ?>>Beach</option>
                    <option value="Mountain" <?php echo ($category_filter === 'Mountain') ? 'selected' : ''; ?>>Mountain</option>
                    <option value="City" <?php echo ($category_filter === 'City') ? 'selected' : ''; ?>>City</option>
                    <option value="Historical" <?php echo ($category_filter === 'Historical') ? 'selected' : ''; ?>>Historical</option>
                    <option value="Adventure" <?php echo ($category_filter === 'Adventure') ? 'selected' : ''; ?>>Adventure</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</section>

<!-- Destinations Section -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($search) || !empty($country_filter) || !empty($category_filter)): ?>
            <div class="mb-4">
                <h2>
                    <?php if (!empty($search)): ?>
                        Search results for "<?php echo htmlspecialchars($search); ?>"
                    <?php elseif (!empty($country_filter)): ?>
                        Destinations in <?php echo htmlspecialchars($country_filter); ?>
                    <?php else: ?>
                        Filtered Destinations
                    <?php endif; ?>
                </h2>
                <a href="destinations.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <h2>Explore All Destinations</h2>
                <p class="text-muted">Discover amazing places for your next journey</p>
            </div>
        <?php endif; ?>
        
        <!-- Destinations Grid -->
        <div class="row">
            <?php 
            if (count($destinations) > 0):
                foreach ($destinations as $destination): 
                    // Choose image based on destination name, or use default
                    $image_key = isset($image_map[$destination['location_name']]) ? $destination['location_name'] : 'Default';
                    $image = isset($destination['main_image_url']) && !empty($destination['main_image_url']) 
                            ? $destination['main_image_url'] 
                            : 'assets/images/destinations/' . $image_map[$image_key];
                    
                    // Get attractions count
                    $attractions_count = getAttractionsCount($conn, $destination['destination_id']);
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm destination-card">
                    <img src="<?php echo $image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($destination['location_name']); ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($destination['location_name']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?php 
                                echo htmlspecialchars($destination['country']);
                                if(!empty($destination['city'])) {
                                    echo ' - ' . htmlspecialchars($destination['city']);
                                }
                            ?>
                        </h6>
                        
                        <?php if (!empty($destination['climate'])): ?>
                        <div class="mb-2 text-muted small">
                            <i class="fas fa-cloud-sun mr-1"></i> <?php echo htmlspecialchars($destination['climate']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($destination['best_time_to_visit'])): ?>
                        <div class="mb-2 text-muted small">
                            <i class="fas fa-calendar-alt mr-1"></i> Best time: <?php echo htmlspecialchars($destination['best_time_to_visit']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <p class="card-text">
                            <?php 
                                $description = $destination['description'] ?? '';
                                echo htmlspecialchars(substr($description, 0, 100) . (strlen($description) > 100 ? '...' : ''));
                            ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-info text-dark"><?php echo $attractions_count; ?> Attractions</span>
                            <a href="destination-detail.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-outline-primary">Explore</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            else:
            ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No destinations found. Please try a different search or filter.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2>Ready to plan your next adventure?</h2>
                <p class="lead mb-0">Create your personalized itinerary and discover amazing destinations.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="create-trip.php" class="btn btn-light btn-lg">Start Planning</a>
            </div>
        </div>
    </div>
</section>

<?php
// Close the database connection
$conn->close();

// Include footer
include 'footer.php';
?>