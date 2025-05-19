<?php
// Start session at the beginning of the file
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the intended destination in session to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?redirect=1");
    exit();
}

// Include database connection
require_once 'includes/db_connect.php';

// Get user's trips
$user_id = $_SESSION['user_id'];

// Query for upcoming trips
$sql_upcoming = "SELECT t.*, 
                (SELECT location_name FROM Destinations d 
                 JOIN Trip_Destinations td ON d.destination_id = td.destination_id 
                 WHERE td.trip_id = t.trip_id 
                 ORDER BY td.order_index LIMIT 1) as destination
                FROM Trips t 
                WHERE t.user_id = ? 
                AND t.end_date >= CURDATE() 
                AND t.status != 'cancelled' 
                ORDER BY t.start_date ASC";

$stmt = $conn->prepare($sql_upcoming);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_trips = [];
while ($row = $result->fetch_assoc()) {
    $upcoming_trips[] = $row;
}

// Query for past trips
$sql_past = "SELECT t.*, 
            (SELECT location_name FROM Destinations d 
             JOIN Trip_Destinations td ON d.destination_id = td.destination_id 
             WHERE td.trip_id = t.trip_id 
             ORDER BY td.order_index LIMIT 1) as destination
            FROM Trips t 
            WHERE t.user_id = ? 
            AND t.end_date < CURDATE() 
            ORDER BY t.end_date DESC";

$stmt = $conn->prepare($sql_past);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$past_trips = [];
while ($row = $result->fetch_assoc()) {
    $past_trips[] = $row;
}

// Query for cancelled trips
$sql_cancelled = "SELECT t.*, 
                (SELECT location_name FROM Destinations d 
                 JOIN Trip_Destinations td ON d.destination_id = td.destination_id 
                 WHERE td.trip_id = t.trip_id 
                 ORDER BY td.order_index LIMIT 1) as destination
                FROM Trips t 
                WHERE t.user_id = ? 
                AND t.status = 'cancelled' 
                ORDER BY t.start_date ASC";

$stmt = $conn->prepare($sql_cancelled);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cancelled_trips = [];
while ($row = $result->fetch_assoc()) {
    $cancelled_trips[] = $row;
}

$page_title = 'My Trips - Travel Planner';
include 'header.php';
?>

<section class="my-trips-section py-5">
    <div class="container">
        <h1 class="mb-4">My Trips</h1>
        
        <!-- Add Trip Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Upcoming Trips</h2>
            <a href="destinations.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Plan New Trip</a>
        </div>
        
        <!-- Upcoming Trips -->
        <?php if (empty($upcoming_trips)): ?>
            <div class="alert alert-info mb-5">
                You don't have any upcoming trips. Start planning your adventure!
            </div>
        <?php else: ?>
            <div class="row mb-5">
                <?php foreach ($upcoming_trips as $trip): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($trip['name']); ?></h5>
                            
                            <?php if(!empty($trip['destination'])): ?>
                            <p class="card-text text-muted mb-2">
                                <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($trip['destination']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> 
                                <?php 
                                if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                                    echo date('M d, Y', strtotime($trip['start_date'])) . ' - ' . 
                                         date('M d, Y', strtotime($trip['end_date']));
                                } elseif (!empty($trip['start_date'])) {
                                    echo 'From ' . date('M d, Y', strtotime($trip['start_date']));
                                } elseif (!empty($trip['end_date'])) {
                                    echo 'Until ' . date('M d, Y', strtotime($trip['end_date']));
                                } else {
                                    echo 'Dates not set';
                                }
                                ?>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="badge bg-success"><?php echo htmlspecialchars($trip['status']); ?></span>
                                
                                <?php 
                                // Check if trip has an itinerary
                                $itinerary_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM Trip_Itinerary WHERE trip_id = ?");
                                $itinerary_stmt->bind_param("i", $trip['trip_id']);
                                $itinerary_stmt->execute();
                                $itinerary_result = $itinerary_stmt->get_result()->fetch_assoc();
                                
                                if ($itinerary_result['item_count'] > 0): 
                                ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-calendar-check"></i> 
                                        <?php echo $itinerary_result['item_count']; ?> itinerary items
                                    </span>
                                <?php endif; ?>
                                
                                <?php 
                                // Count destinations in trip
                                $dest_stmt = $conn->prepare("SELECT COUNT(*) as dest_count FROM Trip_Destinations WHERE trip_id = ?");
                                $dest_stmt->bind_param("i", $trip['trip_id']);
                                $dest_stmt->execute();
                                $dest_result = $dest_stmt->get_result()->fetch_assoc();
                                
                                if ($dest_result['dest_count'] > 0): 
                                ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pin-map"></i> 
                                        <?php echo $dest_result['dest_count']; ?> destinations
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="trip-details.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-info-circle"></i> Details
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        data-bs-toggle="modal" data-bs-target="#cancelTripModal<?php echo $trip['trip_id']; ?>">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cancel Trip Modal -->
                    <div class="modal fade" id="cancelTripModal<?php echo $trip['trip_id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Cancel Trip</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to cancel your trip to <?php echo htmlspecialchars($trip['destination'] ?? $trip['name']); ?>?</p>
                                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Trip</button>
                                    <a href="cancel-trip.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-danger">Yes, Cancel Trip</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Cancelled Trips -->
        <h2 class="h4 mb-3">Cancelled Trips</h2>
        <?php if (empty($cancelled_trips)): ?>
            <div class="alert alert-info">
                You don't have any cancelled trips.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($cancelled_trips as $trip): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-danger">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($trip['name']); ?></h5>
                            
                            <?php if(!empty($trip['destination'])): ?>
                            <p class="card-text text-muted mb-2">
                                <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($trip['destination']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> 
                                <?php 
                                if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                                    echo date('M d, Y', strtotime($trip['start_date'])) . ' - ' . 
                                         date('M d, Y', strtotime($trip['end_date']));
                                } elseif (!empty($trip['start_date'])) {
                                    echo 'From ' . date('M d, Y', strtotime($trip['start_date']));
                                } elseif (!empty($trip['end_date'])) {
                                    echo 'Until ' . date('M d, Y', strtotime($trip['end_date']));
                                } else {
                                    echo 'Dates not set';
                                }
                                ?>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="badge bg-danger"><?php echo htmlspecialchars($trip['status']); ?></span>
                                
                                <?php 
                                // Check if trip has an itinerary
                                $itinerary_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM Trip_Itinerary WHERE trip_id = ?");
                                $itinerary_stmt->bind_param("i", $trip['trip_id']);
                                $itinerary_stmt->execute();
                                $itinerary_result = $itinerary_stmt->get_result()->fetch_assoc();
                                
                                if ($itinerary_result['item_count'] > 0): 
                                ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-calendar-check"></i> 
                                        <?php echo $itinerary_result['item_count']; ?> itinerary items
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="trip-details.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-info-circle"></i> Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Past Trips -->
        <h2 class="h4 mb-3">Past Trips</h2>
        <?php if (empty($past_trips)): ?>
            <div class="alert alert-info">
                You don't have any past trips.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($past_trips as $trip): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($trip['name']); ?></h5>
                            
                            <?php if(!empty($trip['destination'])): ?>
                            <p class="card-text text-muted mb-2">
                                <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($trip['destination']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> 
                                <?php 
                                if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                                    echo date('M d, Y', strtotime($trip['start_date'])) . ' - ' . 
                                         date('M d, Y', strtotime($trip['end_date']));
                                } elseif (!empty($trip['start_date'])) {
                                    echo 'From ' . date('M d, Y', strtotime($trip['start_date']));
                                } elseif (!empty($trip['end_date'])) {
                                    echo 'Until ' . date('M d, Y', strtotime($trip['end_date']));
                                } else {
                                    echo 'Dates not set';
                                }
                                ?>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($trip['status']); ?></span>
                                
                                <?php 
                                // Check if trip has an itinerary
                                $itinerary_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM Trip_Itinerary WHERE trip_id = ?");
                                $itinerary_stmt->bind_param("i", $trip['trip_id']);
                                $itinerary_stmt->execute();
                                $itinerary_result = $itinerary_stmt->get_result()->fetch_assoc();
                                
                                if ($itinerary_result['item_count'] > 0): 
                                ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-calendar-check"></i> 
                                        <?php echo $itinerary_result['item_count']; ?> itinerary items
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="trip-details.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-info-circle"></i> Details
                                </a>
                                <?php if ($trip['status'] !== 'completed'): ?>
                                <a href="mark-completed.php?id=<?php echo $trip['trip_id']; ?>" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-check-circle"></i> Mark as Completed
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Toast notifications container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="successToastMessage">Operation completed successfully.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// Check for success message in URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        // Show success toast
        document.getElementById('successToastMessage').textContent = decodeURIComponent(successMsg);
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        toast.show();
        
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (errorMsg) {
        // You could implement an error toast similar to the success toast
        alert(decodeURIComponent(errorMsg));
        
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php 
// Close any open database connections
$conn->close();

// Include footer
include 'footer.php'; 
?>