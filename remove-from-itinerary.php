<?php
session_start();
require_once 'includes/db_connect.php';

// Check for different ID types
$attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : null;
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : null;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null; // For backward compatibility

// Determine what to delete
if ($attraction_id) {
    $item_type = 'attraction';
    $column = 'attraction_id';
    $item_id = $attraction_id;
} elseif ($flight_id) {
    $item_type = 'flight';
    $column = 'flight_id';
    $item_id = $flight_id;
} elseif ($room_id) {
    $item_type = 'room';
    $column = 'room_id';
    $item_id = $room_id;
} elseif ($id) {
    // For backward compatibility
    $item_type = 'attraction';
    $column = 'attraction_id';
    $item_id = $id;
} else {
    // No valid ID
    $_SESSION['itinerary_message'] = "No item specified to remove.";
    $_SESSION['itinerary_message_type'] = "danger";
    header("Location: itinerary.php");
    exit();
}

// Get user ID
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_user_id'])) {
        $_SESSION['guest_user_id'] = uniqid('guest_', true);
    }
    $user_id = $_SESSION['guest_user_id'];
    $is_guest = 1;
} else {
    $user_id = intval($_SESSION['user_id']);
    $is_guest = 0;
}

// Delete from itinerary
$sql = "DELETE FROM UserItinerary WHERE user_id = ? AND $column = ? AND is_guest = ?";
$stmt = $conn->prepare($sql);

// Bind parameters
if (!isset($_SESSION['user_id'])) {
    $stmt->bind_param("sis", $user_id, $item_id, $is_guest);
} else {
    $stmt->bind_param("iii", $user_id, $item_id, $is_guest);
}

if ($stmt->execute()) {
    $_SESSION['itinerary_message'] = "Item removed from your itinerary.";
    $_SESSION['itinerary_message_type'] = "success";
} else {
    $_SESSION['itinerary_message'] = "Failed to remove item: " . $conn->error;
    $_SESSION['itinerary_message_type'] = "danger";
}

// Redirect
header("Location: itinerary.php");
exit();
?>