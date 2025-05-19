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
$page_title = 'Edit Attraction - RoundTours';
$active_page = 'attractions';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: attractions-view.php');
    exit;
}

$attraction_id = intval($_GET['id']);

// Get attraction data
$query = "SELECT * FROM attractions WHERE attraction_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $attraction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: attractions-view.php');
    exit;
}

$attraction = $result->fetch_assoc();
$stmt->close();

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
    $errors = [];
    
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
    $image_url = $attraction['image_url']; // Keep existing image if no new one is uploaded
    
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
                // Delete old image if it exists
                if (!empty($image_url) && file_exists('../' . $image_url)) {
                    unlink('../' . $image_url);
                }
                
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

    // Validate JSON fields
    $json_fields = [
        'itinerary' => $itinerary,
        'additional_info' => $additional_info,
        'faqs' => $faqs,
        'transportation_details' => $transportation_details
    ];
    
    foreach ($json_fields as $field_name => $field_value) {
        if (!empty($field_value)) {
            json_decode($field_value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Invalid JSON format in $field_name field";
            }
        }
    }

    // If no errors, update attraction
    if (empty($errors)) {
        $sql = "UPDATE attractions SET 
                destination_id = ?, 
                name = ?, 
                description = ?, 
                image_url = ?, 
                category = ?, 
                address = ?, 
                entrance_fee = ?, 
                opening_hours = ?, 
                estimated_time_minutes = ?, 
                status = ?,
                itinerary = ?,
                additional_info = ?,
                faqs = ?,
                physical_requirements = ?,
                public_transportation_info = ?,
                transportation_details = ?
                WHERE attraction_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssdsissssssi", 
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
            $transportation_details,
            $attraction_id
        );
        
        if ($stmt->execute()) {
            // Redirect to attractions list with success message
            header('Location: attractions-view.php?updated=1');
            exit;
        } else {
            $errors[] = "Error updating attraction: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';

// Helper function to display JSON in textarea
function formatJson($json) {
    if (empty($json)) return '';
    $decoded = json_decode($json, true);
    if ($decoded === null) return $json;
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

<div class="main-content">
    <div class="page-header">
        <h1>Edit Attraction</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="attractions-view.php">Attractions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Attraction</li>
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
                                   value="<?php echo htmlspecialchars($attraction['name']); ?>" 
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
                                        <?php echo ($attraction['destination_id'] == $destination['destination_id']) ? 'selected' : ''; ?>>
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
                                required><?php echo htmlspecialchars($attraction['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select Category</option>
                                <?php foreach ($existing_categories as $category): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($category); ?>"
                                        <?php echo ($attraction['category'] == $category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other" <?php echo !in_array($attraction['category'], $existing_categories) && !empty($attraction['category']) ? 'selected' : ''; ?>>Other</option>
                            </select>
                            
                            <input type="text" 
                                   class="form-control mt-2 <?php echo !in_array($attraction['category'], $existing_categories) && !empty($attraction['category']) ? '' : 'd-none'; ?>" 
                                   id="custom_category" 
                                   name="custom_category" 
                                   placeholder="Enter custom category"
                                   value="<?php echo !in_array($attraction['category'], $existing_categories) && !empty($attraction['category']) ? htmlspecialchars($attraction['category']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Attraction Image</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*">
                            <small class="form-text text-muted">Recommended size: 1280x720 pixels</small>
                            
                            <?php if (!empty($attraction['image_url'])): ?>
                                <div class="mt-2">
                                    <p class="text-muted mb-1">Current Image:</p>
                                    <img src="../<?php echo htmlspecialchars($attraction['image_url']); ?>" alt="<?php echo htmlspecialchars($attraction['name']); ?>" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
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
                                   value="<?php echo htmlspecialchars($attraction['address']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="entrance_fee" class="form-label">Entrance Fee</label>
                            <input type="number" 
                                   step="0.01" 
                                   class="form-control" 
                                   id="entrance_fee" 
                                   name="entrance_fee" 
                                   value="<?php echo htmlspecialchars($attraction['entrance_fee']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="opening_hours" class="form-label">Opening Hours</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="opening_hours" 
                                   name="opening_hours" 
                                   value="<?php echo htmlspecialchars($attraction['opening_hours']); ?>"
                                   placeholder="e.g., 8:00 AM - 5:00 PM">
                        </div>
                        
                        <div class="mb-3">
                            <label for="estimated_time_minutes" class="form-label">Estimated Visit Time (minutes)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="estimated_time_minutes" 
                                   name="estimated_time_minutes" 
                                   value="<?php echo htmlspecialchars($attraction['estimated_time_minutes']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($attraction['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($attraction['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="physical_requirements" class="form-label">Physical Requirements</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="physical_requirements" 
                                   name="physical_requirements" 
                                   value="<?php echo htmlspecialchars($attraction['physical_requirements']); ?>"
                                   placeholder="e.g., Moderate walking, some steps">
                        </div>
                        
                        <div class="mb-3">
                            <label for="public_transportation_info" class="form-label">Public Transportation Info</label>
                            <textarea 
                                class="form-control" 
                                id="public_transportation_info" 
                                name="public_transportation_info" 
                                rows="3"><?php echo htmlspecialchars($attraction['public_transportation_info']); ?></textarea>
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
                                placeholder='[{"title":"Example Title","description":"Description","duration":"30 minutes"}]'><?php echo formatJson($attraction['itinerary']); ?></textarea>
                            <small class="form-text text-muted">Optional JSON format for tours/activities</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_info" class="form-label">Additional Information (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="additional_info" 
                                name="additional_info" 
                                rows="4" 
                                placeholder='[{"icon":"info-circle","color":"info","content":"Important detail"}]'><?php echo formatJson($attraction['additional_info']); ?></textarea>
                            <small class="form-text text-muted">Optional JSON for extra details with icons</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="faqs" class="form-label">Frequently Asked Questions (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="faqs" 
                                name="faqs" 
                                rows="4" 
                                placeholder='[{"question":"What should I know?","answer":"Important information"}]'><?php echo formatJson($attraction['faqs']); ?></textarea>
                            <small class="form-text text-muted">Optional JSON for FAQs</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transportation_details" class="form-label">Transportation Details (JSON format)</label>
                            <textarea 
                                class="form-control" 
                                id="transportation_details" 
                                name="transportation_details" 
                                rows="4" 
                                placeholder='{"departure_point":"Location","return_point":"Location","transportation_type":"Tour Bus"}'><?php echo formatJson($attraction['transportation_details']); ?></textarea>
                            <small class="form-text text-muted">Optional JSON for transportation information</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-end">
                    <a href="attractions-edit.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Attraction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>

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
            customCategoryInput.value = '';
        }
    });

    // Optional: Validate JSON inputs
    function isValidJSON(str) {
        if (!str.trim()) return true; // Empty is valid (optional field)
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
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    });
});