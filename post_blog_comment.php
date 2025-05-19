<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'blogs.php';
        $_SESSION['pending_comment'] = $_POST['comment'] ?? '';
        $_SESSION['pending_blog_id'] = $_POST['blog_id'] ?? 0;
        $_SESSION['login_message'] = "Please log in to post your comment.";
        header("Location: login.php");
        exit();
    }

    $blog_id = isset($_POST['blog_id']) ? intval($_POST['blog_id']) : 0;
    $comment_content = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($blog_id <= 0 || empty($comment_content)) {
        $_SESSION['error'] = "Please enter a comment before submitting.";
        header("Location: blog_detail.php?id=$blog_id#comments");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO Comments (blog_id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $blog_id, $user_id, $comment_content, $created_at);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Your comment has been posted!";
    } else {
        $_SESSION['error'] = "Error posting comment. Please try again.";
    }

    header("Location: blog_detail.php?id=$blog_id#comments");
    exit();
} else {
    header("Location: index.php");
    exit();
}