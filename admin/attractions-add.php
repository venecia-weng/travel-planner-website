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
$page_title = 'Add Attraction - RoundTours';
$active_page = 'attractions';

// Get destinations for dropdown
$destinations_query = "SELECT destination_id, location_name FROM destinations ORDER BY location_name";
$destinations_result = $conn->query($destinations_query);
$destinations = [];
if ($destinations_result) {
    while ($row = $destinations_result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Get existing attraction categories
$categories_query = "SELECT DISTINCT category FROM attractions WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categories_result = $conn->query($categories_query);
$existing_categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $existing_categories[] = $row['category'];
    }
}

// Initialize form variables
$errors = [];
$attraction_data = [
    'destination_id' => '',
    'name' => '',
    'description' => '',
    'category' => '',
    'address' => '',
    'entrance_fee' => '',
    'opening_hours' => '',
    'estimated_time_minutes' => '',
    'status' => 'active',
    'physical_requirements' => '',
    'public_transportation_info' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $attraction_data = [
        'destination_id' => intval($_POST['destination_id']),
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description']),
        'category' => !empty($_POST['custom_category']) ? trim($_POST['custom_category']) : trim($_POST['category']),
        'address' => trim($_POST['address'] ?? ''),
        'entrance_fee' => !empty($_POST['entrance_fee']) ? floatval($_POST['entrance_fee']) : null,
        'opening_hours' => trim($_POST['opening_hours'] ?? ''),
        'estimated_time_minutes' => !empty($_POST['estimated_time_minutes']) ? intval($_POST['estimated_time_minutes']) : null,
        'status' => $_POST['status'],
        'physical_requirements' => trim($_POST['physical_requirements'] ?? ''),
        'public_transportation_info' => trim($_POST['public_transportation_info'] ?? '')
    ];
    
    // Validate required fields
    if (empty($attraction_data['name'])) {
        $errors[] = "Attraction name is required";
    }
    
    if (empty($attraction_data['destination_id'])) {
        $errors[] = "Please select a destination";
    }
    
    if (empty($attraction_data['description'])) {
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

    // Optional JSON fields
    $itinerary = !empty($_POST['itinerary']) ? trim($_POST['itinerary']) : null;
    $additional_info = !empty($_POST['additional_info']) ? trim($_POST['additional_info']) : null;
    $faqs = !empty($_POST['faqs']) ? trim($_POST['faqs']) : null;
    $transportation_details = !empty($_POST['transportation_details']) ? trim($_POST['transportation_details']) : null;

    // If no errors, insert attraction
    if (empty($errors)) {
        $sql = "INSERT INTO attractions (
            destination_id, 
            name, 
            description, 
            image_url, 
            category, 
            address, 
            entrance_fee, 
            opening_hours, 
            estimated_time_minutes, 
            status,
            itinerary,
            additional_info,
            faqs,
            physical_requirements,
            public_transportation_info,
            transportation_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssdsisssssss", 
            $attraction_data['destination_id'], 
            $attraction_data['name'], 
            $attraction_data['description'], 
            $image_url, 
            $attraction_data['category'], 
            $attraction_data['address'], 
            $attraction_data['entrance_fee'], 
            $attraction_data['opening_hours'], 
            $attraction_data['estimated_time_minutes'], 
            $attraction_data['status'],
            $itinerary,
            $additional_info,
            $faqs,
            $attraction_data['physical_requirements'],
            $attraction_data['public_transportation_info'],
            $transportation_details
        );
        
        if ($stmt->execute()) {
            // Redirect to attractions list with success message
            header('Location: attractions-view.php?created=1');
            exit;
        } else {
            $errors[] = "Error creating attraction: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Add New Attraction</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="attractions-view.php">Attractions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Attraction</li>
            </ol>
        </nav>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
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
                            <label for="name" class="form-label">Attraction Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($attraction_data['name']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="destination_id" class="form-label">Destination <span class="text-danger">*</span></label>
                            <select 
                                class="form-control" 
                                id="destination_id" 
                                name="destination_id" 
                                required>
                                <option value="">Select Destination</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option 
                                        value="<?php echo $destination['destination_id']; ?>"
                                        <?php echo ($attraction_data['destination_id'] == $destination['destination_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($destination['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description" 
                                rows="5" 
                                required><?php echo htmlspecialchars($attraction_data['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select Category</option>
                                <?php foreach ($existing_categories as $category): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($category); ?>"
                                        <?php echo ($attraction_data['category'] == $category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other">Other</option>
                            </select>
                            
                            <input type="text" 
                                   class="form-control mt-2 d-none" 
                                   id="custom_category" 
                                   name="custom_category" 
                                   placeholder="Enter custom category">
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Attraction Image</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*">
                            <small class="form-text text-muted">Recommended size: 1280x720 pixels</small>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Additional Details</h4>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="address" 
                                   name="address" 
                                   value="<?php echo htmlspecialchars($attraction_data['address']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="entrance_fee" class="form-label">Entrance Fee</label>
                            <input type="number" 
                                   step="0.01" 
                                   class="form-control" 
                                   id="entrance_fee" 
                                   name="entrance_fee" 
                                   value="<?php echo htmlspecialchars($attraction_data['entrance_fee']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="opening_hours" class="form-label">Opening Hours</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="opening_hours" 
                                   name="opening_hours" 
                                   value="<?php echo htmlspecialchars($attraction_data['opening_hours']); ?>"
                                   placeholder="e.g., 8:00 AM - 5:00 PM">
                        </div>
                        
                        <div class="mb-3">
                            <label for="estimated_time_minutes" class="form-label">Estimated Visit Time (minutes)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="estimated_time_minutes" 
                                   name="estimated_time_minutes" 
                                   value="<?php echo htmlspecialchars($attraction_data['estimated_time_minutes']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($attraction_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($attraction_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="physical_requirements" class="form-label">Physical Requirements</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="physical_requirements" 
                                   name="physical_requirements" 
                                   value="<?php echo htmlspecialchars($attraction_data['physical_requirements']); ?>"
                                   placeholder="e.g., Moderate walking, some steps">
                        </div>
                        
                        <div class="mb-3">
                            <label for="public_transportation_info" class="form-label">Public Transportation Info</label>
                            <textarea 
                                class="form-control" 
                                id="public_transportation_info" 
                                name="public_transportation_info" 
                                rows="3"><?php echo htmlspecialchars($attraction_data['public_transportation_info']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Optional Advanced Information -->
                    <div class="col-12">
                        <h4 class="mb-3">Optional Advanced Information</h4>
                        
                        <div class="mb-3">
                            <label for="itinerary" class="form-label">Itinerary (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="itinerary" 
                                name="itinerary" 
                                rows="4" 
                                placeholder='[{"title":"Example Title","description":"Description","duration":"30 minutes"}]'></textarea>
                            <small class="form-text text-muted">Optional JSON format for tours/activities</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_info" class="form-label">Additional Information (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="additional_info" 
                                name="additional_info" 
                                rows="4" 
                                placeholder='[{"icon":"info-circle","color":"info","content":"Important detail"}]'></textarea>
                            <small class="form-text text-muted">Optional JSON for extra details with icons</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="faqs" class="form-label">Frequently Asked Questions (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="faqs" 
                                name="faqs" 
                                rows="4" 
                                placeholder='[{"question":"What should I know?","answer":"Important information"}]'></textarea>
                            <small class="form-text text-muted">Optional JSON for FAQs</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transportation_details" class="form-label">Transportation Details (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="transportation_details" 
                                name="transportation_details" 
                                rows="4" 
                                placeholder='{"departure_point":"Location","return_point":"Location","transportation_type":"Tour Bus"}'></textarea>
                            <small class="form-text text-muted">Optional JSON for transportation information</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-end">
                    <a href="attractions-add.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Attraction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include admin footer
include 'admin-footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle category dropdown
    const categorySelect = document.getElementById('category');
    const customCategoryInput = document.getElementById('custom_category');
    
    categorySelect.addEventListener('change', function() {
        if (this.value === 'other') {
            customCategoryInput.classList.remove('d-none');
            customCategoryInput.required = true;
        } else {
            customCategoryInput.classList.add('d-none');
            customCategoryInput.required = false;
        }
    });

    // Optional: Validate JSON inputs
    function isValidJSON(str) {
        try {
            JSON.parse(str);
            return true;
        } catch (e) {
            return false;
        }
    }

    const jsonInputs = ['itinerary', 'additional_info', 'faqs', 'transportation_details'];
    
    jsonInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        input.addEventListener('blur', function() {
            if (this.value.trim() !== '' && !isValidJSON(this.value)) {
                this.setCustomValidity('Please enter a valid JSON format');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});
</script>