<?php
session_start();
require_once '../includes/db_connect.php';
$page_title = 'Create Blog Post';
include 'admin-header.php';

// Handle blog creation for Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $user_id = $_SESSION['admin_id'];
        
        // Query the database to get the author's first and last name
        $authorQuery = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $authorQuery->bind_param("i", $user_id);
        $authorQuery->execute();
        $authorResult = $authorQuery->get_result();
        
        if ($authorResult->num_rows > 0) {
            $userInfo = $authorResult->fetch_assoc();
            $author = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
            
            // If both names are empty, use username as fallback
            if (trim($author) === '') {
                $usernameQuery = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
                $usernameQuery->bind_param("i", $user_id);
                $usernameQuery->execute();
                $usernameResult = $usernameQuery->get_result();
                if ($usernameResult->num_rows > 0) {
                    $usernameInfo = $usernameResult->fetch_assoc();
                    $author = $usernameInfo['username'];
                } else {
                    $author = 'Admin';
                }
            }
        } else {
            $author = 'Admin';
        }
        
        $created_at = date('Y-m-d H:i:s');
        $created_date = date('Y-m-d');

        if (empty($title) || empty($content)) {
            echo "<p class='text-danger text-center'>Title and content are required.</p>";
        } else {
            // Define upload directory with correct path
            $upload_dir = "../assets/uploads/";
            
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    echo "<p class='text-danger text-center'>Failed to create upload directory. Check permissions.</p>";
                    // Log error for debugging
                    error_log("Failed to create directory: " . $upload_dir);
                } else {
                    echo "<p class='text-success text-center'>Successfully created upload directory.</p>";
                }
            }
            
            // Main image upload
            $main_image_path = null;
            if (!empty($_FILES['main_image']['tmp_name'])) {
                $filename = time() . "_" . basename($_FILES['main_image']['name']);
                $target_path = "assets/uploads/" . $filename; // Database path
                $upload_path = $upload_dir . $filename; // Server path
                
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                    $main_image_path = $target_path;
                    // Log success for debugging
                    error_log("Successfully uploaded main image to: " . $upload_path);
                    echo "<p class='text-success text-center'>Main image uploaded successfully.</p>";
                } else {
                    echo "<p class='text-danger text-center'>Failed to upload main image. Error code: " . $_FILES['main_image']['error'] . "</p>";
                    // Log error for debugging
                    error_log("Failed to upload main image. Error code: " . $_FILES['main_image']['error']);
                }
            }

            // Insert blog post into database
            $stmt = $conn->prepare("INSERT INTO blogs (title, content, image_path, destination, category, created_at, created_date, user_id, author) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssis", $title, $content, $main_image_path, $destination, $category, $created_at, $created_date, $user_id, $author);
            
            if ($stmt->execute()) {
                $blog_id = $stmt->insert_id;
                
                // Additional images upload
                if (!empty($_FILES['blog_images']['name'][0])) {
                    foreach ($_FILES['blog_images']['tmp_name'] as $index => $tmp_name) {
                        if (!empty($tmp_name)) {
                            $filename = time() . "_" . $index . "_" . basename($_FILES['blog_images']['name'][$index]);
                            $db_path = "assets/uploads/" . $filename; // Database path
                            $server_path = $upload_dir . $filename; // Server path
                            
                            if (move_uploaded_file($tmp_name, $server_path)) {
                                $order = $index + 1;
                                $imgStmt = $conn->prepare("INSERT INTO blog_images (blog_id, image_path, image_order) VALUES (?, ?, ?)");
                                $imgStmt->bind_param("isi", $blog_id, $db_path, $order);
                                $imgStmt->execute();
                                // Log success
                                error_log("Successfully uploaded additional image to: " . $server_path);
                            } else {
                                echo "<p class='text-warning'>Failed to upload additional image #" . ($index + 1) . ".</p>";
                                // Log error
                                error_log("Failed to upload additional image. Error code: " . $_FILES['blog_images']['error'][$index]);
                            }
                        }
                    }
                }
                
                echo "<p class='text-success text-center'>Blog posted successfully!</p>";
            } else {
                echo "<p class='text-danger text-center'>Failed to post blog: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p class='text-danger text-center'>Unauthorized access.</p>";
    }
}

// Get author info for display
$current_author = '';
if (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $authorQuery = $conn->prepare("SELECT first_name, last_name, username FROM users WHERE user_id = ?");
    $authorQuery->bind_param("i", $user_id);
    $authorQuery->execute();
    $authorResult = $authorQuery->get_result();
    
    if ($authorResult->num_rows > 0) {
        $userInfo = $authorResult->fetch_assoc();
        $first_name = $userInfo['first_name'];
        $last_name = $userInfo['last_name'];
        
        if (!empty(trim($first_name)) && !empty(trim($last_name))) {
            $current_author = $first_name . ' ' . $last_name;
        } else {
            $current_author = $userInfo['username'] ?? 'Admin';
        }
    }
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

.image-preview .img-container {
    position: relative;
    display: inline-block;
}

.image-preview img {
    max-width: 120px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    border: none;
    border-radius: 50%;
    background-color: #dc3545;
    font-size: 14px;
    cursor: pointer;
    width: 22px;
    height: 22px;
    line-height: 18px;
    text-align: center;
}
</style>

<div class="blog-form-container">
    <h2 class="text-center mb-4">Create a New Blog Post</h2>
    
    <!-- Display Current Author Name -->
    <?php if (!empty($current_author)): ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-user-edit me-2"></i> Creating post as: <strong><?= htmlspecialchars($current_author) ?></strong>
    </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Title <span class="text-danger">*</span></label>
            <input type="text" name="title" required class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Content <span class="text-danger">*</span></label>
            <textarea name="content" rows="6" required class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label>Destination <span class="text-danger">*</span></label>
            <input type="text" name="destination" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                <option value="">Select category</option>
                <option value="Food">Food</option>
                <option value="Attractions">Attractions</option>
                <option value="Travel itinerary">Travel Itinerary</option>
                <option value="Beaches">Beaches</option>
                <option value="Adventure">Adventure</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Main Image <span class="text-danger">*</span></label><br>
            <label class="custom-upload" required>
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

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Post Blog
            </button>
        </div>
    </form>
</div>

<?php include 'admin-footer.php'; ?>

<script>
function previewImages(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    previewContainer.innerHTML = '';

    Array.from(input.files).forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            const imgWrapper = document.createElement('div');
            imgWrapper.classList.add('img-container');

            const img = document.createElement('img');
            img.src = event.target.result;

            const removeBtn = document.createElement('button');
            removeBtn.classList.add('remove-btn');
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = () => {
                input.value = '';
                imgWrapper.remove();
            };

            imgWrapper.appendChild(img);
            imgWrapper.appendChild(removeBtn);
            previewContainer.appendChild(imgWrapper);
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
</script>