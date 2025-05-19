<?php

// Restrict access to admin users only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Sanitize input
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$category = trim($_POST['category'] ?? '');
$user_id = $_SESSION['user_id'];
$created_at = date('Y-m-d H:i:s');

// Validate required fields
if (empty($title) || empty($content)) {
    echo json_encode(['error' => 'Title and content are required.']);
    exit;
}

// Optional main image
$main_image_path = null;
if (!empty($_FILES['main_image']['tmp_name'])) {
    $filename = time() . "_" . basename($_FILES['main_image']['name']);
    $target_path = "assets/uploads/" . $filename;
    move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path);
    $main_image_path = $target_path;
}

// Insert blog post
$stmt = $conn->prepare("INSERT INTO Blogs (title, content, image_path, destination, category, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssi", $title, $content, $main_image_path, $destination, $category, $created_at, $user_id);
$stmt->execute();
$blog_id = $stmt->insert_id;

// Handle multiple additional blog images
if (!empty($_FILES['blog_images']['name'][0])) {
    foreach ($_FILES['blog_images']['tmp_name'] as $index => $tmp_name) {
        if (!empty($tmp_name)) {
            $filename = time() . "_" . basename($_FILES['blog_images']['name'][$index]);
            $path = "assets/uploads/" . $filename;
            move_uploaded_file($tmp_name, $path);

            $order = $index + 1;
            $imgStmt = $conn->prepare("INSERT INTO blog_images (blog_id, image_path, image_order) VALUES (?, ?, ?)");
            $imgStmt->bind_param("isi", $blog_id, $path, $order);
            $imgStmt->execute();
        }
    }
}

echo json_encode(['success' => true, 'blog_id' => $blog_id]);
$conn->close();
?>
