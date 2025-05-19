<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin-login.php");
    exit();
}

$blog_id = $_GET['id'] ?? null;
if (!$blog_id) {
    echo "<p class='text-danger text-center'>Invalid blog ID.</p>";
    exit;
}

// Delete images associated with blog
$imgStmt = $conn->prepare("SELECT image_path FROM blog_images WHERE blog_id = ?");
$imgStmt->bind_param("i", $blog_id);
$imgStmt->execute();
$result = $imgStmt->get_result();

while ($img = $result->fetch_assoc()) {
    if (file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }
}

$conn->query("DELETE FROM blog_images WHERE blog_id = $blog_id");
$conn->query("DELETE FROM Blogs WHERE blog_id = $blog_id");

header("Location: blog-list.php?deleted=true");
exit;