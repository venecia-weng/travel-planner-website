<?php
session_start();
require_once 'includes/db_connect.php';

// Check if an ID is provided for any of the item types
$attraction_id = isset($_GET['attraction_id']) ? intval($_GET['attraction_id']) : null;
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : null;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
$check_in_date = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');

// Determine which type of item is being added
if ($attraction_id) {
    $item_type = 'attraction';
    $item_id = $attraction_id;
    $id_column = 'attraction_id';
    $item_name = "attraction";
} elseif ($flight_id) {
    $item_type = 'flight';
    $item_id = $flight_id;
    $id_column = 'flight_id';
    $item_name = "flight";
} elseif ($room_id) {
    $item_type = 'room';
    $item_id = $room_id;
    $id_column = 'room_id';
    $item_name = "room";
} else {
    // No valid ID provided
    $_SESSION['itinerary_message'] = "No item specified.";
    $_SESSION['itinerary_message_type'] = "danger";
    
    // Check if this is an AJAX request
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        echo json_encode(['success' => false, 'message' => 'No item specified.']);
        exit;
    }
    
    header("Location: itinerary.php");
    exit();
}

// Determine the user ID (either logged in or guest)
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_user_id'])) {
        $_SESSION['guest_user_id'] = uniqid('guest_', true);
    }
    $user_id = $_SESSION['guest_user_id'];
    $is_guest = 1; // Using 1 instead of true for database storage
} else {
    $user_id = intval($_SESSION['user_id']);
    $is_guest = 0; // Using 0 instead of false for database storage
}

// Check if the item exists
$check_sql = "";
if ($item_type === 'attraction') {
    $check_sql = "SELECT * FROM Attractions WHERE attraction_id = ? AND status = 'active'";
} elseif ($item_type === 'flight') {
    $check_sql = "SELECT * FROM flights WHERE flight_id = ?";
} elseif ($item_type === 'room') {
    $check_sql = "SELECT r.*, h.hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.hotel_id WHERE r.room_id = ?";
}

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $item_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['itinerary_message'] = ucfirst($item_name) . " not found.";
    $_SESSION['itinerary_message_type'] = "danger";
    
    // Check if this is an AJAX request
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        echo json_encode(['success' => false, 'message' => ucfirst($item_name) . ' not found.']);
        exit;
    }
    
    header("Location: itinerary.php");
    exit();
}

// Get item details for date/time if needed
$item_details = $check_result->fetch_assoc();

// Determine appropriate date and time for the itinerary
$item_date = date('Y-m-d');
$time_slot = "09:00:00"; // Default time

if ($item_type === 'flight') {
    $item_date = date('Y-m-d', strtotime($item_details['departure_date_time']));
    $time_slot = date('H:i:s', strtotime($item_details['departure_date_time']));
} elseif ($item_type === 'room') {
    $item_date = $check_in_date;
    $time_slot = "14:00:00"; // Typical hotel check-in time
}

// Check if this item already exists in user's itinerary
$check_exists_sql = "SELECT * FROM UserItinerary WHERE user_id = ? AND $id_column = ? AND is_guest = ?";
$check_exists_stmt = $conn->prepare($check_exists_sql);

// Bind parameters based on user type
if (!isset($_SESSION['user_id'])) {
    // Guest user (string ID)
    $check_exists_stmt->bind_param("sis", $user_id, $item_id, $is_guest);
} else {
    // Regular user (integer ID)
    $check_exists_stmt->bind_param("iii", $user_id, $item_id, $is_guest);
}

$check_exists_stmt->execute();
$check_exists_result = $check_exists_stmt->get_result();

// If item is already in itinerary, update the message accordingly
if ($check_exists_result->num_rows > 0) {
    $_SESSION['itinerary_message'] = "This " . $item_name . " is already in your itinerary.";
    $_SESSION['itinerary_message_type'] = "info";
    
    // Check if this is an AJAX request
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        echo json_encode(['success' => true, 'message' => 'This ' . $item_name . ' is already in your itinerary.']);
        exit;
    }
    
    header("Location: itinerary.php");
    exit();
}

// Build the SQL query - set all other item types to NULL
$sql = "INSERT INTO UserItinerary (user_id, attraction_id, flight_id, room_id, is_guest, item_date, time_slot) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Set values for the query
$attraction_id_param = ($item_type === 'attraction') ? $item_id : NULL;
$flight_id_param = ($item_type === 'flight') ? $item_id : NULL;
$room_id_param = ($item_type === 'room') ? $item_id : NULL;

// Bind parameters based on user type
if (!isset($_SESSION['user_id'])) {
    // Guest user (string ID)
    $stmt->bind_param("siiiiss", $user_id, $attraction_id_param, $flight_id_param, $room_id_param, $is_guest, $item_date, $time_slot);
} else {
    // Regular user (integer ID)
    $stmt->bind_param("iiiiiss", $user_id, $attraction_id_param, $flight_id_param, $room_id_param, $is_guest, $item_date, $time_slot);
}

if ($stmt->execute()) {
    $_SESSION['itinerary_message'] = ucfirst($item_name) . " added to your itinerary successfully!";
    $_SESSION['itinerary_message_type'] = "success";
    
    // Check if this is an AJAX request
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        echo json_encode(['success' => true, 'message' => ucfirst($item_name) . ' added to your itinerary successfully!']);
        exit;
    }
} else {
    $_SESSION['itinerary_message'] = "Failed to add " . $item_name . " to your itinerary: " . $conn->error;
    $_SESSION['itinerary_message_type'] = "danger";
    
    // Check if this is an AJAX request
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        echo json_encode(['success' => false, 'message' => 'Failed to add ' . $item_name . ' to your itinerary: ' . $conn->error]);
        exit;
    }
}

// Redirect to itinerary page if not an AJAX request
header("Location: itinerary.php");
exit();
?>