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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: rooms-view.php');
    exit;
}

$room_id = intval($_GET['id']);

$query = "Select * from rooms where room_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: rooms-view.php');
    exit;
}

$room = $result->fetch_assoc();
$stmt->close();

// Get hotels for dropdown
$hotels_query = "SELECT * FROM hotels";
$hotels_result = $conn->query($hotels_query);
$hotels = [];
if ($hotels_result) {
    while ($row = $hotels_result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

// Set page title
$page_title = 'Edit Rooms - RoundTours';
$active_page = 'rooms';

$errors = [];
$room_data = [
    'hotel_id' => $room['hotel_id'],
    'room_type' => $room['room_type'],
    'amenities' => $room['amenities'],
    'description' => $room['description'],
    'num_adults' => $room['num_adults'],
    'num_children' => $room['num_children'],
    'price' => $room['price'],
    'discount' => $room['discount'] == null ? 0 : $room['discount'],
    'main_img_url' => $room['main_img_url']
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_data = [
        'hotel_id' => !empty($_POST['hotel_id']) ? intval($_POST['hotel_id']) : null,
        'room_type' => trim($_POST['room_type'] ?? ''),
        'amenities' => trim($_POST['amenities'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'num_adults' => trim($_POST['num_adults'] ?? ''),
        'num_children' => trim($_POST['num_children'] ?? ''),

        // Price and Discount - convert to float
        'price' => !empty($_POST['price']) ? floatval($_POST['price']) : null,
        'discount' => !empty($_POST['discount']) ? floatval($_POST['discount']) : null
    ];

    // Validate form data
    $errors = [];

    // Validate basic room information
    if (empty($room_data['hotel_id'])) {
        $errors[] = "Hotel ID is required";
    }

    if (empty($room_data['room_type'])) {
        $errors[] = "Room Type is required";
    }

    if (empty($room_data['amenities'])) {
        $errors[] = "Amenities is required";
    }

    if (empty($room_data['description'])) {
        $errors[] = "Description is required";
    }
    if (empty($room_data['num_adults'])) {
        $errors[] = "Number of adults is required";
    }
    if (empty($room_data['num_children'])) {
        $errors[] = "Number of children is required";
    }

    // Validate numbers
    if ($room_data['price'] === null || $room_data['price'] === '') {
        $errors[] = "Price is required";
    } elseif (!is_numeric($room_data['price']) || $room_data['price'] <= 0) {
        $errors[] = "Price must be a positive number";
    }

    if (!is_numeric($room_data['num_adults']) || $room_data['num_adults'] <= 0) {
        $errors[] = "Number of adults must be a positive number";
    }
    if (!is_numeric($room_data['num_children']) || $room_data['num_children'] <= 0) {
        $errors[] = "Number of children must be a positive number";
    }
    if (!empty($room_data['discount']) && (!is_numeric($room_data['discount']) || $room_data['discount'] < 0)) {
        $errors[] = "Discount percentage must be a positive number";
    }

    // Handle image upload
    $image_url = $room['main_img_url'];
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
        $sql = "UPDATE rooms SET hotel_id = ?, room_type = ?, amenities = ?, description = ?, price = ?, discount = ?, main_img_url = ?, 
            num_adults = ?, num_children = ? WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssddsiii",
            $room_data['hotel_id'],
            $room_data['room_type'],
            $room_data['amenities'],
            $room_data['description'],
            $room_data['price'],
            $room_data['discount'],
            $image_url,
            $room_data['num_adults'],
            $room_data['num_children'],
            $room_id
        );

        if ($stmt->execute()) {
            // Redirect to rooms page
            header('Location: rooms-view.php?updated=1');
            exit;
        } else {
            $errors[] = "Error editing room: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Edit New Rooms</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="rooms-view.php">Rooms</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Room</li>
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
                            <label for="hotel_id" class="form-label">Hotel <span class="text-danger">*</span></label>
                            <select
                                class="form-control"
                                id="hotel_id"
                                name="hotel_id"
                                required>
                                <option value="">Select Hotel</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option
                                        value="<?php echo $hotel['hotel_id']; ?>"
                                        <?php echo ($room_data['hotel_id'] == $hotel['hotel_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="room_type" class="form-label">Room Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="room_type" name="room_type" required
                                value="<?php echo htmlspecialchars($room_data['room_type']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="amenities" class="form-label">Amenities <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="amenities" name="amenities" required
                                value="<?php echo htmlspecialchars($room_data['amenities']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="num_adults" class="form-label">Number of adults <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="num_adults" name="num_adults" required
                                value="<?php echo htmlspecialchars($room_data['num_adults']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="num_children" class="form-label">Number of children <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="num_children" name="num_children" required
                                value="<?php echo htmlspecialchars($room_data['num_children']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Attraction Image</label>
                            <input type="file"
                                class="form-control"
                                id="image"
                                name="image"
                                accept="image/*">
                            <small class="form-text text-muted">Recommended size: 1280x720 pixels</small>

                            <?php if (!empty($room['main_img_url'])): ?>
                                <div class="mt-2">
                                    <p class="text-muted mb-1">Current Image:</p>
                                    <img src="../<?php echo htmlspecialchars($room['main_img_url']); ?>" alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Prices and Discounts</h4>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="price" name="price" required
                                value="<?php echo htmlspecialchars($room_data['price']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="discount" class="form-label">Discount Percentage (Optional)</label>
                            <input type="text" class="form-control" id="discount" name="discount"
                                value="<?php echo htmlspecialchars($room_data['discount']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"
                                rows="5"
                                required><?php echo htmlspecialchars($room_data['description']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="rooms-edit.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Edit Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>