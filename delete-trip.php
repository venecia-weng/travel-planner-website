<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get trip ID
$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the trip belongs to the user
$user_id = $_SESSION['user_id'];
$check_sql = "SELECT * FROM Trips WHERE trip_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $trip_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Trip not found or doesn't belong to user
    $_SESSION['error_message'] = "Trip not found or you don't have permission to delete it.";
    header("Location: my-trips.php");
    exit();
}

// Begin transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Delete trip itinerary items
    $delete_itinerary_sql = "DELETE FROM Trip_Itinerary WHERE trip_id = ?";
    $stmt = $conn->prepare($delete_itinerary_sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Delete trip destinations
    $delete_destinations_sql = "DELETE FROM Trip_Destinations WHERE trip_id = ?";
    $stmt = $conn->prepare($delete_destinations_sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Delete trip flights
    $delete_flights_sql = "DELETE FROM Trip_Flights WHERE trip_id = ?";
    $stmt = $conn->prepare($delete_flights_sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Delete trip rooms
    $delete_rooms_sql = "DELETE FROM Trip_Rooms WHERE trip_id = ?";
    $stmt = $conn->prepare($delete_rooms_sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Finally delete the trip itself
    $delete_trip_sql = "DELETE FROM Trips WHERE trip_id = ?";
    $stmt = $conn->prepare($delete_trip_sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Trip deleted successfully.";
} catch (Exception $e) {
    // If an error occurs, roll back the transaction
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to delete trip: " . $e->getMessage();
}

// Redirect back to my trips page
header("Location: my-trips.php");
exit();
?>