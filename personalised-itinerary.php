<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Get destinations for quiz options
$sql = "SELECT destination_id, location_name, country, city, main_image_url, 
        currency, status, timezone, language,
        budget_low_min, budget_low_max, budget_mid_min, 
        budget_mid_max, budget_high_min, budget_high_max,
        budget_low_desc, budget_mid_desc, budget_high_desc,
        culture_description, adventure_description, relax_description, 
        foodie_description, nightlife_description, balanced_description
        FROM Destinations 
        WHERE status = 'active'";
$result = $conn->query($sql);
$destinations = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Convert destinations to JSON for JavaScript
$destinations_json = json_encode($destinations);

// Get attractions for quiz recommendations
$sql = "SELECT attraction_id, destination_id, name, description, category, entrance_fee 
        FROM Attractions 
        WHERE status = 'active'";
$result = $conn->query($sql);
$attractions = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $attractions[] = $row;
    }
}

// Convert attractions to JSON for JavaScript
$attractions_json = json_encode($attractions);

// Get featured destinations (limit to 5 for the quiz)
$sql = "SELECT * FROM Destinations WHERE status = 'active' ORDER BY RAND() LIMIT 5";
$result = $conn->query($sql);
$featured_destinations = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $featured_destinations[] = $row;
    }
}

// Attraction types/categories for interests
$sql = "SELECT DISTINCT category FROM Attractions WHERE category IS NOT NULL";
$result = $conn->query($sql);
$attraction_categories = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!empty($row['category'])) {
            $attraction_categories[] = $row['category'];
        }
    }
}

// Get tip types for special requirements
$sql = "SELECT DISTINCT tip_type FROM Travel_Tips WHERE tip_type IS NOT NULL";
$result = $conn->query($sql);
$tip_types = array();
$destinations_json = json_encode($destinations);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!empty($row['tip_type'])) {
            $tip_types[] = $row['tip_type'];
        }
    }
}

// Page title
$page_title = 'Custom Itinerary Quiz - Travel Itinerary Planner';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        /* Quiz Container */
        .quiz-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        }

        /* Progress Bar */
        .progress-container {
            margin-bottom: 40px;
        }

        .progress {
            height: 8px;
            background-color: #e9e9e9;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #3498db, #1abc9c);
            transition: width 0.5s ease;
        }

        /* Quiz Slides */
        .quiz-slide {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .quiz-slide.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .quiz-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .quiz-header p {
            color: #666;
            font-size: 16px;
        }

        /* Option Cards */
        .option-card {
            background-color: #fff;
            border: 2px solid #e9e9e9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .option-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
            border-color: #bbb;
        }

        .option-card.selected {
            border-color: #3498db;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.2);
            position: relative;
        }

        .option-card.selected::after {
            content: "\f00c";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            color: #3498db;
            background-color: rgba(255, 255, 255, 0.9);
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .option-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .option-card h5 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .option-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 0;
        }

        /* Navigation Buttons */
        .quiz-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .quiz-navigation button {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 500;
        }

        /* Multi-Select Options */
        .multi-select-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .multi-option {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 30px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .multi-option:hover {
            background-color: #e9e9e9;
        }

        .multi-option.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Range Slider */
        .range-slider {
            width: 100%;
            appearance: none;
            height: 6px;
            border-radius: 3px;
            background: #e9e9e9;
            outline: none;
        }

        .range-slider::-webkit-slider-thumb {
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #3498db;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .range-slider::-webkit-slider-thumb:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            color: #666;
            font-size: 14px;
            padding: 0 10px;
        }

        /* Budget Descriptions */
        .budget-examples {
            margin-top: 30px;
        }

        .budget-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .budget-description p {
            margin-bottom: 10px;
        }

        /* Result Container */
        .result-container {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            text-align: center;
        }

        .result-container.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .result-container h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .result-image {
            width: 100%;
            max-width: 800px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .result-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: left;
        }

        .result-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .result-card .lead {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        /* Hero Banner */
        .hero-banner {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/destinations/thailand.jpg');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
            margin-bottom: 40px;
        }

        .hero-banner h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-banner p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-banner {
                height: 300px;
            }
            
            .hero-banner h1 {
                font-size: 2.2rem;
            }
            
            .quiz-container {
                padding: 20px;
            }
            
            .quiz-header h2 {
                font-size: 24px;
            }
            
            .option-card img {
                height: 140px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Hero Banner -->
    <div class="hero-banner">
        <div class="container">
            <h1>Custom Itinerary Quiz</h1>
            <p>Give us five minutes of your time and we'll formulate a unique trip just for you.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="quiz-container">
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 12.5%;" aria-valuenow="12.5" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="text-end text-muted mt-2"><span id="currentStep">1</span> of <span id="totalSteps">8</span></p>
            </div>
            
            <!-- Quiz Slides -->
            <div class="quiz-slides">
                <!-- Question 1: Destination -->
                <div class="quiz-slide active" data-step="1">
                    <div class="quiz-header">
                        <h2>Where would you like to go?</h2>
                        <p class="text-muted">Select a destination to begin your adventure</p>
                    </div>
                    <div class="row">
                        <?php foreach($destinations as $destination): ?>
                        <div class="col-md-4 mb-4">
                            <div class="option-card" data-value="<?php echo $destination['destination_id']; ?>">
                                <?php if(!empty($destination['main_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($destination['main_image_url']); ?>" alt="<?php echo htmlspecialchars($destination['location_name']); ?>">
                                <?php else: ?>
                                <img src="assets/images/destinations/placeholder.jpg" alt="Placeholder">
                                <?php endif; ?>
                                <h5><?php echo htmlspecialchars($destination['location_name']); ?></h5>
                                <p class="text-muted">
                                    <?php 
                                        echo htmlspecialchars($destination['country']);
                                        if(!empty($destination['city'])) {
                                            echo ' - ' . htmlspecialchars($destination['city']);
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="col-md-4 mb-4">
                            <div class="option-card" data-value="anywhere">
                                <img src="assets/images/destinations/anywhere.jpg" alt="Anywhere">
                                <h5>Surprise Me</h5>
                                <p class="text-muted">I'm open to any destination</p>
                            </div>
                        </div>
                    </div>
                    <div class="quiz-navigation">
                        <button class="btn btn-outline-secondary disabled" disabled>Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                
                <!-- Question 2: Traveler Type -->
                <div class="quiz-slide" data-step="2">
                    <div class="quiz-header">
                        <h2>What kind of traveler are you?</h2>
                        <p class="text-muted">Select the option that best describes you</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="option-card" data-value="culture">
                                <img src="assets/images/traveler-types/culture.jpg" alt="Culture Explorer">
                                <h5>Culture Explorer</h5>
                                <p class="text-muted">Museums, historical sites, and local traditions</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="adventure">
                                <img src="assets/images/traveler-types/adventure.jpg" alt="Adventure Seeker">
                                <h5>Adventure Seeker</h5>
                                <p class="text-muted">Hiking, outdoor activities, and thrilling experiences</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="relax">
                                <img src="assets/images/traveler-types/relax.jpg" alt="Relaxation Enthusiast">
                                <h5>Relaxation Enthusiast</h5>
                                <p class="text-muted">Beaches, spas, and peaceful getaways</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="foodie">
                                <img src="assets/images/traveler-types/foodie.jpg" alt="Food Lover">
                                <h5>Food Lover</h5>
                                <p class="text-muted">Restaurants, food tours, and cooking classes</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="nightlife">
                                <img src="assets/images/traveler-types/nightlife.jpg" alt="Night Owl">
                                <h5>Night Owl</h5>
                                <p class="text-muted">Bars, clubs, and vibrant nightlife</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="balanced">
                                <img src="assets/images/traveler-types/balanced.jpg" alt="Balanced Explorer">
                                <h5>Balanced Explorer</h5>
                                <p class="text-muted">A bit of everything, balanced itinerary</p>
                            </div>
                        </div>
                    </div>
                    <div class="quiz-navigation">
                        <button class="btn btn-outline-secondary prev-step">Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                
                <!-- Question 3: Trip Duration -->
                <div class="quiz-slide" data-step="3">
                    <div class="quiz-header">
                        <h2>How long will your trip be?</h2>
                        <p class="text-muted">Select the duration of your adventure</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="option-card" data-value="weekend">
                                <img src="assets/images/duration/weekend.jpg" alt="Weekend Trip">
                                <h5>Weekend Getaway</h5>
                                <p class="text-muted">2-3 days</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="short">
                                <img src="assets/images/duration/short.jpg" alt="Short Trip">
                                <h5>Short Trip</h5>
                                <p class="text-muted">4-6 days</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="week">
                                <img src="assets/images/duration/week.jpg" alt="One Week">
                                <h5>One Week</h5>
                                <p class="text-muted">7 days</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="twoweeks">
                                <img src="assets/images/duration/twoweeks.jpg" alt="Two Weeks">
                                <h5>Two Weeks</h5>
                                <p class="text-muted">14 days</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="extended">
                                <img src="assets/images/duration/extended.jpg" alt="Extended Stay">
                                <h5>Extended Stay</h5>
                                <p class="text-muted">3+ weeks</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="flexible">
                                <img src="assets/images/duration/flexible.jpg" alt="Flexible">
                                <h5>Flexible</h5>
                                <p class="text-muted">Not sure yet</p>
                            </div>
                        </div>
                    </div>
                    <div class="quiz-navigation">
                        <button class="btn btn-outline-secondary prev-step">Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                
                <!-- Question 4: Travel Companion -->
                <div class="quiz-slide" data-step="4">
                    <div class="quiz-header">
                        <h2>Who are you traveling with?</h2>
                        <p class="text-muted">This helps us tailor activities for your group</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="option-card" data-value="solo">
                                <img src="assets/images/travel-with/solo.jpg" alt="Solo Travel">
                                <h5>Solo Adventure</h5>
                                <p class="text-muted">Traveling on my own</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="couple">
                                <img src="assets/images/travel-with/couple.jpg" alt="Couple">
                                <h5>Couple's Retreat</h5>
                                <p class="text-muted">Traveling with partner</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="friends">
                                <img src="assets/images/travel-with/friends.jpg" alt="Friends">
                                <h5>Friend Group</h5>
                                <p class="text-muted">Traveling with friends</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="family">
                                <img src="assets/images/travel-with/family.jpg" alt="Family">
                                <h5>Family Time</h5>
                                <p class="text-muted">Traveling with family</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="family-kids">
                                <img src="assets/images/travel-with/family-kids.jpg" alt="Family with Kids">
                                <h5>Family with Kids</h5>
                                <p class="text-muted">Kid-friendly activities needed</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="group">
                                <img src="assets/images/travel-with/group.jpg" alt="Large Group">
                                <h5>Large Group</h5>
                                <p class="text-muted">Traveling with 8+ people</p>
                            </div>
                        </div>
                    </div>
                    <div class="quiz-navigation">
                        <button class="btn btn-outline-secondary prev-step">Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                
                <!-- Question 5: Budget -->
                <div class="quiz-slide" data-step="5">
                    <div class="quiz-header">
                        <h2>What's your budget per person (excluding flights)?</h2>
                        <p class="text-muted">This helps us suggest appropriate accommodations and activities</p>
                    </div>
                    <div class="mb-5">
                        <input type="range" class="form-range range-slider" min="1" max="5" step="1" id="budgetRange" value="3">
                        <div class="slider-labels">
                            <span>Budget</span>
                            <span>Mid-range</span>
                            <span>Luxury</span>
                        </div>
                    </div>
                    <div class="budget-examples mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="budget-title">Your selection: <span id="budgetLabel">Mid-range</span></h5>
                            <div id="budgetDescriptions">
                                <div id="budget-loading" class="text-center">
                                    <p>Please select a destination first to see budget information</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="quiz-navigation">
                        <button class="btn btn-outline-secondary prev-step">Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
                </div>
                <!-- Question 6: Travel Dates -->
                <div class="quiz-slide" data-step="6">
                    <div class="quiz-header">
                        <h2>When are you planning to travel?</h2>
                        <p class="text-muted">This helps us recommend the best destinations for your travel dates</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="option-card" data-value="within-month">
                                <h5>Within a Month</h5>
                                <p class="text-muted">Planning a last-minute trip</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="1-3-months">
                                <h5>1-3 Months</h5>
                                <p class="text-muted">Planning a few months ahead</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="option-card" data-value="3-6-months">
                                <h5>3-6 Months</h5>
                                <p class="text-muted">Planning well in advance</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="option-card" data-value="6-plus-months">
                                <h5>6+ Months</h5>
                                <p class="text-muted">Planning far in advance</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="option-card" data-value="specific-dates">
                                <h5>Specific Dates</h5>
                                <div class="row mt-2">
                                    <div class="col-md-6 mb-2">
                                        <label for="startDate" class="form-label small">Start Date:</label>
                                        <input type="date" class="form-control form-control-sm" id="startDate" name="startDate">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="endDate" class="form-label small">End Date:</label>
                                        <input type="date" class="form-control form-control-sm" id="endDate" name="endDate">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="quiz-navigation mt-4">
                        <button class="btn btn-outline-secondary prev-step">Back</button>
                        <button class="btn btn-primary next-step">Next</button>
                    </div>
</div>
                
            </div>
        </div>
    </div>

    <?php
    include 'footer.php';
    ?> 

    <!-- JavaScript -->
    <script>
    const allDestinations = <?php echo $destinations_json; ?>;
    const allAttractions = <?php echo $attractions_json; ?>;
    if (typeof allDestinations !== 'object') {
        console.error("Destinations data not properly loaded");
    }
    if (typeof allAttractions !== 'object') {
        console.error("Attractions data not properly loaded");
    }
</script>
<script src="assets/js/personalised-itinerary.js"></script>

</body>
</html>