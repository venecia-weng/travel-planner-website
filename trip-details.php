<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?redirect=1");
    exit();
}

// Get trip ID
$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($trip_id <= 0) {
    header("Location: my-trips.php");
    exit();
}

// Verify the trip belongs to the user
$user_id = $_SESSION['user_id'];
$trip_sql = "SELECT t.*, 
             (SELECT location_name FROM Destinations d 
              JOIN Trip_Destinations td ON d.destination_id = td.destination_id 
              WHERE td.trip_id = t.trip_id 
              ORDER BY td.order_index LIMIT 1) as main_destination
             FROM Trips t 
             WHERE t.trip_id = ? AND t.user_id = ?";
$stmt = $conn->prepare($trip_sql);
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Trip not found or doesn't belong to user
    $_SESSION['error_message'] = "Trip not found or you don't have permission to view it.";
    header("Location: my-trips.php");
    exit();
}

$trip = $result->fetch_assoc();

// Get trip itinerary items
$itinerary_sql = "SELECT ti.*, 
                 CASE 
                   WHEN ti.item_type = 'attraction' THEN (SELECT name FROM Attractions WHERE attraction_id = ti.item_id) 
                   WHEN ti.item_type = 'flight' THEN (SELECT flight_number FROM flights WHERE flight_id = ti.item_id)
                   WHEN ti.item_type = 'room' THEN (SELECT room_type FROM rooms WHERE room_id = ti.item_id)
                   ELSE 'Unknown'
                 END as item_name,
                 CASE 
                   WHEN ti.item_type = 'attraction' THEN 'attraction'
                   WHEN ti.item_type = 'flight' THEN 'flight'
                   WHEN ti.item_type = 'room' THEN 'accommodation'
                 END as item_category
                 FROM Trip_Itinerary ti
                 WHERE ti.trip_id = ?
                 ORDER BY ti.scheduled_date, ti.scheduled_time";
$stmt = $conn->prepare($itinerary_sql);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$itinerary_result = $stmt->get_result();

$itinerary_items = [];
while ($row = $itinerary_result->fetch_assoc()) {
    $itinerary_items[] = $row;
}

// Get trip destinations
$destinations_sql = "SELECT d.*, td.arrival_date, td.departure_date, td.notes 
                    FROM Destinations d
                    JOIN Trip_Destinations td ON d.destination_id = td.destination_id
                    WHERE td.trip_id = ?
                    ORDER BY td.order_index";
$stmt = $conn->prepare($destinations_sql);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$destinations_result = $stmt->get_result();

$trip_destinations = [];
while ($row = $destinations_result->fetch_assoc()) {
    $trip_destinations[] = $row;
}

// Include header
$page_title = 'Trip Details - ' . htmlspecialchars($trip['name']);
include 'header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1><?php echo htmlspecialchars($trip['name']); ?></h1>
            <?php if (!empty($trip['main_destination'])): ?>
            <p class="text-muted">
                <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($trip['main_destination']); ?>
            </p>
            <?php endif; ?>
        </div>
        <div>
            <a href="itinerary.php?trip_id=<?php echo $trip_id; ?>" class="btn btn-primary">
                <i class="bi bi-calendar-week"></i> View/Edit Itinerary
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Trip Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Trip Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $trip['status'] === 'planning' ? 'warning' : 
                                ($trip['status'] === 'booked' ? 'success' : 
                                ($trip['status'] === 'cancelled' ? 'danger' : 'info')); 
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($trip['status'])); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Dates:</strong>
                        <?php if (!empty($trip['start_date']) && !empty($trip['end_date'])): ?>
                            <?php echo date('M d, Y', strtotime($trip['start_date'])); ?> - 
                            <?php echo date('M d, Y', strtotime($trip['end_date'])); ?>
                            (<?php 
                                $start = new DateTime($trip['start_date']);
                                $end = new DateTime($trip['end_date']);
                                $diff = $start->diff($end);
                                echo $diff->days + 1; ?> days)
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($trip['description'])): ?>
                    <div class="mb-0">
                        <strong>Description:</strong>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($trip['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Itinerary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Itinerary</h5>
                    <a href="itinerary.php?trip_id=<?php echo $trip_id; ?>" class="btn btn-sm btn-light">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($itinerary_items)): ?>
                        <p class="text-muted">No itinerary items added yet. <a href="itinerary.php?trip_id=<?php echo $trip_id; ?>">Add some now</a>!</p>
                    <?php else: ?>
                        <?php 
                        // Group items by date
                        $items_by_date = [];
                        foreach ($itinerary_items as $item) {
                            $date = $item['scheduled_date'];
                            if (!isset($items_by_date[$date])) {
                                $items_by_date[$date] = [];
                            }
                            $items_by_date[$date][] = $item;
                        }
                        
                        // Display items by date
                        foreach ($items_by_date as $date => $items): 
                            $formatted_date = date('l, F j, Y', strtotime($date));
                        ?>
                            <h5 class="border-bottom pb-2 mt-4"><?php echo $formatted_date; ?></h5>
                            <div class="timeline">
                                <?php foreach ($items as $item): ?>
                                <div class="timeline-item">
                                    <div class="timeline-item-time">
                                        <?php echo date('g:i A', strtotime($item['scheduled_time'])); ?>
                                    </div>
                                    <div class="timeline-item-content">
                                        <div class="badge bg-<?php 
                                            echo $item['item_category'] === 'flight' ? 'primary' : 
                                                ($item['item_category'] === 'accommodation' ? 'success' : 'info'); 
                                        ?> mb-2">
                                            <?php echo ucfirst($item['item_category']); ?>
                                        </div>
                                        <h6><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                        <?php if (!empty($item['notes'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Destinations -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Destinations</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trip_destinations)): ?>
                        <p class="text-muted">No destinations added yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($trip_destinations as $destination): ?>
                            <div class="list-group-item">
                                <h6><?php echo htmlspecialchars($destination['location_name']); ?></h6>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars($destination['city']); ?>, 
                                    <?php echo htmlspecialchars($destination['country']); ?>
                                </div>
                                
                                <?php if (!empty($destination['arrival_date']) && !empty($destination['departure_date'])): ?>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo date('M d', strtotime($destination['arrival_date'])); ?> - 
                                    <?php echo date('M d', strtotime($destination['departure_date'])); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($destination['notes'])): ?>
                                <div class="mt-2 small">
                                    <?php echo htmlspecialchars($destination['notes']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="itinerary.php?trip_id=<?php echo $trip_id; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-calendar-check"></i> Edit Itinerary
                        </a>
                        
                        <?php if ($trip['status'] !== 'cancelled'): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelTripModal">
                            <i class="bi bi-x-circle"></i> Cancel Trip
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#deleteTripModal">
                            <i class="bi bi-trash"></i> Delete Trip
                        </button>
                        
                        <a href="my-trips.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to My Trips
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Trip Modal -->
<div class="modal fade" id="cancelTripModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your trip to <?php echo htmlspecialchars($trip['main_destination'] ?? $trip['name']); ?>?</p>
                <p class="text-muted">This will mark your trip as cancelled, but all your planning will be preserved.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Trip</button>
                <a href="cancel-trip.php?id=<?php echo $trip_id; ?>" class="btn btn-danger">Yes, Cancel Trip</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Trip Modal -->
<div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>permanently delete</strong> your trip to <?php echo htmlspecialchars($trip['main_destination'] ?? $trip['name']); ?>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will remove all trip data including itinerary, destinations, and bookings.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Trip</button>
                <a href="delete-trip.php?id=<?php echo $trip_id; ?>" class="btn btn-danger">Yes, Delete Trip</a>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -30px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
}
.timeline-item-time {
    font-weight: bold;
    margin-bottom: 5px;
}
</style>

<?php include 'footer.php'; ?>