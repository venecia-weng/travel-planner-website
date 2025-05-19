<?php
// Prevent any output before intended JSON response
error_reporting(0); // Disable error reporting
ini_set('display_errors', 0); // Don't display errors

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not logged in', 'redirect' => 'login.php']);
        exit;
    }
    
    // Regular redirect for non-AJAX requests
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Initialize response
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    // Check for attraction ID
    if (isset($_POST['attraction_id'])) {
        $attraction_id = intval($_POST['attraction_id']);
        
        // Check if this attraction is already favorited by the user
        $check_sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND attraction_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $attraction_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Attraction is already favorited, so remove it
            $row = $check_result->fetch_assoc();
            $favorite_id = $row['favorite_id'];
            
            $delete_sql = "DELETE FROM favorites WHERE favorite_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $favorite_id);
            
            if ($delete_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Attraction removed from favorites',
                    'status' => 'removed',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to remove from favorites'];
            }
            $delete_stmt->close();
        } else {
            // Attraction is not favorited, so add it
            $insert_sql = "INSERT INTO favorites (user_id, attraction_id, destination_id, hotel_id, room_id) VALUES (?, ?, NULL, NULL, NULL)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $user_id, $attraction_id);
            
            if ($insert_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Attraction added to favorites',
                    'status' => 'added',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add to favorites'];
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } 
    // Check for destination ID
    else if (isset($_POST['destination_id'])) {
        $destination_id = intval($_POST['destination_id']);
        
        // Check if this destination is already favorited by the user
        $check_sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND destination_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $destination_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Destination is already favorited, so remove it
            $row = $check_result->fetch_assoc();
            $favorite_id = $row['favorite_id'];
            
            $delete_sql = "DELETE FROM favorites WHERE favorite_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $favorite_id);
            
            if ($delete_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Destination removed from favorites',
                    'status' => 'removed',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to remove from favorites'];
            }
            $delete_stmt->close();
        } else {
            // Destination is not favorited, so add it
            $insert_sql = "INSERT INTO favorites (user_id, destination_id, attraction_id, hotel_id, room_id) VALUES (?, ?, NULL, NULL, NULL)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $user_id, $destination_id);
            
            if ($insert_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Destination added to favorites',
                    'status' => 'added',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add to favorites'];
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    // Check for hotel ID
    else if (isset($_POST['hotel_id'])) {
        $hotel_id = intval($_POST['hotel_id']);
        
        // Check if this hotel is already favorited by the user
        $check_sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND hotel_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $hotel_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Hotel is already favorited, so remove it
            $row = $check_result->fetch_assoc();
            $favorite_id = $row['favorite_id'];
            
            $delete_sql = "DELETE FROM favorites WHERE favorite_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $favorite_id);
            
            if ($delete_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Hotel removed from favorites',
                    'status' => 'removed',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to remove from favorites'];
            }
            $delete_stmt->close();
        } else {
            // Hotel is not favorited, so add it
            $insert_sql = "INSERT INTO favorites (user_id, hotel_id, attraction_id, destination_id, room_id) VALUES (?, ?, NULL, NULL, NULL)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $user_id, $hotel_id);
            
            if ($insert_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Hotel added to favorites',
                    'status' => 'added',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add to favorites: ' . $insert_stmt->error];
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    // Check for room ID
    else if (isset($_POST['room_id'])) {
        $room_id = intval($_POST['room_id']);
        
        // Check if this room is already favorited by the user
        $check_sql = "SELECT favorite_id FROM favorites WHERE user_id = ? AND room_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $room_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Room is already favorited, so remove it
            $row = $check_result->fetch_assoc();
            $favorite_id = $row['favorite_id'];
            
            $delete_sql = "DELETE FROM favorites WHERE favorite_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $favorite_id);
            
            if ($delete_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Room removed from favorites',
                    'status' => 'removed',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to remove from favorites'];
            }
            $delete_stmt->close();
        } else {
            // Room is not favorited, so add it
            $insert_sql = "INSERT INTO favorites (user_id, room_id, attraction_id, destination_id, hotel_id) VALUES (?, ?, NULL, NULL, NULL)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $user_id, $room_id);
            
            if ($insert_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Room added to favorites',
                    'status' => 'added',
                    'count' => getFavoriteCount($conn, $user_id)
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add to favorites'];
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

// Function to get favorite count for a user
function getFavoriteCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

// Close database connection
$conn->close();

// Return JSON response for AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set the content type and output the JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For non-AJAX, redirect with message
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'favorites.php';
header("Location: $redirect");
exit;
?>