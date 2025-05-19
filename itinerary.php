<?php
session_start();
require_once 'includes/db_connect.php';

// Check for trip_id parameter if we're loading an existing trip
$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : null;
$trip = null;
$trip_itinerary_items = [];

// Determine the user ID (either logged in or guest)
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_user_id'])) {
        $_SESSION['guest_user_id'] = uniqid('guest_', true);
    }
    $user_id = $_SESSION['guest_user_id'];
    $is_guest = 1; // Using 1 instead of true for database storage
    
    // If trip_id is provided but user is not logged in, redirect to login
    if ($trip_id) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php?redirect=1");
        exit();
    }
} else {
    $user_id = intval($_SESSION['user_id']);
    $is_guest = 0; // Using 0 instead of false for database storage
    
    // If trip_id is provided, check if this trip belongs to the user
    if ($trip_id) {
        $trip_sql = "SELECT * FROM Trips WHERE trip_id = ? AND user_id = ?";
        $stmt = $conn->prepare($trip_sql);
        $stmt->bind_param("ii", $trip_id, $user_id);
        $stmt->execute();
        $trip_result = $stmt->get_result();
        
        if ($trip_result->num_rows > 0) {
            $trip = $trip_result->fetch_assoc();
            
            // Set default date range from trip dates if available
            if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                $today = date("d/m/Y", strtotime($trip['start_date']));
                $defaultEndDate = date("d/m/Y", strtotime($trip['end_date']));
            }
            
            // Load trip's itinerary items from Trip_Itinerary table
            $trip_items_sql = "SELECT ti.*, 
                   CASE 
                     WHEN ti.item_type = 'attraction' THEN 'attraction'
                     WHEN ti.item_type = 'flight' THEN 'flight'
                     WHEN ti.item_type = 'room' THEN 'room'
                   END as type,
                   CASE 
                     WHEN ti.item_type = 'attraction' THEN (SELECT name FROM Attractions WHERE attraction_id = ti.item_id) 
                     WHEN ti.item_type = 'flight' THEN (SELECT flight_number FROM flights WHERE flight_id = ti.item_id)
                     WHEN ti.item_type = 'room' THEN (SELECT room_type FROM rooms WHERE room_id = ti.item_id)
                     ELSE 'Unknown'
                   END as item_name
                   FROM Trip_Itinerary ti
                   WHERE ti.trip_id = ?
                   ORDER BY ti.scheduled_date, ti.scheduled_time";
            
            $stmt = $conn->prepare($trip_items_sql);
            $stmt->bind_param("i", $trip_id);
            $stmt->execute();
            $trip_items_result = $stmt->get_result();
            
            if ($trip_items_result->num_rows > 0) {
                while ($row = $trip_items_result->fetch_assoc()) {
                    $trip_itinerary_items[] = $row;
                }
            }
            $stmt->close();
        } else {
            // Trip not found or doesn't belong to user
            $itinerary_message = "Trip not found or you don't have permission to access it.";
            $itinerary_message_type = "danger";
            $trip_id = null;
        }
    }
}

// Get attractions from the user's itinerary
$attractions_sql = "SELECT ui.*, a.name, a.description, a.estimated_time_minutes, a.image_url, a.category,
               d.location_name, d.country, d.city, 'attraction' as item_type
        FROM UserItinerary ui
        JOIN Attractions a ON ui.attraction_id = a.attraction_id
        JOIN Destinations d ON a.destination_id = d.destination_id
        WHERE ui.user_id = ? AND ui.is_guest = ? AND ui.attraction_id IS NOT NULL
        ORDER BY ui.item_date, ui.time_slot";

// Get flights from the user's itinerary
$flights_sql = "SELECT ui.*, f.flight_id, f.flight_number, f.origin, f.destination, 
                f.departure_date_time, a.name as name, a.logo_url as image_url, 
                'flight' as item_type, 'Flight' as category
        FROM UserItinerary ui
        JOIN flights f ON ui.flight_id = f.flight_id
        JOIN airlines a ON f.airline_id = a.airline_id
        WHERE ui.user_id = ? AND ui.is_guest = ? AND ui.flight_id IS NOT NULL
        ORDER BY ui.item_date, ui.time_slot";

// Get rooms from the user's itinerary
$rooms_sql = "SELECT ui.*, r.room_id, r.room_type as name, r.main_img_url as image_url, 
              h.hotel_name, h.city, h.country, 'room' as item_type,
              'Accommodation' as category
        FROM UserItinerary ui
        JOIN rooms r ON ui.room_id = r.room_id
        JOIN hotels h ON r.hotel_id = h.hotel_id
        WHERE ui.user_id = ? AND ui.is_guest = ? AND ui.room_id IS NOT NULL
        ORDER BY ui.item_date, ui.time_slot";

// Fetch attractions
$stmt = $conn->prepare($attractions_sql);
if (!isset($_SESSION['user_id'])) {
    $stmt->bind_param("si", $user_id, $is_guest);
} else {
    $stmt->bind_param("ii", $user_id, $is_guest);
}
$stmt->execute();
$result = $stmt->get_result();
$attractions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attractions[] = $row;
    }
}
$stmt->close();

// Fetch flights
$stmt = $conn->prepare($flights_sql);
if (!isset($_SESSION['user_id'])) {
    $stmt->bind_param("si", $user_id, $is_guest);
} else {
    $stmt->bind_param("ii", $user_id, $is_guest);
}
$stmt->execute();
$result = $stmt->get_result();
$flights = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }
}
$stmt->close();

// Fetch rooms
$stmt = $conn->prepare($rooms_sql);
if (!isset($_SESSION['user_id'])) {
    $stmt->bind_param("si", $user_id, $is_guest);
} else {
    $stmt->bind_param("ii", $user_id, $is_guest);
}
$stmt->execute();
$result = $stmt->get_result();
$rooms = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$stmt->close();

// Combine all items
$itinerary_items = array_merge($attractions, $flights, $rooms);

$itinerary_message = isset($itinerary_message) ? $itinerary_message : '';
$itinerary_message_type = isset($itinerary_message_type) ? $itinerary_message_type : '';

if (isset($_SESSION['itinerary_message'])) {
    $itinerary_message = $_SESSION['itinerary_message'];
    $itinerary_message_type = $_SESSION['itinerary_message_type'] ?? 'info';

    unset($_SESSION['itinerary_message']);
    unset($_SESSION['itinerary_message_type']);
}

// Always initialize with today's date as fallback
$today = date("d/m/Y");
$defaultEndDate = date('d/m/Y', strtotime('+7 days'));

// Only override if valid trip dates exist
if (isset($trip) && !empty($trip['start_date']) && !empty($trip['end_date'])) {
    $today = date("d/m/Y", strtotime($trip['start_date']));
    $defaultEndDate = date("d/m/Y", strtotime($trip['end_date']));
}

// Prepare the trip data for JavaScript
if ($trip) {
    // Format the trip data for JavaScript
    $js_trip = array(
        'trip_id' => $trip['trip_id'],
        'name' => $trip['name'],
        'start_date' => $trip['start_date'],
        'end_date' => $trip['end_date'],
        'status' => $trip['status']
    );
    
    // Output the JavaScript variables
    echo "<script>\n";
    echo "const tripData = " . json_encode($js_trip) . ";\n";
    echo "const tripItineraryItems = " . json_encode($trip_itinerary_items) . ";\n";
    echo "</script>\n";
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .itinerary-container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0;
        }

        .date-picker-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            text-align: center;
        }

        .date-picker-container input {
            padding: 12px 20px;
            font-size: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            width: 300px;
            text-align: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .attractions-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .attraction-items {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .attraction {
            display: flex;
            align-items: center;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: white;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: grab;
            width: 250px;
        }

        .attraction:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        .attraction img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 12px;
        }

        .attraction-info {
            flex: 1;
        }

        .attraction-name {
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
            color: #343a40;
        }

        .attraction-location {
            font-size: 0.85em;
            color: #6c757d;
            display: block;
            margin-bottom: 2px;
        }

        .attraction-category {
            display: inline-block;
            font-size: 0.7em;
            background-color: #e9ecef;
            color: #495057;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 2px;
        }

        .attraction-time {
            font-size: 0.75em;
            color: #495057;
            display: block;
            margin-top: 4px;
        }

        .attraction .remove-btn {
            color: #dc3545;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .attraction .remove-btn:hover {
            background-color: #ffebee;
        }

        .schedule-section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .itinerary-grid {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding: 20px;
            gap: 20px;
        }

        .itinerary-day {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            min-width: 300px;
            flex: 0 0 300px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .day-header {
            background-color:rgb(46, 112, 184);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }

        .time-slot {
            margin: 10px 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .time-slot-header {
            background-color: #f1f3f5;
            padding: 8px 12px;
            font-weight: 500;
            font-size: 0.9em;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
        }

        .drop-area {
            padding: 15px;
            min-height: 60px;
            border: 2px dashed #dee2e6;
            border-radius: 6px;
            margin: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5a6268; /* Darker gray for better contrast */
            font-size: 0.9em;
            transition: all 0.2s;
        }

        .drop-area.highlight {
            background-color: #e8f4ff;
            border-color: #007bff;
        }

        .dropped-attraction {
            background-color: #e8f4ff;
            margin: 10px;
            padding: 12px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropped-attraction-content {
            flex: 1;
        }

        .dropped-attraction .remove-from-slot {
            background: none;
            border: none;
            color: #dc3545;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            padding: 0;
            margin-left: 8px;
        }

        .dropped-attraction .remove-from-slot:hover {
            background-color: #ffebee;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
            padding: 0 20px 20px;
        }

        .action-button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .save-button {
            background-color:rgb(46, 112, 184);
            color: white;
        }

        .save-button:hover {
            background-color:rgb(50, 129, 214);
        }

        .print-button {
            background-color: #6c757d;
            color: white;
        }

        .print-button:hover {
            background-color: #5a6268;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Activity labels */
        .activity-label {
            display: inline-block;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 12px;
            margin-right: 5px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .label-activity {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .label-food {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .label-travel {
            background-color: #fff8e1;
            color: #ff6f00;
        }

        .label-other {
            background-color: #f3e5f5;
            color: #6a1b9a;
        }

        .flight {
            border-left: 4px solid #007bff;
        }
        
        .room {
            border-left: 4px solid #28a745;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .filter-button {
            padding: 8px 15px;
            border-radius: 20px;
            border: none;
            background-color: #f1f3f5;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .filter-button.active {
            background-color:rgb(46, 112, 184);
            color: white;
        }
        
        .filter-button:hover:not(.active) {
            background-color: #e9ecef;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .attraction {
                width: 100%;
            }
            
            .itinerary-day {
                min-width: 100%;
                flex: 0 0 100%;
            }
            
            .itinerary-grid {
                flex-direction: column;
                overflow-x: hidden;
            }
        }
    </style>
</head>
<body>

    <div class="itinerary-container">
        <h1 class="section-title">Your Travel Itinerary</h1>

        <?php if (!empty($itinerary_message)): ?>
        <div class="alert alert-<?php echo $itinerary_message_type; ?>">
            <?php echo $itinerary_message; ?>
        </div>
        <?php endif; ?>

        <div class="date-picker-container">
            <label for="tripDates" class="mb-2 d-block">Select Your Trip Dates</label>
            <input type="text" id="tripDates" placeholder="Select your trip dates" readonly>
        </div>

        <div class="attractions-section">
            <h2 class="section-title">Your Itinerary Items</h2>
            <p>Drag and drop these items to your schedule below</p>
            
            <!-- Add filter buttons -->
            <div class="filter-buttons">
                <button class="filter-button active" data-filter="all">All Items</button>
                <button class="filter-button" data-filter="attraction">Attractions</button>
                <button class="filter-button" data-filter="flight">Flights</button>
                <button class="filter-button" data-filter="room">Accommodations</button>
            </div>
            
            <div id="attractionItems" class="attraction-items">
                <?php if (empty($itinerary_items)): ?>
                    <p>No items added yet. Browse <a href="destinations.php">destinations</a>, <a href="flights.php">flights</a>, or <a href="hotels.php">hotels</a> to add items to your itinerary.</p>
                <?php else: ?>
                    <?php foreach ($itinerary_items as $item): ?>
                        <div class="attraction <?php echo $item['item_type']; ?>" draggable="true" 
                             data-id="<?php echo isset($item['attraction_id']) ? $item['attraction_id'] : (isset($item['flight_id']) ? $item['flight_id'] : $item['room_id']); ?>"
                             data-type="<?php echo $item['item_type']; ?>">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class="placeholder-img" style="width:60px;height:60px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;margin-right:12px;">
                                    <i class="fas fa-<?php echo $item['item_type'] === 'flight' ? 'plane' : ($item['item_type'] === 'room' ? 'bed' : 'image'); ?> text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="attraction-info">
                                <span class="attraction-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                
                                <span class="attraction-location">
                                    <i class="fas fa-<?php echo $item['item_type'] === 'flight' ? 'plane' : ($item['item_type'] === 'room' ? 'hotel' : 'map-marker-alt'); ?>"></i> 
                                    <?php if ($item['item_type'] === 'flight'): ?>
                                        <?php echo htmlspecialchars($item['origin']); ?> to <?php echo htmlspecialchars($item['destination']); ?>
                                    <?php elseif ($item['item_type'] === 'room'): ?>
                                        <?php echo htmlspecialchars($item['hotel_name']); ?>, <?php echo htmlspecialchars($item['city']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($item['location_name']); ?>
                                    <?php endif; ?>
                                </span>
                                
                                <?php if (!empty($item['category'])): ?>
                                    <span class="attraction-category">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($item['item_type'] === 'flight'): ?>
                                    <span class="attraction-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('h:i A', strtotime($item['departure_date_time'])); ?>
                                    </span>
                                <?php elseif ($item['item_type'] === 'attraction' && !empty($item['estimated_time_minutes'])): ?>
                                    <span class="attraction-time">
                                        <i class="far fa-clock"></i>
                                        <?php 
                                            $hours = floor($item['estimated_time_minutes'] / 60);
                                            $minutes = $item['estimated_time_minutes'] % 60;
                                            
                                            if ($hours > 0) {
                                                echo $hours . 'h ';
                                            }
                                            if ($minutes > 0 || $hours == 0) {
                                                echo $minutes . 'm';
                                            }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="remove-from-itinerary.php?<?php echo $item['item_type']; ?>_id=<?php echo isset($item['attraction_id']) ? $item['attraction_id'] : (isset($item['flight_id']) ? $item['flight_id'] : $item['room_id']); ?>" class="remove-btn" title="Remove from itinerary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="schedule-section">
            <div id="itineraryGrid" class="itinerary-grid" tabindex="0">
                <!-- Itinerary days will be dynamically created here -->
            </div>
            
            <div class="action-buttons">
                <button id="saveItineraryBtn" class="action-button save-button">
                    <i class="fas fa-save"></i> Save Itinerary
                </button>
                <button id="printItineraryBtn" class="action-button print-button">
                    <i class="fas fa-print"></i> Print Itinerary
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for date selection
        flatpickr("#tripDates", {
            mode: "range",
            dateFormat: "d/m/Y",
            defaultDate: ["<?php echo $today; ?>", "<?php echo $defaultEndDate; ?>"],
            minDate: "today",
            maxDate: new Date().fp_incr(365),
            locale: {
                firstDayOfWeek: 1,
                weekdays: {
                    shorthand: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    longhand: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
                },
                months: {
                    shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    longhand: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
                }
            },
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    generateItineraryGrid(selectedDates[0], selectedDates[1]);
                }
            }
        });

        const datePickerInstance = document.querySelector("#tripDates")._flatpickr;
        if (datePickerInstance.selectedDates.length === 2) {
            generateItineraryGrid(datePickerInstance.selectedDates[0], datePickerInstance.selectedDates[1]);
        }

        // Filter buttons functionality
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                // Update active state
                document.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filterValue = this.getAttribute('data-filter');
                
                // Filter items
                document.querySelectorAll('.attraction').forEach(item => {
                    if (filterValue === 'all' || item.classList.contains(filterValue)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function formatDayOfWeek(date) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return days[date.getDay()];
        }

        function generateItineraryGrid(startDate, endDate) {
            const itineraryGrid = document.getElementById('itineraryGrid');
            itineraryGrid.innerHTML = '';

            const currentDate = new Date(startDate);
            const endDateObj = new Date(endDate);
        
            while (currentDate <= endDateObj) {
                const formattedDate = formatDate(currentDate);
                const dayOfWeek = formatDayOfWeek(currentDate);
                const dateStr = currentDate.toISOString().split('T')[0];
            
                const dayDiv = document.createElement('div');
                dayDiv.classList.add('itinerary-day');
                dayDiv.setAttribute('data-date', dateStr);
                
                // Create day header with formatted date and day of week
                const dayHeader = document.createElement('div');
                dayHeader.classList.add('day-header');
                dayHeader.textContent = `${dayOfWeek}, ${formattedDate}`;
                dayDiv.appendChild(dayHeader);

                // Create time slots for morning, afternoon, and evening
                const timeSlots = [
                    { time: '09:00:00', display: '9:00 AM' },
                    { time: '10:00:00', display: '10:00 AM' },
                    { time: '11:00:00', display: '11:00 AM' },
                    { time: '12:00:00', display: '12:00 PM' },
                    { time: '13:00:00', display: '1:00 PM' },
                    { time: '14:00:00', display: '2:00 PM' },
                    { time: '15:00:00', display: '3:00 PM' },
                    { time: '16:00:00', display: '4:00 PM' },
                    { time: '17:00:00', display: '5:00 PM' },
                    { time: '18:00:00', display: '6:00 PM' },
                    { time: '19:00:00', display: '7:00 PM' },
                    { time: '20:00:00', display: '8:00 PM' },
                    { time: '21:00:00', display: '9:00 PM' },
                    { time: '22:00:00', display: '10:00 PM' },
                    { time: '23:00:00', display: '11:00 PM' },
                ];

                timeSlots.forEach(slot => {
                    const timeSlotDiv = document.createElement('div');
                    timeSlotDiv.classList.add('time-slot');
                    timeSlotDiv.setAttribute('data-time', slot.time);
                    timeSlotDiv.setAttribute('data-date', dateStr);
                    
                    // Time slot header
                    const timeHeader = document.createElement('div');
                    timeHeader.classList.add('time-slot-header');
                    timeHeader.textContent = slot.display;
                    timeSlotDiv.appendChild(timeHeader);
                    
                    // Drop area
                    const dropArea = document.createElement('div');
                    dropArea.classList.add('drop-area');
                    dropArea.textContent = 'Drop item here';
                    timeSlotDiv.appendChild(dropArea);
                    
                    dayDiv.appendChild(timeSlotDiv);
                });

                itineraryGrid.appendChild(dayDiv);
                
                // Move to the next day
                currentDate.setDate(currentDate.getDate() + 1);
            }

            // Set up drag and drop
            addDragAndDropListeners();
        }

        function addDragAndDropListeners() {
            document.querySelectorAll('.attraction').forEach(attraction => {
                attraction.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('application/json', JSON.stringify({
                        id: this.getAttribute('data-id'),
                        type: this.getAttribute('data-type'),
                        name: this.querySelector('.attraction-name').textContent,
                        category: this.querySelector('.attraction-category')?.textContent.trim(),
                        location: this.querySelector('.attraction-location')?.textContent.trim(),
                        duration: this.querySelector('.attraction-time')?.textContent.trim()
                    }));
                });
            });

            document.querySelectorAll('.drop-area').forEach(area => {
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('highlight');
                });
                
                area.addEventListener('dragleave', function() {
                    this.classList.remove('highlight');
                });
                
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('highlight');
                    
                    const data = JSON.parse(e.dataTransfer.getData('application/json'));
                    const timeSlot = this.parentElement;
                    const date = timeSlot.getAttribute('data-date');
                    const time = timeSlot.getAttribute('data-time');

                    // Create a compact version of the item for display in the schedule
                    const droppedAttractionDiv = document.createElement('div');
                    droppedAttractionDiv.classList.add('dropped-attraction');
                    droppedAttractionDiv.setAttribute('data-id', data.id);
                    droppedAttractionDiv.setAttribute('data-type', data.type || 'attraction');
                    
                    // Determine label class based on item type and category
                    let labelClass = 'label-other';
                    let labelText = data.category || 'Activity';
                    
                    if (data.type === 'flight') {
                        labelClass = 'label-travel';
                        labelText = 'Flight';
                    } else if (data.type === 'room') {
                        labelClass = 'label-other';
                        labelText = 'Accommodation';
                    } else if (data.category) {
                        if (data.category.toLowerCase().includes('food') || data.category.toLowerCase().includes('restaurant')) {
                            labelClass = 'label-food';
                        } else if (data.category.toLowerCase().includes('activity') || data.category.toLowerCase().includes('tour')) {
                            labelClass = 'label-activity';
                        } else if (data.category.toLowerCase().includes('transport') || data.category.toLowerCase().includes('travel')) {
                            labelClass = 'label-travel';
                        }
                    }
                    
                    droppedAttractionDiv.innerHTML = `
                        <div class="dropped-attraction-content">
                            <div class="activity-label ${labelClass}">${labelText}</div>
                            <strong>${data.name}</strong>
                            ${data.location ? `<div class="small text-muted">${data.location}</div>` : ''}
                            ${data.duration ? `<div class="small text-muted">${data.duration}</div>` : ''}
                        </div>
                        <button class="remove-from-slot" title="Remove from schedule">×</button>
                    `;

                    this.replaceWith(droppedAttractionDiv);

                    // Save to database
                    saveItemToTimeSlot(data.id, data.type || 'attraction', date, time);

                    // Add remove button functionality
                    droppedAttractionDiv.querySelector('.remove-from-slot').addEventListener('click', function() {
                        const dropArea = document.createElement('div');
                        dropArea.classList.add('drop-area');
                        dropArea.textContent = 'Drop item here';

                        dropArea.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            this.classList.add('highlight');
                        });
                        
                        dropArea.addEventListener('dragleave', function() {
                            this.classList.remove('highlight');
                        });
                        
                        dropArea.addEventListener('drop', function(e) {
                            e.preventDefault();
                            const dropEvent = new Event('drop');
                            dropEvent.dataTransfer = e.dataTransfer;
                            this.dispatchEvent(dropEvent);
                        });
                        
                        droppedAttractionDiv.replaceWith(dropArea);
                    });
                });
            });
        }

        function saveItemToTimeSlot(itemId, itemType, date, time) {
            const formData = new FormData();
            formData.append(itemType + '_id', itemId);
            formData.append('date', date);
            formData.append('time', time);

            fetch('save-to-itinerary.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Item scheduled successfully!', 'success');
                } else {
                    console.error('Error saving to itinerary:', data.message);
                    showNotification('Failed to schedule: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while saving to your schedule.', 'danger');
            });
        }
        
        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.maxWidth = '300px';
            notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            notification.style.transition = 'all 0.3s ease';
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(20px)';
            
            notification.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>${message}</div>
                    <button type="button" style="background:none;border:none;font-size:1.2em;line-height:1;cursor:pointer;padding:0 0 0 10px;" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
            }, 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(20px)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Print itinerary button
        document.getElementById('printItineraryBtn').addEventListener('click', function() {
            window.print();
        });
    });

    function saveItineraryToTrip() {
    // Check if user is logged in
    <?php if (!isset($_SESSION['user_id'])): ?>
        // Show login prompt
        showNotification('Please log in to save your itinerary to a trip.', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    <?php endif; ?>
    
    // Collect all scheduled items
    const scheduledItems = [];
    document.querySelectorAll('.dropped-attraction').forEach(item => {
        const timeSlot = item.closest('.time-slot');
        if (timeSlot) {
            scheduledItems.push({
                item_id: item.getAttribute('data-id'),
                item_type: item.getAttribute('data-type'),
                date: timeSlot.getAttribute('data-date'),
                time: timeSlot.getAttribute('data-time')
            });
        }
    });
    
    // If no items are scheduled, show a message
    if (scheduledItems.length === 0) {
        showNotification('Please add items to your schedule before saving.', 'warning');
        return;
    }
    
    // Prompt for trip name using native prompt (more reliable than modal)
    const tripName = prompt('Enter a name for your trip:', 'My Trip ' + new Date().toLocaleDateString());
    
    if (!tripName) {
        // User canceled
        return;
    }
    
    // Show loading indicator
    const saveBtn = document.getElementById('saveItineraryBtn');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    // Ask for trip description
    const tripDescription = prompt('Enter a description for your trip (optional):', '');
    
    // Get date range from the date picker
    const datePickerInstance = document.querySelector("#tripDates")._flatpickr;
    let startDate = null;
    let endDate = null;
    if (datePickerInstance.selectedDates.length === 2) {
        startDate = datePickerInstance.selectedDates[0].toISOString().split('T')[0];
        endDate = datePickerInstance.selectedDates[1].toISOString().split('T')[0];
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('items', JSON.stringify(scheduledItems));
    formData.append('trip_name', tripName);
    formData.append('trip_description', tripDescription || '');
    
    // Also include trip ID if we're editing an existing trip
    const urlParams = new URLSearchParams(window.location.search);
    const tripId = urlParams.get('trip_id');
    if (tripId) {
        formData.append('trip_id', tripId);
    }
    
    if (startDate && endDate) {
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);
    }
    
    // Send data to server
    fetch('save-itinerary-to-trip.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset save button
        saveBtn.innerHTML = originalBtnText;
        saveBtn.disabled = false;
        
        if (data.success) {
            // Show success notification
            showNotification(`Trip "${tripName}" saved successfully!`, 'success');
            
            // Add success message to page
            const successMessage = document.createElement('div');
            successMessage.className = 'alert alert-success mt-3';
            successMessage.innerHTML = `
                <h5><i class="fas fa-check-circle"></i> Itinerary Saved!</h5>
                <p>Your itinerary has been saved as: <strong>${tripName}</strong></p>
                <div class="mt-2">
                    <a href="trip-details.php?id=${data.trip_id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> View Trip Details
                    </a>
                    <a href="my-trips.php" class="btn btn-outline-primary btn-sm ms-2">
                        <i class="fas fa-list"></i> See All My Trips
                    </a>
                </div>
            `;
            
            // Insert after the action buttons
            const actionButtonsDiv = document.querySelector('.action-buttons');
            actionButtonsDiv.parentNode.insertBefore(successMessage, actionButtonsDiv.nextSibling);
            
            // Scroll to the message
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // If we're creating a new trip (not editing), update URL with trip_id
            if (!tripId && data.trip_id) {
                // Update URL without reloading
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('trip_id', data.trip_id);
                window.history.pushState({}, '', newUrl);
                
                // Reload the page after 3 seconds to ensure state is fresh
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        } else {
            showNotification('Failed to save: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while saving your itinerary.', 'danger');
        
        // Reset save button
        saveBtn.innerHTML = originalBtnText;
        saveBtn.disabled = false;
    });
    }
    // Update the save button event listener
    document.getElementById('saveItineraryBtn').addEventListener('click', saveItineraryToTrip);

    // Function to check if items are already loaded in schedule
    function checkSchedulePopulated() {
        const existingItems = [];
        document.querySelectorAll('.dropped-attraction').forEach(item => {
            existingItems.push({
                id: item.getAttribute('data-id'),
                type: item.getAttribute('data-type')
            });
        });
        return existingItems;
    }

    // Then when placing items, check against this array
    const existingItems = checkSchedulePopulated();
    const isDuplicate = existingItems.some(item => 
        item.id === tripItem.item_id && item.type === tripItem.item_type);
    if (!isDuplicate) {
        // Place the item
}

    // When page loads, check if trip data is available
    document.addEventListener('DOMContentLoaded', function() {
        // Check if trip data is available
        if (typeof tripData !== 'undefined' && typeof tripItineraryItems !== 'undefined') {
            // Set date range in date picker
            if (tripData.start_date && tripData.end_date) {
                const datePickerInstance = document.querySelector("#tripDates")._flatpickr;
                datePickerInstance.setDate([tripData.start_date, tripData.end_date]);
            }
            
            // Wait for grid to be generated
            setTimeout(() => {
                // Important: Get existing items BEFORE trying to add new ones
                const existingItems = [];
                document.querySelectorAll('.dropped-attraction').forEach(item => {
                    existingItems.push({
                        id: item.getAttribute('data-id'),
                        type: item.getAttribute('data-type')
                    });
                });
                
                // Place items in the schedule, but only if they don't already exist
                tripItineraryItems.forEach(item => {
                    // Check if this item is already in the schedule
                    const isDuplicate = existingItems.some(existingItem => 
                        existingItem.id === item.item_id && 
                        existingItem.type === item.item_type);
                    
                    // Skip if duplicate
                    if (isDuplicate) {
                        return;
                    }
                    
                    // Find the corresponding time slot
                    const date = item.scheduled_date;
                    const time = item.scheduled_time;
                    
                    const timeSlot = document.querySelector(`.time-slot[data-date="${date}"][data-time="${time}"]`);
                    if (timeSlot) {
                        const dropArea = timeSlot.querySelector('.drop-area');
                        if (dropArea) {
                            // Add to tracking array to prevent future duplication
                            existingItems.push({
                                id: item.item_id,
                                type: item.item_type
                            });
                            
                            // Create dropped attraction element
                            const droppedAttractionDiv = document.createElement('div');
                            droppedAttractionDiv.classList.add('dropped-attraction');
                            droppedAttractionDiv.setAttribute('data-id', item.item_id);
                            droppedAttractionDiv.setAttribute('data-type', item.item_type);
                            
                            // Determine label class and text
                            let labelClass = 'label-other';
                            let labelText = item.item_type.charAt(0).toUpperCase() + item.item_type.slice(1);
                            
                            if (item.item_type === 'flight') {
                                labelClass = 'label-travel';
                            } else if (item.item_type === 'room') {
                                labelClass = 'label-other';
                                labelText = 'Accommodation';
                            }
                            
                            droppedAttractionDiv.innerHTML = `
                                <div class="dropped-attraction-content">
                                    <div class="activity-label ${labelClass}">${labelText}</div>
                                    <strong>${item.item_name || 'Item'}</strong>
                                </div>
                                <button class="remove-from-slot" title="Remove from schedule">×</button>
                            `;
                            
                            // Replace the drop area with the item
                            dropArea.replaceWith(droppedAttractionDiv);
                            
                            // Add remove button functionality
                            droppedAttractionDiv.querySelector('.remove-from-slot').addEventListener('click', function() {
                                const newDropArea = document.createElement('div');
                                newDropArea.classList.add('drop-area');
                                newDropArea.textContent = 'Drop item here';
                                
                                // Add event listeners for drag and drop
                                newDropArea.addEventListener('dragover', function(e) {
                                    e.preventDefault();
                                    this.classList.add('highlight');
                                });
                                
                                newDropArea.addEventListener('dragleave', function() {
                                    this.classList.remove('highlight');
                                });
                                
                                newDropArea.addEventListener('drop', function(e) {
                                    e.preventDefault();
                                    this.classList.remove('highlight');
                                    
                                    // Get the data and handle the drop
                                    try {
                                        const data = JSON.parse(e.dataTransfer.getData('application/json'));
                                        const timeSlot = this.closest('.time-slot');
                                        const date = timeSlot.getAttribute('data-date');
                                        const time = timeSlot.getAttribute('data-time');
                                        
                                        // Create a dropped attraction element (similar to above)
                                    } catch (err) {
                                        console.error('Error handling drop:', err);
                                    }
                                });
                                
                                droppedAttractionDiv.replaceWith(newDropArea);
                            });
                        }
                    }
                });
            }, 500); // Small delay to ensure the grid is generated
        }
    });
    </script>
</body>
</html>
    