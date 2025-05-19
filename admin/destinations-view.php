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
$page_title = 'Destinations - RoundTours';
$active_page = 'destinations';

// Handle destination deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $destination_id = intval($_GET['delete']);
    
    // Check if destination has attractions linked to it
    $check_attractions = $conn->prepare("SELECT COUNT(*) FROM attractions WHERE destination_id = ?");
    $check_attractions->bind_param("i", $destination_id);
    $check_attractions->execute();
    $check_attractions->bind_result($attraction_count);
    $check_attractions->fetch();
    $check_attractions->close();
    
    if ($attraction_count > 0) {
        $delete_error = "Cannot delete destination. It has $attraction_count attractions linked to it. Please remove the attractions first.";
    } else {
        // Delete the destination
        $delete_query = $conn->prepare("DELETE FROM destinations WHERE destination_id = ?");
        $delete_query->bind_param("i", $destination_id);
        
        if ($delete_query->execute()) {
            header("Location: destinations.php?deleted=1");
            exit;
        } else {
            $delete_error = "Error deleting destination: " . $conn->error;
        }
        $delete_query->close();
    }
}

// Get destinations list with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];
$param_types = '';

if (!empty($search)) {
    $search_condition = " WHERE location_name LIKE ? OR country LIKE ? OR city LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $param_types = 'sss';
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM destinations" . $search_condition;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Get destinations for current page
$query = "SELECT destination_id, location_name, country, city, status, main_image_url FROM destinations" . $search_condition . " ORDER BY location_name LIMIT ?, ?";
$stmt = $conn->prepare($query);

$param_types .= 'ii';
$params[] = $offset;
$params[] = $records_per_page;

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$destinations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Destinations</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Destinations</li>
                </ol>
            </nav>
        </div>
        <a href="destinations-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Destination
        </a>
    </div>
    
    <?php if (isset($_GET['created']) && $_GET['created'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Destination has been created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Destination has been updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Destination has been deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($delete_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo $delete_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title">All Destinations</h5>
                <form class="d-flex" method="GET">
                    <input class="form-control me-2" type="search" placeholder="Search destinations..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="destinations.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Image</th>
                            <th scope="col">Name</th>
                            <th scope="col">Country</th>
                            <th scope="col">City</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($destinations)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No destinations found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($destinations as $destination): ?>
                                <tr>
                                    <td><?php echo $destination['destination_id']; ?></td>
                                    <td>
                                        <?php if (!empty($destination['main_image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($destination['main_image_url']); ?>" alt="<?php echo htmlspecialchars($destination['location_name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($destination['location_name']); ?></td>
                                    <td><?php echo htmlspecialchars($destination['country']); ?></td>
                                    <td><?php echo htmlspecialchars($destination['city']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $destination['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($destination['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="destinations-edit.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $destination['destination_id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                        
                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $destination['destination_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete <strong><?php echo htmlspecialchars($destination['location_name']); ?></strong>? This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="destinations.php?delete=<?php echo $destination['destination_id']; ?>" class="btn btn-danger">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4 && $start_page > 1) {
                            $start_page = max(1, $end_page - 4);
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>