<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin-login.php');
    exit();
}

$image_id = $_GET['id'] ?? null;
$blog_id = $_GET['blog_id'] ?? null;

if (!$image_id || !$blog_id) {
    exit('Missing parameters.');
}

$stmt = $conn->prepare("SELECT image_path FROM blog_images WHERE image_id = ?");
$stmt->bind_param("i", $image_id);
$stmt->execute();
$result = $stmt->get_result();
$image = $result->fetch_assoc();

if ($image && file_exists('../' . $image['image_path'])) {
    unlink('../' . $image['image_path']);
}

$conn->query("DELETE FROM blog_images WHERE image_id = $image_id");

header("Location: blog-edit.php?id=$blog_id");
exit;