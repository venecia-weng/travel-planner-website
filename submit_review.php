<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'includes/db_connect.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $attraction_id = isset($_POST['attraction_id']) ? intval($_POST['attraction_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $visit_date = isset($_POST['visit_date']) ? $_POST['visit_date'] . '-01' : null;
    $user_id = $_SESSION['user_id'];
    
    // Validate data
    if ($attraction_id <= 0 || $rating <= 0 || $rating > 5 || empty($comment)) {
        $_SESSION['error_message'] = "Please provide a valid rating and review.";
        header("Location: attraction-detail.php?id=" . $attraction_id);
        exit();
    }
    
    // Check if the reviews table structure allows for attraction reviews
    // First, get the attraction's associated destination
    $sql = "SELECT destination_id FROM Attractions WHERE attraction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attraction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Invalid attraction.";
        header("Location: attractions.php");
        exit();
    }
    
    $destination_id = $result->fetch_assoc()['destination_id'];
    
    // Check if the Reviews table has an attraction_id column
    $attraction_column_exists = false;
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'attraction_id'");
    
    if ($result->num_rows === 0) {
        // Add attraction_id column to Reviews table
        try {
            $conn->query("ALTER TABLE Reviews ADD COLUMN attraction_id INT DEFAULT NULL");
            $attraction_column_exists = true;
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating database structure: " . $e->getMessage();
            header("Location: attraction-detail.php?id=" . $attraction_id);
            exit();
        }
    } else {
        $attraction_column_exists = true;
    }
    
    // Check if review_type column exists
    $review_type_column_exists = false;
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'review_type'");
    
    if ($result->num_rows === 0) {
        // Add review_type column to Reviews table
        try {
            $conn->query("ALTER TABLE Reviews ADD COLUMN review_type VARCHAR(20) DEFAULT 'destination'");
            $review_type_column_exists = true;
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating database structure: " . $e->getMessage();
            header("Location: attraction-detail.php?id=" . $attraction_id);
            exit();
        }
    } else {
        $review_type_column_exists = true;
    }
    
    // Insert the review using the existing Reviews table
    try {
        $review_type = 'attraction';
        
        // For attraction reviews, we'll store the destination_id and the attraction_id
        $sql = "INSERT INTO Reviews (user_id, destination_id, attraction_id, rating, title, comment, visit_date, review_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidssss", $user_id, $destination_id, $attraction_id, $rating, $title, $comment, $visit_date, $review_type);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Your review has been submitted successfully!";
        } else {
            $_SESSION['error_message'] = "There was a problem submitting your review. Please try again.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
    
    // Redirect back to the attraction page
    header("Location: attraction-detail.php?id=" . $attraction_id);
    exit();
} else {
    // If not a POST request, redirect to home
    header("Location: index.php");
    exit();
}
?>