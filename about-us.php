<?php
require_once 'includes/db_connect.php';

// Set page title
$page_title = 'RoundTours - Plan Your Dream Trip';

// Include header
include 'header.php';
?>
    <div class="container px-4 mt-5">
        <!-- Hero Section -->
        <div class="row">
            <div class="col-12">
                <div class="text-white text-center py-5 rounded-3" style="background: linear-gradient(135deg, #0866b8 0%, #33c4ca 100%); margin-bottom: 3rem;">
                    <h1 class="display-5 fw-bold mb-3">Discover the World, Simplified</h1>
                    <p class="lead opacity-75">Your Ultimate Travel Companion</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row mb-5">
            <!-- Who We Are -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-globe text-primary me-3" style="font-size: 2.5rem;"></i>
                            <h2 class="card-title text-primary">Who We Are</h2>
                        </div>
                        <p class="card-text text-muted">Travel Planner is more than just a travel website – we're your personal gateway to seamless, unforgettable adventures. Born from a passion for exploration and a desire to make travel planning effortless, we've created a platform that turns your travel dreams into reality.</p>
                    </div>
                </div>
            </div>

            <!-- Our Mission -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle text-success me-3" style="font-size: 2.5rem;"></i>
                            <h2 class="card-title text-success">Our Mission</h2>
                        </div>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                Curated destination discoveries
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                Unbeatable travel deals
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                Personalized itinerary creation
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                Expert travel insights
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- What Makes Us Special -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bi bi-star-fill me-3" style="font-size: 2.5rem; color:rgba(88, 61, 25, 0.59);"></i>
                            <h2 class="card-title" style="color: rgba(88, 61, 25, 0.59);">What Makes Us Special</h2>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 bg-light rounded h-100">
                                    <i class="bi bi-map text-primary mb-3" style="font-size: 3rem;"></i>
                                    <h3 class="h5 mb-2">All-in-One Planning</h3>
                                    <p class="text-muted">Seamlessly organize flights, accommodations, and activities in one intuitive platform.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 bg-light rounded h-100">
                                    <i class="bi bi-lightbulb text-success mb-3" style="font-size: 3rem;"></i>
                                    <h3 class="h5 mb-2">Smart Recommendations</h3>
                                    <p class="text-muted">Personalized suggestions based on your travel preferences and style.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center p-3 bg-light rounded h-100">
                                    <i class="bi bi-shield-check text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h3 class="h5 mb-2">Secure & Reliable</h3>
                                    <p class="text-muted">Top-notch security to protect your data and bookings.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="row">
            <div class="col-12">
                <div class="text-white text-center py-5 rounded-3" style="background: linear-gradient(135deg, #0866b8 0%, #33c4ca 100%);">
                    <h2 class="display-5 fw-bold mb-3">Ready to Explore the World?</h2>
                    <p class="lead mb-4 opacity-75">Your next adventure is just a click away. Start planning, start dreaming!</p>
                    <a href="personalised-itinerary.php" class="btn btn-light" style="color: #004085;">Begin Your Journey</a>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
include 'footer.php';
?>