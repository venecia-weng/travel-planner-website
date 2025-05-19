<?php
// Start session at the beginning of the file
session_start();

// Set up the response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to save an itinerary']);
    exit();
}

// Include database connection
require_once 'includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if we have items to save
if (!isset($_POST['items']) || empty($_POST['items'])) {
    echo json_encode(['success' => false, 'message' => 'No items to save']);
    exit();
}

// Check if trip name is provided
if (!isset($_POST['trip_name']) || empty($_POST['trip_name'])) {
    echo json_encode(['success' => false, 'message' => 'Trip name is required']);
    exit();
}

// Get trip information
$trip_name = trim($_POST['trip_name']);
$trip_description = isset($_POST['trip_description']) ? trim($_POST['trip_description']) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

// Check if we're updating an existing trip
$trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : null;

// Decode the items JSON
$items = json_decode($_POST['items'], true);
if (!$items || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item data']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // If trip_id is provided, verify it belongs to the user and update it
    if ($trip_id) {
        $check_trip_sql = "SELECT * FROM Trips WHERE trip_id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_trip_sql);
        $stmt->bind_param("ii", $trip_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Trip not found or doesn't belong to the user
            throw new Exception("Trip not found or you don't have permission to edit it");
        }
        
        // Update the trip details
        $update_trip_sql = "UPDATE Trips SET name = ?, description = ?, start_date = ?, end_date = ? WHERE trip_id = ?";
        $stmt = $conn->prepare($update_trip_sql);
        $stmt->bind_param("ssssi", $trip_name, $trip_description, $start_date, $end_date, $trip_id);
        $stmt->execute();
        
        // Delete existing itinerary items for this trip to avoid duplicates
        $delete_items_sql = "DELETE FROM Trip_Itinerary WHERE trip_id = ?";
        $stmt = $conn->prepare($delete_items_sql);
        $stmt->bind_param("i", $trip_id);
        $stmt->execute();
    } else {
        // Create a new trip
        $create_trip_sql = "INSERT INTO Trips (user_id, name, description, start_date, end_date, status) 
                            VALUES (?, ?, ?, ?, ?, 'planning')";
        $stmt = $conn->prepare($create_trip_sql);
        $stmt->bind_param("issss", $user_id, $trip_name, $trip_description, $start_date, $end_date);
        $stmt->execute();
        
        $trip_id = $conn->insert_id;
    }
    
    // Now save all itinerary items to the Trip_Itinerary table
    $insert_item_sql = "INSERT INTO Trip_Itinerary (trip_id, item_id, item_type, scheduled_date, scheduled_time, notes) 
                        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_item_sql);
    
    // Count successful inserts
    $success_count = 0;
    
    foreach ($items as $item) {
        // Get item details
        $item_id = $item['item_id'];
        $item_type = $item['item_type'];
        $scheduled_date = $item['date'];
        $scheduled_time = $item['time'];
        $notes = "Added from itinerary planner";
        
        // Insert item
        $stmt->bind_param("iissss", $trip_id, $item_id, $item_type, $scheduled_date, $scheduled_time, $notes);
        
        if ($stmt->execute()) {
            $success_count++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => $success_count . ' items saved to your trip', 
        'trip_id' => $trip_id,
        'trip_name' => $trip_name
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close the database connection
    $conn->close();
}
?>