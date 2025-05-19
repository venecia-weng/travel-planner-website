<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please log in to like a comment.";
        header("Location: login.php");
        exit();
    }

    $comment_id = intval($_POST['comment_id'] ?? 0);
    $blog_id = intval($_POST['blog_id'] ?? 0);

    if ($comment_id <= 0 || $blog_id <= 0) {
        $_SESSION['error'] = "Invalid like request.";
        header("Location: blog_detail.php?id=$blog_id#comments");
        exit();
    }

    // Check if user has already liked this comment
    $check_sql = "SELECT like_id FROM Likes WHERE comment_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // User already liked, so optionally prevent duplicate likes or toggle
        $_SESSION['error'] = "You've already liked this comment.";
    } else {
        // Insert the like
        $insert_sql = "INSERT INTO Likes (blog_id, comment_id, user_id, created_at)
                       VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $blog_id, $comment_id, $_SESSION['user_id']);

        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "You liked the comment!";
        } else {
            $_SESSION['error'] = "Failed to like comment.";
        }
    }

    header("Location: blog_detail.php?id=$blog_id#comment-$comment_id");
    exit();
}
?>