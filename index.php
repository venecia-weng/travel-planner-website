<?php
// Do NOT include session_start() here as it's already in header.php
// Include database connection
require_once 'includes/db_connect.php';

// Check for logout success parameter
$logout_success = isset($_GET['logout']) && $_GET['logout'] === 'success';

// Set page title
$page_title = 'RoundTours - Plan Your Dream Trip';

// Get featured destinations
$sql = "SELECT * FROM Destinations WHERE status = 'active' ORDER BY RAND() LIMIT 6";
$result = $conn->query($sql);

$featured_destinations = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $featured_destinations[] = $row;
    }
}

// Include header
include 'header.php';

// Check if user is logged in - we'll use this to conditionally display the CTA section
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
?>

<!-- Add animation styles -->
<style>
.alert-dismissible {
    animation: slideDown 0.5s ease-out forwards;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.jumbotron {
    background-image: url('assets/images/hero-background.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    padding: 100px 0;
    position: relative;
}

.jumbotron::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
}

.jumbotron .container {
    position: relative;
    z-index: 1;
}

.feature-box {
    text-align: center;
    padding: 30px 20px;
    margin-bottom: 30px;
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.feature-box:hover {
    transform: translateY(-10px);
}

.feature-icon {
    font-size: 50px;
    margin-bottom: 20px;
    color: #3498db;
}

/* Quiz Card Styles */
.quiz-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 50px;
}

.quiz-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.quiz-card-body {
    padding: 30px;
}

.quiz-card-img {
    height: 250px;
    object-fit: cover;
}

.quiz-headline {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2c3e50;
}

/* .quiz-description {
    color: #7f8c8d;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.quiz-button {
    font-weight: 600;
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 50px;
    background-color: #3498db;
    border-color: #3498db;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    transition: all 0.3s ease;
} */

.quiz-description {
    color: #333333; /* Darker gray for better contrast */
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.quiz-button {
    font-weight: 600;
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 50px;
    background-color: #267ab9; /* Slightly darker blue */
    border-color: #267ab9; /* Match the background color */
    box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3); /* Adjusted shadow color */
    transition: all 0.3s ease;
}

.quiz-button:hover {
    background-color: #2980b9;
    border-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 7px 20px rgba(52, 152, 219, 0.4);
}

@media (max-width: 768px) {
    .quiz-headline {
        font-size: 1.5rem;
    }
}
</style>

<?php if ($logout_success): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> You have been successfully logged out.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<!-- Hero Section -->
<div class="jumbotron jumbotron-fluid mb-0">
    <div class="container text-center">
        <h1 class="display-4">Plan Your Perfect Trip</h1>
        <p class="lead">Create custom travel itineraries, explore destinations, and organize your dream vacation with ease.</p>
        <div class="mt-4">
            <a href="destinations.php" class="btn btn-primary btn-lg me-2">Explore Destinations</a>
            <a href="itinerary.php" class="btn btn-outline-light btn-lg">Create Itinerary</a>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="bg-light py-4">
    <div class="container">
        <form action="destinations.php" method="get">
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" placeholder="Search for destinations..." name="search">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Itinerary Quiz Card -->
<div class="container mt-5">
    <div class="card quiz-card">
        <div class="row g-0">
            <div class="col-md-6">
                <img src="assets/images/destinations/thailand.jpg" class="img-fluid quiz-card-img w-100" alt="Take our Travel Quiz">
            </div>
            <div class="col-md-6">
                <div class="quiz-card-body d-flex flex-column justify-content-center h-100">
                    <h2 class="quiz-headline">Discover Your Perfect Trip</h2>
                    <p class="quiz-description">Not sure where to go? Answer a few questions about your travel preferences, and we'll create a personalized itinerary just for you. It only takes 5 minutes!</p>
                    <div>
                        <a href="personalised-itinerary.php" class="btn btn-primary quiz-button">
                            <i class="bi bi-check2-circle me-2"></i> Take the Travel Quiz
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Destinations Section -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Featured Destinations</h2>
        <a href="destinations.php" class="btn btn-outline-primary">View All</a>
    </div>
    
    <div class="row">
        <?php if(count($featured_destinations) > 0): ?>
            <?php foreach($featured_destinations as $destination): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if(!empty($destination['main_image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($destination['main_image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($destination['location_name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <img src="images/placeholder.jpg" class="card-img-top" alt="Placeholder" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
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
                            <div class="mb-2 small text-muted">
                                <i class="bi bi-cloud-sun me-1"></i> <?php echo htmlspecialchars($destination['climate']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <p class="card-text">
                                <?php 
                                    $description = $destination['description'];
                                    echo htmlspecialchars(substr($description, 0, 100) . (strlen($description) > 100 ? '...' : ''));
                                ?>
                            </p>
                            <a href="destination-detail.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No featured destinations available at the moment. Please check back later.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Why Choose Our Trip Planner?</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="bi bi-map"></i>
                    </div>
                    <h4>Extensive Destination Database</h4>
                    <p>Explore detailed information on hundreds of destinations around the world, including attractions, tips, and travel guides.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h4>Custom Itineraries</h4>
                    <p>Create personalized day-by-day travel plans that fit your interests, preferences, and schedule.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="bi bi-airplane"></i>
                    </div>
                    <h4>Travel Organization</h4>
                    <p>Keep track of accommodations, transportation details, and activities all in one convenient place.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section - Only show if user is NOT logged in -->
<?php if (!$is_logged_in): ?>
<div class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2>Ready to start planning your next adventure?</h2>
        <p class="lead">Create your account now and begin building your perfect itinerary.</p>
        <a href="register.php" class="btn btn-light btn-lg mt-3">Sign Up for Free</a>
    </div>
</div>
<?php endif; ?>

<!-- How It Works Section -->
<div class="container my-5 py-3">
    <h2 class="text-center mb-5">How It Works</h2>
    <div class="row">
        <div class="col-md-3">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                    <h3 class="m-0">1</h3>
                </div>
                <h4>Create Account</h4>
                <p>Sign up for free and set up your profile.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                    <h3 class="m-0">2</h3>
                </div>
                <h4>Explore Destinations</h4>
                <p>Browse detailed information about various destinations.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                    <h3 class="m-0">3</h3>
                </div>
                <h4>Plan Your Trip</h4>
                <p>Add destinations to your itinerary and organize your schedule.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                    <h3 class="m-0">4</h3>
                </div>
                <h4>Enjoy Your Journey</h4>
                <p>Access your itinerary anytime during your travels.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>