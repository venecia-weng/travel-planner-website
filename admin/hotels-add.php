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
$page_title = 'Add Hotels - RoundTours';
$active_page = 'hotels';

$errors = [];
$hotel_data = [
    'hotel_name' => '',
    'country' => '',
    'city' => '',
    'description' => '',
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_data = [
        'hotel_name' => trim($_POST['hotel_name'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];

    // Validate form data
    $errors = [];

    // Validate basic hotel information
    if (empty($hotel_data['hotel_name'])) {
        $errors[] = "Hotel Name is required";
    }

    if (empty($hotel_data['country'])) {
        $errors[] = "Country is required";
    }

    if (empty($hotel_data['city'])) {
        $errors[] = "City is required";
    }

    if (empty($hotel_data['description'])) {
        $errors[] = "Description is required";
    }
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/attractions/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = basename($_FILES['image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } else {
            $unique_name = 'attraction_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $unique_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = 'assets/images/attractions/' . $unique_name;
            } else {
                $errors[] = "Error uploading image";
            }
        }
    }

    // Insert data if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO hotels (hotel_name, country, city, main_img_url, description, currency, status) VALUES (
            ?, ?, ?, ?, ?, 'SGD' , 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $hotel_data['hotel_name'],
            $hotel_data['country'],
            $hotel_data['city'],
            $image_url,
            $hotel_data['description'],
        );

        if ($stmt->execute()) {
            // Redirect to hotels page
            header('Location: hotels-view.php?created=1');
            exit;
        } else {
            $errors[] = "Error creating hotel: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Add New Hotels</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="hotels-view.php">Hotels</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Hotel</li>
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
                            <label for="hotel_name" class="form-label">Hotel Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotel_name" name="hotel_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="country" name="country" required>
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>

                    </div>

                    <!-- Additional Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Prices and Seats</h4>

                        <div class="mb-3">
                            <label for="image" class="form-label">Attraction Image</label>
                            <input type="file"
                                class="form-control"
                                id="image"
                                name="image"
                                accept="image/*">
                            <small class="form-text text-muted">Recommended size: 1280x720 pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"
                                rows="5"
                                required><?php echo htmlspecialchars($hotel_data['description']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="hotels-add.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Hotel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>