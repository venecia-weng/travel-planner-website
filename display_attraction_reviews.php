<?php
/**
 * This file provides a function to display attraction reviews.
 * Include this file in your attraction-detail.php to show reviews.
 */

/**
 * Get attraction reviews from the database
 * 
 * @param mysqli $conn Database connection
 * @param int $attraction_id The ID of the attraction
 * @param int $limit Maximum number of reviews to return (default 5)
 * @return array Array of reviews
 */
function getAttractionReviews($conn, $attraction_id, $limit = 5) {
    $reviews = array();
    
    // Check if attraction_id and review_type columns exist in Reviews table
    $columns_exist = true;
    
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'attraction_id'");
    if ($result->num_rows === 0) {
        $columns_exist = false;
    }
    
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'review_type'");
    if ($result->num_rows === 0) {
        $columns_exist = false;
    }
    
    if (!$columns_exist) {
        return $reviews; // Return empty array if columns don't exist
    }
    
    try {
        $sql = "SELECT r.*, u.username, u.profile_image, u.first_name, u.last_name 
                FROM Reviews r 
                LEFT JOIN Users u ON r.user_id = u.user_id 
                WHERE r.attraction_id = ? AND r.review_type = 'attraction'
                ORDER BY r.created_at DESC 
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $attraction_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
        }
    } catch (Exception $e) {
        // Log error if needed
        error_log("Error fetching attraction reviews: " . $e->getMessage());
    }
    
    return $reviews;
}

/**
 * Display attraction reviews in HTML format
 * 
 * @param array $reviews Array of review data
 * @return string HTML output of reviews
 */
function displayAttractionReviews($reviews) {
    if (empty($reviews)) {
        return '<div class="alert alert-info">No reviews available for this attraction yet.</div>';
    }
    
    $html = '<div class="reviews-container">';
    
    foreach ($reviews as $review) {
        $reviewer_name = !empty($review['username']) ? $review['username'] : 'Anonymous';
        if (!empty($review['first_name'])) {
            $reviewer_name = $review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.';
        }
        
        $profile_image = !empty($review['profile_image']) ? $review['profile_image'] : 'assets/images/profiles/default.jpg';
        
        $visit_date_text = '';
        if (!empty($review['visit_date'])) {
            $visit_date = new DateTime($review['visit_date']);
            $visit_date_text = 'Visited: ' . $visit_date->format('M Y');
        }
        
        $rating = floatval($review['rating']);
        $stars_html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars_html .= '<i class="fas fa-star"></i>';
            } elseif ($i - 0.5 <= $rating) {
                $stars_html .= '<i class="fas fa-star-half-alt"></i>';
            } else {
                $stars_html .= '<i class="far fa-star"></i>';
            }
        }
        
        $review_title = !empty($review['title']) ? htmlspecialchars($review['title']) : '';
        $review_comment = !empty($review['comment']) ? htmlspecialchars($review['comment']) : '';
        
        $created_date = new DateTime($review['created_at']);
        $created_date_text = $created_date->format('M d, Y');
        
        $html .= <<<HTML
<div class="review-item mb-4 pb-3 border-bottom">
    <div class="d-flex align-items-center mb-2">
        <img src="{$profile_image}" class="rounded-circle mr-3" style="width: 50px; height: 50px; object-fit: cover;" alt="{$reviewer_name}">
        <div>
            <h5 class="mb-0">{$reviewer_name}</h5>
            <div class="text-warning">
                {$stars_html}
                <span class="ml-2 text-muted small">{$rating}/5</span>
            </div>
        </div>
        <div class="text-muted small ml-auto">
            {$visit_date_text}
        </div>
    </div>
    
    <h6 class="mt-2 font-weight-bold">{$review_title}</h6>
    <p>{$review_comment}</p>
    <div class="text-muted small">Posted: {$created_date_text}</div>
</div>
HTML;
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Count total reviews for an attraction
 * 
 * @param mysqli $conn Database connection
 * @param int $attraction_id The ID of the attraction
 * @return array Array with count and average rating
 */
function getAttractionReviewStats($conn, $attraction_id) {
    $stats = [
        'count' => 0,
        'average' => 0
    ];
    
    // Check if attraction_id and review_type columns exist in Reviews table
    $columns_exist = true;
    
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'attraction_id'");
    if ($result->num_rows === 0) {
        $columns_exist = false;
    }
    
    $result = $conn->query("SHOW COLUMNS FROM Reviews LIKE 'review_type'");
    if ($result->num_rows === 0) {
        $columns_exist = false;
    }
    
    if (!$columns_exist) {
        return $stats; // Return empty stats if columns don't exist
    }
    
    try {
        $sql = "SELECT COUNT(*) as count, AVG(rating) as average 
                FROM Reviews
                WHERE attraction_id = ? AND review_type = 'attraction'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $attraction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stats['count'] = $row['count'];
            $stats['average'] = round(floatval($row['average']), 1);
        }
    } catch (Exception $e) {
        // Log error if needed
        error_log("Error fetching attraction review stats: " . $e->getMessage());
    }
    
    return $stats;
}
?>