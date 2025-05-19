<?php
session_start();
require_once 'includes/db_connect.php';
$page_title = 'Create Blog Post';
include 'header.php';

// Handle blog creation for Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'Admin')
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $user_id = $_SESSION['user_id'];
    $author = $_SESSION['username'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    $created_date = date('Y-m-d');

    if (empty($title) || empty($content)) {
        echo "<p class='text-danger text-center'>Title and content are required.</p>";
    } else {
        if (!file_exists('assets/uploads')) {
            mkdir('assets/uploads', 0777, true);
        }

        $main_image_path = null;
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $filename = time() . "_" . basename($_FILES['main_image']['name']);
            $target_path = "assets/uploads/" . $filename;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path);
            $main_image_path = $target_path;
        }

        $stmt = $conn->prepare("INSERT INTO Blogs (title, content, image_path, destination, category, created_at, created_date, user_id, author) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssis", $title, $content, $main_image_path, $destination, $category, $created_at, $created_date, $user_id, $author);
        $stmt->execute();
        $blog_id = $stmt->insert_id;

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

        echo "<p class='text-success text-center'>Blog posted successfully!</p>";
    }
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
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    margin-top: 8px;
    margin-bottom: 10px;
    transition: background 0.3s ease, box-shadow 0.3s ease;
}

.custom-upload:hover {
    background: linear-gradient(135deg, #0b5ed7, #0aa2c0);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
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
    <h2 class="text-center mb-4">Create a New Blog Post</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Title <span class="text-danger">*</span></label>
            <input type="text" name="title" required class="form-control">
        </div>

        <div class="mb-3">
            <label>Content <span class="text-danger">*</span></label>
            <textarea name="content" rows="6" required class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Destination</label>
            <input type="text" name="destination" class="form-control">
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="category" class="form-select">
                <option value="">Select category</option>
                <option value="Food">Food</option>
                <option value="Attractions">Attractions</option>
                <option value="Travel itinerary">Travel Itinerary</option>
                <option value="Beaches">Beaches</option>
                <option value="Adventure">Adventure</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Main Image</label><br>
            <label class="custom-upload">
                📷 Choose Image
                <input type="file" name="main_image" id="main_image_input" accept="image/*">
            </label>
            <div id="main-image-preview" class="image-preview"></div>
        </div>

        <div class="mb-3">
            <label>Additional Images</label><br>
            <label class="custom-upload">
                📷 Choose Images
                <input type="file" name="blog_images[]" id="blog_images_input" multiple accept="image/*">
            </label>
            <div id="blog-image-preview" class="image-preview"></div>
        </div>

        <button type="button" onclick="clearPreviews()" class="btn btn-danger mb-3">Clear All</button>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Post Blog
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

<script>
function previewImages(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    previewContainer.innerHTML = '';

    const files = input.files;
    [...files].forEach(file => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            const img = document.createElement('img');
            img.src = event.target.result;
            previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

document.getElementById('main_image_input').addEventListener('change', function () {
    previewImages(this, 'main-image-preview');
});

document.getElementById('blog_images_input').addEventListener('change', function () {
    previewImages(this, 'blog-image-preview');
});

function clearPreviews() {
    document.getElementById('main_image_input').value = '';
    document.getElementById('blog_images_input').value = '';
    document.getElementById('main-image-preview').innerHTML = '';
    document.getElementById('blog-image-preview').innerHTML = '';
}
</script>