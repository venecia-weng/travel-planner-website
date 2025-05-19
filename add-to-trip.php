<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the intended destination in session to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?redirect=1");
    exit();
}

// Get destination ID from URL
$destination_id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : 0;

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

// Get user's trips
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM Trips WHERE user_id = ? AND status != 'cancelled' ORDER BY start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trips_result = $stmt->get_result();

$trips = array();
if ($trips_result->num_rows > 0) {
    while($row = $trips_result->fetch_assoc()) {
        $trips[] = $row;
    }
}

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['trip_id']) && isset($_POST['arrival_date']) && isset($_POST['departure_date'])) {
        $trip_id = intval($_POST['trip_id']);
        $arrival_date = $_POST['arrival_date'];
        $departure_date = $_POST['departure_date'];
        $accommodation = isset($_POST['accommodation']) ? $_POST['accommodation'] : '';
        $transportation = isset($_POST['transportation']) ? $_POST['transportation'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Validate dates
        $valid = true;
        if (empty($arrival_date) || empty($departure_date)) {
            $message = "Arrival and departure dates are required.";
            $messageType = "danger";
            $valid = false;
        } elseif ($arrival_date > $departure_date) {
            $message = "Departure date must be after arrival date.";
            $messageType = "danger";
            $valid = false;
        }
        
        // Get the order index (highest current index + 1)
        $order_index = 1;
        $sql = "SELECT MAX(order_index) as max_index FROM Trip_Destinations WHERE trip_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $trip_id);
        $stmt->execute();
        $index_result = $stmt->get_result();
        if ($row = $index_result->fetch_assoc()) {
            if ($row['max_index'] !== null) {
                $order_index = $row['max_index'] + 1;
            }
        }
        
        if ($valid) {
            // Check if destination already exists in this trip
            $sql = "SELECT * FROM Trip_Destinations WHERE trip_id = ? AND destination_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $trip_id, $destination_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing destination in trip
                $sql = "UPDATE Trip_Destinations SET 
                        arrival_date = ?, 
                        departure_date = ?, 
                        accommodation_details = ?, 
                        transportation_details = ?, 
                        notes = ? 
                        WHERE trip_id = ? AND destination_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssii", $arrival_date, $departure_date, $accommodation, $transportation, $notes, $trip_id, $destination_id);
                
                if ($stmt->execute()) {
                    $message = "Destination details updated in your trip!";
                    $messageType = "success";
                } else {
                    $message = "Error updating destination in trip: " . $conn->error;
                    $messageType = "danger";
                }
            } else {
                // Add new destination to trip
                $sql = "INSERT INTO Trip_Destinations (trip_id, destination_id, order_index, arrival_date, departure_date, accommodation_details, transportation_details, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiisssss", $trip_id, $destination_id, $order_index, $arrival_date, $departure_date, $accommodation, $transportation, $notes);
                
                if ($stmt->execute()) {
                    $message = "Destination added to your trip!";
                    $messageType = "success";
                } else {
                    $message = "Error adding destination to trip: " . $conn->error;
                    $messageType = "danger";
                }
            }
        }
    } elseif (isset($_POST['create_trip']) && isset($_POST['trip_name'])) {
        // Create a new trip
        $trip_name = $_POST['trip_name'];
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        
        // Validate
        if (empty($trip_name)) {
            $message = "Trip name is required.";
            $messageType = "danger";
        } else {
            // Insert new trip
            $sql = "INSERT INTO Trips (user_id, name, description, start_date, end_date, status) 
                    VALUES (?, ?, ?, ?, ?, 'planning')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $trip_name, $description, $start_date, $end_date);
            
            if ($stmt->execute()) {
                $new_trip_id = $conn->insert_id;
                $message = "New trip created! You can now add this destination to your trip.";
                $messageType = "success";
                
                // Refresh trips list
                $sql = "SELECT * FROM Trips WHERE user_id = ? AND status != 'cancelled' ORDER BY start_date DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $trips_result = $stmt->get_result();
                
                $trips = array();
                if ($trips_result->num_rows > 0) {
                    while($row = $trips_result->fetch_assoc()) {
                        $trips[] = $row;
                    }
                }
            } else {
                $message = "Error creating trip: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Trip - Travel Itinerary Planner</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Travel Itinerary Planner</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="destinations.php">Destinations</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="trips.php">My Trips</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="mb-4">Add to Trip</h1>
        
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <?php if(!empty($destination['main_image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($destination['main_image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($destination['location_name']); ?>">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" class="card-img-top" alt="Placeholder">
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
                        <p class="card-text">
                            <?php 
                                $description = $destination['description'];
                                echo htmlspecialchars(substr($description, 0, 100) . (strlen($description) > 100 ? '...' : ''));
                            ?>
                        </p>
                        <a href="destination-detail.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="tripTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="existing-tab" data-toggle="tab" href="#existing" role="tab">Add to Existing Trip</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="new-tab" data-toggle="tab" href="#new" role="tab">Create New Trip</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="tripTabsContent">
                            <!-- Add to Existing Trip Tab -->
                            <div class="tab-pane fade show active" id="existing" role="tabpanel">
                                <?php if(count($trips) > 0): ?>
                                    <form method="post" action="add-to-trip.php?destination_id=<?php echo $destination_id; ?>">
                                        <div class="form-group">
                                            <label for="trip_id"><strong>Select Trip:</strong></label>
                                            <select class="form-control" id="trip_id" name="trip_id" required>
                                                <?php foreach($trips as $trip): ?>
                                                    <option value="<?php echo $trip['trip_id']; ?>">
                                                        <?php echo htmlspecialchars($trip['name']); ?>
                                                        <?php if(!empty($trip['start_date'])): ?>
                                                            (<?php echo date('M d, Y', strtotime($trip['start_date'])); ?>
                                                            <?php if(!empty($trip['end_date'])): ?>
                                                                - <?php echo date('M d, Y', strtotime($trip['end_date'])); ?>
                                                            <?php endif; ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="arrival_date"><strong>Arrival Date:</strong></label>
                                                    <input type="date" class="form-control" id="arrival_date" name="arrival_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="departure_date"><strong>Departure Date:</strong></label>
                                                    <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="accommodation"><strong>Accommodation Details:</strong></label>
                                            <textarea class="form-control" id="accommodation" name="accommodation" rows="2" placeholder="Hotel name, booking information, etc."></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="transportation"><strong>Transportation Details:</strong></label>
                                            <textarea class="form-control" id="transportation" name="transportation" rows="2" placeholder="Flight numbers, train tickets, etc."></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes"><strong>Notes:</strong></label>
                                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information about your stay"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success btn-block">Add to Trip</button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <p>You don't have any trips yet. Create a new trip to add this destination.</p>
                                        <button class="btn btn-primary" id="switchToNewTrip">Create New Trip</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Create New Trip Tab -->
                            <div class="tab-pane fade" id="new" role="tabpanel">
                                <form method="post" action="add-to-trip.php?destination_id=<?php echo $destination_id; ?>">
                                    <div class="form-group">
                                        <label for="trip_name"><strong>Trip Name:</strong></label>
                                        <input type="text" class="form-control" id="trip_name" name="trip_name" placeholder="e.g. Thailand Adventure 2025" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_date"><strong>Start Date (optional):</strong></label>
                                                <input type="date" class="form-control" id="start_date" name="start_date">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="end_date"><strong>End Date (optional):</strong></label>
                                                <input type="date" class="form-control" id="end_date" name="end_date">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><strong>Trip Description (optional):</strong></label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of your trip"></textarea>
                                    </div>
                                    
                                    <input type="hidden" name="create_trip" value="1">
                                    <button type="submit" class="btn btn-primary btn-block">Create Trip</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2025 Travel Itinerary Planner. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <a href="#" class="text-white mr-3">Privacy Policy</a>
                    <a href="#" class="text-white mr-3">Terms of Service</a>
                    <a href="#" class="text-white">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Script to switch to the "Create New Trip" tab
        document.getElementById('switchToNewTrip')?.addEventListener('click', function() {
            document.getElementById('new-tab').click();
        });
        
        // Date validation
        document.getElementById('arrival_date')?.addEventListener('change', function() {
            document.getElementById('departure_date').min = this.value;
        });
        
        document.getElementById('start_date')?.addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>