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
    $_SESSION['error_message'] = "Trip not found or you don't have permission to cancel it.";
    header("Location: my-trips.php");
    exit();
}

// Update trip status to cancelled
$update_sql = "UPDATE Trips SET status = 'cancelled' WHERE trip_id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $trip_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Trip cancelled successfully.";
} else {
    $_SESSION['error_message'] = "Failed to cancel trip: " . $conn->error;
}

// Redirect back to my trips page
header("Location: my-trips.php");
exit();
?>