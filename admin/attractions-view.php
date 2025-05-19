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
$page_title = 'Attractions - RoundTours';
$active_page = 'attractions';

// Handle attraction deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $attraction_id = intval($_GET['delete']);
    
    // Get image URL before deletion to delete the file after
    $image_query = "SELECT image_url FROM attractions WHERE attraction_id = ?";
    $image_stmt = $conn->prepare($image_query);
    $image_stmt->bind_param("i", $attraction_id);
    $image_stmt->execute();
    $image_stmt->bind_result($image_url);
    $image_stmt->fetch();
    $image_stmt->close();
    
    // Delete the attraction
    $delete_query = $conn->prepare("DELETE FROM attractions WHERE attraction_id = ?");
    $delete_query->bind_param("i", $attraction_id);
    
    if ($delete_query->execute()) {
        // Delete image file if it exists
        if (!empty($image_url) && file_exists('../' . $image_url)) {
            unlink('../' . $image_url);
        }
        
        header("Location: attractions.php?deleted=1");
        exit;
    } else {
        $delete_error = "Error deleting attraction: " . $conn->error;
    }
    $delete_query->close();
}

// Get attractions list with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Filter by destination
$destination_filter = isset($_GET['destination']) && is_numeric($_GET['destination']) ? intval($_GET['destination']) : 0;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];
$param_types = '';

// Build search conditions
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(a.name LIKE ? OR a.description LIKE ? OR a.category LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $param_types = 'sss';
}

if ($destination_filter > 0) {
    $where_conditions[] = "a.destination_id = ?";
    $params[] = $destination_filter;
    $param_types .= 'i';
}

// Combine conditions
if (!empty($where_conditions)) {
    $search_condition = " WHERE " . implode(" AND ", $where_conditions);
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM attractions a" . $search_condition;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Get attractions for current page
$query = "SELECT a.attraction_id, a.name, a.image_url, a.category, a.status, 
          d.location_name as destination_name 
          FROM attractions a 
          LEFT JOIN destinations d ON a.destination_id = d.destination_id" 
          . $search_condition . 
          " ORDER BY a.name LIMIT ?, ?";

$param_types .= 'ii';
$params[] = $offset;
$params[] = $records_per_page;

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$attractions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all destinations for filter dropdown
$destinations_query = "SELECT destination_id, location_name FROM destinations ORDER BY location_name";
$destinations_result = $conn->query($destinations_query);
$destinations = [];
if ($destinations_result) {
    while ($row = $destinations_result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Attractions</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attractions</li>
                </ol>
            </nav>
        </div>
        <a href="attractions-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Attraction
        </a>
    </div>
    
    <?php if (isset($_GET['created']) && $_GET['created'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Attraction has been created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Attraction has been updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Attraction has been deleted successfully.
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h5 class="card-title">All Attractions</h5>
                <div class="d-flex align-items-center">
                    <form class="d-flex flex-wrap" method="GET">
                        <div class="input-group me-2 mb-2 mb-md-0">
                            <select class="form-select" name="destination" style="max-width: 200px;">
                                <option value="0">All Destinations</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?php echo $destination['destination_id']; ?>" <?php echo $destination_filter == $destination['destination_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($destination['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="submit">Filter</button>
                        </div>
                        <div class="input-group mb-2 mb-md-0">
                            <input class="form-control" type="search" placeholder="Search attractions..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">Search</button>
                        </div>
                        <?php if (!empty($search) || $destination_filter > 0): ?>
                            <a href="attractions.php" class="btn btn-outline-secondary ms-2 mb-2 mb-md-0">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Image</th>
                            <th scope="col">Name</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Category</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attractions)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No attractions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attractions as $attraction): ?>
                                <tr>
                                    <td><?php echo $attraction['attraction_id']; ?></td>
                                    <td>
                                        <?php if (!empty($attraction['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($attraction['image_url']); ?>" alt="<?php echo htmlspecialchars($attraction['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f8f9fa;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($attraction['name']); ?></td>
                                    <td><?php echo htmlspecialchars($attraction['destination_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attraction['category'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $attraction['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($attraction['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="attractions-edit.php?id=<?php echo $attraction['attraction_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $attraction['attraction_id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                        
                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $attraction['attraction_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete <strong><?php echo htmlspecialchars($attraction['name']); ?></strong>? This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="attractions.php?delete=<?php echo $attraction['attraction_id']; ?>" class="btn btn-danger">Delete</a>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $destination_filter > 0 ? '&destination=' . $destination_filter : ''; ?>" aria-label="Previous">
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
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $destination_filter > 0 ? '&destination=' . $destination_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $destination_filter > 0 ? '&destination=' . $destination_filter : ''; ?>" aria-label="Next">
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