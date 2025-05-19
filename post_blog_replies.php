<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please log in to reply.";
        header("Location: login.php");
        exit();
    }

    $comment_id = intval($_POST['comment_id'] ?? 0);
    $reply_content = trim($_POST['reply_content'] ?? '');
    $blog_id = intval($_POST['blog_id'] ?? 0);

    if ($comment_id <= 0 || empty($reply_content)) {
        $_SESSION['error'] = "Reply cannot be empty.";
        header("Location: blog_detail.php?id=$blog_id#comments");
        exit();
    }

    $sql = "INSERT INTO replies (comment_id, user_id, content, created_at)
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $comment_id, $_SESSION['user_id'], $reply_content);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Reply posted!";
    } else {
        $_SESSION['error'] = "Failed to post reply.";
    }

    header("Location: blog_detail.php?id=$blog_id#comment-$comment_id");
    exit();
}
?>