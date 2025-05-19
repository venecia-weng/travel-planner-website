<?php
// File: save-to-itinerary.php

// Start session
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Create response array
$response = [
    'success' => false,
    'message' => 'No data received'
];

// Determine the user ID (either logged in or guest)
if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['guest_user_id'])) {
        $_SESSION['guest_user_id'] = uniqid('guest_', true);
    }
    $user_id = $_SESSION['guest_user_id'];
    $is_guest = 1; // Using 1 instead of true for database storage
} else {
    $user_id = $_SESSION['user_id'];
    $is_guest = 0; // Using 0 instead of false for database storage
}

// Check which item type is being scheduled
$attraction_id = isset($_POST['attraction_id']) ? intval($_POST['attraction_id']) : null;
$flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : null;
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;

// Determine which type of item is being added
if ($attraction_id) {
    $item_type = 'attraction';
    $item_id = $attraction_id;
    $id_column = 'attraction_id';
    $table = 'Attractions';
    $status_check = "AND status = 'active'";
} elseif ($flight_id) {
    $item_type = 'flight';
    $item_id = $flight_id;
    $id_column = 'flight_id';
    $table = 'flights';
    $status_check = "";
} elseif ($room_id) {
    $item_type = 'room';
    $item_id = $room_id;
    $id_column = 'room_id';
    $table = 'rooms';
    $status_check = "";
} else {
    $response['message'] = 'No valid item ID provided';
    echo json_encode($response);
    exit;
}

// Check if we have date and time
if (!isset($_POST['date']) || !isset($_POST['time'])) {
    $response['message'] = 'Date and time are required';
    echo json_encode($response);
    exit;
}

$item_date = $_POST['date'];
$time_slot = $_POST['time'];

// Validate item ID
if ($item_id <= 0) {
    $response['message'] = "Invalid $item_type ID";
} else {
    // Check if the item exists
    $sql = "SELECT * FROM $table WHERE $id_column = ? $status_check";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = ucfirst($item_type) . ' not found';
    } else {
        // Check if this item is already in the user's itinerary for this date
        $sql = "SELECT * FROM UserItinerary WHERE user_id = ? AND $id_column = ? AND item_date = ?";
        
        // Bind parameters based on user type
        if (!isset($_SESSION['user_id'])) {
            // Guest user (string ID)
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $user_id, $item_id, $item_date);
        } else {
            // Regular user (integer ID)
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $user_id, $item_id, $item_date);
        }
        
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update the existing itinerary item
            $sql = "UPDATE UserItinerary SET time_slot = ? WHERE user_id = ? AND $id_column = ? AND item_date = ?";
            
            if (!isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $time_slot, $user_id, $item_id, $item_date);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siis", $time_slot, $user_id, $item_id, $item_date);
            }
        } else {
            // Add the item to the itinerary
            // Set up all column values, with the appropriate one being the item_id and the rest NULL
            $attraction_id_param = ($item_type === 'attraction') ? $item_id : NULL;
            $flight_id_param = ($item_type === 'flight') ? $item_id : NULL;
            $room_id_param = ($item_type === 'room') ? $item_id : NULL;
            
            $sql = "INSERT INTO UserItinerary (user_id, attraction_id, flight_id, room_id, item_date, time_slot, is_guest) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            if (!isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siisssi", $user_id, $attraction_id_param, $flight_id_param, $room_id_param, $item_date, $time_slot, $is_guest);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiissi", $user_id, $attraction_id_param, $flight_id_param, $room_id_param, $item_date, $time_slot, $is_guest);
            }
        }
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = ucfirst($item_type) . ' scheduled successfully';
        } else {
            $response['message'] = 'Database error: ' . $stmt->error;
        }
    }
}

// Return response as JSON
echo json_encode($response);
?>