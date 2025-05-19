<?php
session_start();
require_once '../includes/db_connect.php';

// Only allow admin access
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin-login.php");
    exit();
}

// Get blog ID
$blog_id = $_GET['id'] ?? null;
if (!$blog_id) {
    echo "<p class='text-danger text-center'>Blog ID is missing.</p>";
    exit();
}

// Fetch blog
$stmt = $conn->prepare("SELECT * FROM blogs WHERE blog_id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

// Fetch additional images
$additional_images = [];
$img_stmt = $conn->prepare("SELECT image_id, image_path FROM blog_images WHERE blog_id = ? ORDER BY image_order ASC");
$img_stmt->bind_param("i", $blog_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
while ($img = $img_result->fetch_assoc()) {
    $additional_images[] = $img;
}

if (!$blog) {
    echo "<p class='text-danger text-center'>Blog post not found.</p>";
    exit();
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (empty($title) || empty($content)) {
        echo "<p class='text-danger text-center'>Title and content are required.</p>";
    } else {
        // Create upload directory if it doesn't exist
        $upload_dir = "../assets/uploads/";
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                echo "<p class='text-danger text-center'>Failed to create upload directory. Check permissions.</p>";
                error_log("Failed to create directory: " . $upload_dir);
            }
        }

        $main_image_path = $blog['image_path'];
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $filename = time() . "_" . basename($_FILES['main_image']['name']);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path)) {
                $main_image_path = "assets/uploads/" . $filename;
                error_log("Successfully uploaded main image to: " . $target_path);
            } else {
                echo "<p class='text-danger text-center'>Failed to upload main image. Error code: " . $_FILES['main_image']['error'] . "</p>";
                error_log("Failed to upload main image. Error code: " . $_FILES['main_image']['error']);
                error_log("Target path: " . $target_path);
            }
        }

        $stmt = $conn->prepare("UPDATE blogs SET title = ?, content = ?, destination = ?, category = ?, image_path = ?, last_updated = NOW() WHERE blog_id = ?");
        $stmt->bind_param("sssssi", $title, $content, $destination, $category, $main_image_path, $blog_id);
        $stmt->execute();

        if (!empty($_FILES['additional_images']['name'][0])) {
            foreach ($_FILES['additional_images']['tmp_name'] as $index => $tmp_name) {
                if (!empty($tmp_name)) {
                    $filename = time() . "_" . $index . "_" . basename($_FILES['additional_images']['name'][$index]);
                    $path = "assets/uploads/" . $filename;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $order = $index + 1;
                        $imgStmt = $conn->prepare("INSERT INTO blog_images (blog_id, image_path, image_order) VALUES (?, ?, ?)");
                        $imgStmt->bind_param("isi", $blog_id, $path, $order);
                        $imgStmt->execute();
                        error_log("Successfully uploaded additional image to: " . $target_path);
                    } else {
                        echo "<p class='text-warning'>Failed to upload additional image #" . ($index + 1) . ". Error code: " . $_FILES['additional_images']['error'][$index] . "</p>";
                        error_log("Failed to upload additional image. Error code: " . $_FILES['additional_images']['error'][$index]);
                        error_log("Target path: " . $target_path);
                    }
                }
            }
        }

        echo "<p class='text-success text-center'>Blog updated successfully!</p>";
    }
}

$page_title = 'Edit Blog Post';
include 'admin-header.php';
?>

<style>
.blog-form-container {
    max-width: 700px;
    margin: 30px auto;
    padding: 25px;
    background: #f7f7f7;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.custom-upload {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    color: white;
    padding: 10px 20px;
    font-weight: 600;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    display: inline-block;
    margin-top: 8px;
    margin-bottom: 10px;
}
.custom-upload:hover {
    background: linear-gradient(135deg, #0b5ed7, #0aa2c0);
}
.custom-upload input[type="file"] {
    display: none;
}
.image-preview {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.image-preview img {
    max-width: 120px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
</style>

<div class="blog-form-container">
    <h2 class="text-center mb-4">Edit Blog Post</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($blog['title']) ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Content</label>
            <textarea name="content" rows="6" class="form-control" required><?= htmlspecialchars($blog['content']) ?></textarea>
        </div>

        <div class="mb-3">
            <label>Destination</label>
            <input type="text" name="destination" value="<?= htmlspecialchars($blog['destination']) ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="category" class="form-select">
                <option value="">Select category</option>
                <option value="Food" <?= $blog['category'] === 'Food' ? 'selected' : '' ?>>Food</option>
                <option value="Attractions" <?= $blog['category'] === 'Attractions' ? 'selected' : '' ?>>Attractions</option>
                <option value="Travel itinerary" <?= $blog['category'] === 'Travel itinerary' ? 'selected' : '' ?>>Travel Itinerary</option>
                <option value="Beaches" <?= $blog['category'] === 'Beaches' ? 'selected' : '' ?>>Beaches</option>
                <option value="Adventure" <?= $blog['category'] === 'Adventure' ? 'selected' : '' ?>>Adventure</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Main Image (leave blank to keep current)</label><br>
            <label class="custom-upload">
                📷 Choose Image
                <input type="file" name="main_image" id="main_image_input" accept="image/*">
            </label>
            <div id="main-image-preview" class="image-preview">
                <?php if (!empty($blog['image_path'])): ?>
                    <div>
                        <p>Current image:</p>
                        <img src="../<?= htmlspecialchars($blog['image_path']) ?>" alt="Main Image">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <label>Upload Additional Images</label><br>
            <label class="custom-upload">
                📷 Choose Images
                <input type="file" name="additional_images[]" id="additional_images_input" multiple accept="image/*">
            </label>
            <div id="additional-image-preview" class="image-preview"></div>
        </div>

        <?php if (!empty($additional_images)): ?>
        <div class="mb-3">
            <label>Current Additional Images</label>
            <div class="d-flex flex-wrap gap-3 mt-2">
                <?php foreach ($additional_images as $img): ?>
                    <div style="position: relative;">
                        <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="img-thumbnail" style="max-width: 120px;">
                        <a href="blog-image-delete.php?id=<?= $img['image_id'] ?>&blog_id=<?= $blog_id ?>"
                           class="btn btn-sm btn-danger position-absolute top-0 end-0"
                           style="transform: translate(50%, -50%); border-radius: 50%;"
                           onclick="return confirm('Delete this image?')">×</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include 'admin-footer.php'; ?>

<script>
function previewImages(input, containerId) {
    const preview = document.getElementById(containerId);
    if (containerId === 'main-image-preview') {
        // Clear only the newly added previews for main image
        const existingImages = preview.querySelectorAll('div');
        if (existingImages.length > 0) {
            const currentImage = existingImages[0];
            preview.innerHTML = '';
            preview.appendChild(currentImage);
        }
    } else {
        preview.innerHTML = '';
    }
    
    Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            const img = document.createElement('img');
            img.src = e.target.result;
            if (containerId === 'main-image-preview') {
                const p = document.createElement('p');
                p.textContent = 'New image:';
                div.appendChild(p);
            }
            div.appendChild(img);
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
document.getElementById('main_image_input').addEventListener('change', function () {
    previewImages(this, 'main-image-preview');
});
document.getElementById('additional_images_input').addEventListener('change', function () {
    previewImages(this, 'additional-image-preview');
});
</script>