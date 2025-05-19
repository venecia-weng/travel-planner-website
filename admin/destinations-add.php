<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include database connection
require_once '../includes/db_connect.php';

// Set page title
$page_title = 'Add Destination - RoundTours';
$active_page = 'destinations';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $location_name = trim($_POST['location_name']);
    $country = trim($_POST['country']);
    $city = trim($_POST['city'] ?? '');
    $description = trim($_POST['description']);
    $climate = trim($_POST['climate'] ?? '');
    $best_time = trim($_POST['best_time'] ?? '');
    $timezone = trim($_POST['timezone'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    $status = $_POST['status'];
    
    // Validate form data
    $errors = [];
    
    if (empty($location_name)) {
        $errors[] = "Location name is required";
    }
    
    if (empty($country)) {
        $errors[] = "Country is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // Upload image if provided
    $main_image_url = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/destinations/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = basename($_FILES['main_image']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = 'destination_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
        $target_file = $upload_dir . $unique_name;
        
        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } else {
            // Move uploaded file
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_file)) {
                $main_image_url = 'assets/images/destinations/' . $unique_name;
            } else {
                $errors[] = "Error uploading image";
            }
        }
    }
    
    // Insert data if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO destinations (location_name, country, city, description, main_image_url, 
                climate, best_time_to_visit, timezone, language, currency, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssssss', $location_name, $country, $city, $description, $main_image_url, 
                          $climate, $best_time, $timezone, $language, $currency, $status);
        
        if ($stmt->execute()) {
            // Redirect to destinations page
            header('Location: destinations-add.php?created=1');
            exit;
        } else {
            $errors[] = "Error creating destination: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Add New Destination</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="destinations-view.php">Destinations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Destination</li>
            </ol>
        </nav>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Basic Information</h4>
                        
                        <div class="mb-3">
                            <label for="location_name" class="form-label">Location Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location_name" name="location_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="country" name="country" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="main_image" class="form-label">Main Image</label>
                            <input type="file" class="form-control" id="main_image" name="main_image" accept="image/*">
                            <small class="form-text text-muted">Recommended size: 1280x720 pixels</small>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Additional Information</h4>
                        
                        <div class="mb-3">
                            <label for="climate" class="form-label">Climate</label>
                            <input type="text" class="form-control" id="climate" name="climate">
                        </div>
                        
                        <div class="mb-3">
                            <label for="best_time" class="form-label">Best Time to Visit</label>
                            <input type="text" class="form-control" id="best_time" name="best_time">
                        </div>
                        
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <input type="text" class="form-control" id="timezone" name="timezone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="language" class="form-label">Language</label>
                            <input type="text" class="form-control" id="language" name="language">
                        </div>
                        
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="currency" name="currency">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-end">
                    <a href="destinations-add.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Destination</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>