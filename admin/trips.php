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
$page_title = 'Trips - RoundTours';
$active_page = 'trips';

// Handle Trip deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $trip_id = intval($_GET['delete']);

    // Delete the Trip
    $delete_query = $conn->prepare("DELETE FROM Trips WHERE trip_id = ?");
    $delete_query->bind_param("i", $trip_id);

    if ($delete_query->execute()) {
        header("Location: trips.php?deleted=1");
        exit;
    } else {
        $delete_error = "Error deleting Trip: " . $conn->error;
    }
    $delete_query->close();
}

// Handle Trip status updates
if (isset($_GET['status']) && is_numeric($_GET['trip_id'])) {
    $trip_id = intval($_GET['trip_id']);
    $new_status = $_GET['status'];
    
    // Valid statuses
    $valid_statuses = ['planned', 'in_progress', 'completed', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        // Update trip status
        $update_query = $conn->prepare("UPDATE Trips SET status = ? WHERE trip_id = ?");
        $update_query->bind_param("si", $new_status, $trip_id);
        
        if ($update_query->execute()) {
            header("Location: trips.php?updated=1");
            exit;
        } else {
            $update_error = "Error updating Trip status: " . $conn->error;
        }
        $update_query->close();
    }
}

// Get trips list with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];
$param_types = '';

// Filter by status
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$status_condition = '';

// Filter by date
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$date_condition = '';

if (!empty($status_filter) && $status_filter !== 'all') {
    $status_condition = " AND t.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($date_filter === 'upcoming') {
    $date_condition = " AND t.start_date > CURDATE()";
}

if (!empty($search)) {
    $search_condition = " AND (t.name LIKE ? OR d.location_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $param_types .= 'ss';
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT t.trip_id) FROM Trips t 
               LEFT JOIN Trip_Destinations td ON t.trip_id = td.trip_id 
               LEFT JOIN Destinations d ON td.destination_id = d.destination_id 
               WHERE 1=1" . $status_condition . $date_condition . $search_condition;

$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Get trips for current page
$query = "SELECT DISTINCT t.*,
         (SELECT location_name FROM Destinations d 
          JOIN Trip_Destinations td ON d.destination_id = td.destination_id 
          WHERE td.trip_id = t.trip_id 
          ORDER BY td.order_index LIMIT 1) as main_destination,
         (SELECT COUNT(*) FROM Trip_Destinations WHERE trip_id = t.trip_id) as destination_count,
         (SELECT COUNT(*) FROM Trip_Itinerary WHERE trip_id = t.trip_id) as itinerary_count,
         u.user_id, u.email
         FROM Trips t 
         JOIN users u ON t.user_id = u.user_id 
         LEFT JOIN Trip_Destinations td ON t.trip_id = td.trip_id 
         LEFT JOIN Destinations d ON td.destination_id = d.destination_id 
         WHERE 1=1" . $status_condition . $date_condition . $search_condition . 
         " GROUP BY t.trip_id
         ORDER BY t.start_date DESC
         LIMIT ?, ?";

$param_types .= 'ii';
$params[] = $offset;
$params[] = $records_per_page;

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$trips = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Trips</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trips</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Trip has been updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Trip has been deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($delete_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo $delete_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($update_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo $update_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title">All Trips</h5>
                <div class="d-flex">
                    <form class="d-flex me-2" method="GET">
                        <?php if (!empty($status_filter) && $status_filter !== 'all'): ?>
                            <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input class="form-control me-2" type="search" placeholder="Search trips..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="trips.php<?php echo !empty($status_filter) && $status_filter !== 'all' ? '?status_filter=' . urlencode($status_filter) : ''; ?>" class="btn btn-outline-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                            $current_filter = "All Statuses";
                            if ($status_filter === 'planned') $current_filter = "Planned";
                            if ($status_filter === 'in_progress') $current_filter = "In Progress";
                            if ($status_filter === 'completed') $current_filter = "Completed";
                            if ($status_filter === 'cancelled') $current_filter = "Cancelled";
                            echo $current_filter;
                            ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
                            <li><a class="dropdown-item <?php echo empty($status_filter) || $status_filter === 'all' ? 'active' : ''; ?>" href="trips.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>">All Statuses</a></li>
                            <li><a class="dropdown-item <?php echo $status_filter === 'planned' ? 'active' : ''; ?>" href="trips.php?status_filter=planned<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Planned</a></li>
                            <li><a class="dropdown-item <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>" href="trips.php?status_filter=in_progress<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">In Progress</a></li>
                            <li><a class="dropdown-item <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" href="trips.php?status_filter=completed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Completed</a></li>
                            <li><a class="dropdown-item <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>" href="trips.php?status_filter=cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Cancelled</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Trip Name</th>
                            <th scope="col">User Email</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Dates</th>
                            <th scope="col">Status</th>
                            <th scope="col">Itinerary Items</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trips)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No trips found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trips as $trip): ?>
                                <tr>
                                    <td><?php echo $trip['trip_id']; ?></td>
                                    <td><?php echo htmlspecialchars($trip['name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['email']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($trip['main_destination'] ?? 'No destination'); ?>
                                        <?php if ($trip['destination_count'] > 1): ?>
                                            <span class="badge bg-secondary">+<?php echo $trip['destination_count'] - 1; ?> more</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                                            echo date('M d, Y', strtotime($trip['start_date'])) . ' - ' . 
                                                 date('M d, Y', strtotime($trip['end_date']));
                                        } elseif (!empty($trip['start_date'])) {
                                            echo 'From ' . date('M d, Y', strtotime($trip['start_date']));
                                        } elseif (!empty($trip['end_date'])) {
                                            echo 'Until ' . date('M d, Y', strtotime($trip['end_date']));
                                        } else {
                                            echo 'Dates not set';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'bg-secondary';
                                        if ($trip['status'] == 'planned') $status_class = 'bg-primary';
                                        if ($trip['status'] == 'in_progress') $status_class = 'bg-info';
                                        if ($trip['status'] == 'completed') $status_class = 'bg-success';
                                        if ($trip['status'] == 'cancelled') $status_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $trip['itinerary_count']; ?> items
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $trip['trip_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $trip['trip_id']; ?>">
                                                <li><a class="dropdown-item" href="../trip-details.php?id=<?php echo $trip['trip_id']; ?>" target="_blank">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a></li>
                                                
                                                <!-- Status Update Options -->
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header">Update Status</h6></li>
                                                <?php if ($trip['status'] !== 'planned'): ?>
                                                <li><a class="dropdown-item" href="trips.php?trip_id=<?php echo $trip['trip_id']; ?>&status=planned">
                                                    <i class="bi bi-calendar-check"></i> Mark as Planned
                                                </a></li>
                                                <?php endif; ?>
                                                
                                                <?php if ($trip['status'] !== 'in_progress'): ?>
                                                <li><a class="dropdown-item" href="trips.php?trip_id=<?php echo $trip['trip_id']; ?>&status=in_progress">
                                                    <i class="bi bi-hourglass-split"></i> Mark as In Progress
                                                </a></li>
                                                <?php endif; ?>
                                                
                                                <?php if ($trip['status'] !== 'completed'): ?>
                                                <li><a class="dropdown-item" href="trips.php?trip_id=<?php echo $trip['trip_id']; ?>&status=completed">
                                                    <i class="bi bi-check-circle"></i> Mark as Completed
                                                </a></li>
                                                <?php endif; ?>
                                                
                                                <?php if ($trip['status'] !== 'cancelled'): ?>
                                                <li><a class="dropdown-item" href="trips.php?trip_id=<?php echo $trip['trip_id']; ?>&status=cancelled">
                                                    <i class="bi bi-x-circle"></i> Mark as Cancelled
                                                </a></li>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Option -->
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $trip['trip_id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete Trip
                                                </a></li>
                                            </ul>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $trip['trip_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete the trip <strong><?php echo htmlspecialchars($trip['name']); ?></strong>?</p>
                                                        <p class="text-danger">This will permanently remove all trip data, including itineraries and destinations. This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="trips.php?delete=<?php echo $trip['trip_id']; ?>" class="btn btn-danger">Delete</a>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>
                                <?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>
                                <?php echo !empty($status_filter) && $status_filter !== 'all' ? '&status_filter=' . urlencode($status_filter) : ''; ?>
                                <?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?>" aria-label="Previous">
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
                                <a class="page-link" href="?page=<?php echo $i; ?>
                                <?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>
                                <?php echo !empty($status_filter) && $status_filter !== 'all' ? '&status_filter=' . urlencode($status_filter) : ''; ?>
                                <?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>
                                <?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>
                                <?php echo !empty($status_filter) && $status_filter !== 'all' ? '&status_filter=' . urlencode($status_filter) : ''; ?>
                                <?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?>" aria-label="Next">
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

    <!-- Dashboard Cards -->
    <div class="row mt-4">
        <!-- Total Trips Card -->
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Total Trips</div>
                            <?php
                            $total_query = "SELECT COUNT(*) as count FROM Trips";
                            $total_result = $conn->query($total_query);
                            $total_count = $total_result->fetch_assoc()['count'];
                            ?>
                            <div class="text-lg fw-bold"><?php echo $total_count; ?></div>
                        </div>
                        <i class="bi bi-map fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="trips.php">View All Trips</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>

        <!-- Upcoming Trips Card -->
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Upcoming Trips</div>
                            <?php
                            $upcoming_query = "SELECT COUNT(*) as count FROM Trips WHERE start_date > CURDATE()";
                            $upcoming_result = $conn->query($upcoming_query);
                            $upcoming_count = $upcoming_result->fetch_assoc()['count'];
                            ?>
                            <div class="text-lg fw-bold"><?php echo $upcoming_count; ?></div>
                        </div>
                        <i class="bi bi-calendar-check fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="trips.php?date_filter=upcoming">View Upcoming Trips</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>

        <!-- In Progress Trips Card -->
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">In Progress Trips</div>
                            <?php
                            $in_progress_query = "SELECT COUNT(*) as count FROM Trips WHERE status = 'in_progress'";
                            $in_progress_result = $conn->query($in_progress_query);
                            $in_progress_count = $in_progress_result->fetch_assoc()['count'];
                            ?>
                            <div class="text-lg fw-bold"><?php echo $in_progress_count; ?></div>
                        </div>
                        <i class="bi bi-hourglass-split fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="trips.php?status_filter=in_progress">View In Progress Trips</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>

        <!-- Cancelled Trips Card -->
        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">Cancelled Trips</div>
                            <?php
                            $cancelled_query = "SELECT COUNT(*) as count FROM Trips WHERE status = 'cancelled'";
                            $cancelled_result = $conn->query($cancelled_query);
                            $cancelled_count = $cancelled_result->fetch_assoc()['count'];
                            ?>
                            <div class="text-lg fw-bold"><?php echo $cancelled_count; ?></div>
                        </div>
                        <i class="bi bi-x-circle fs-1"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="trips.php?status_filter=cancelled">View Cancelled Trips</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Destinations -->
    <div class="card mt-4 mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Popular Destinations</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Destination</th>
                            <th>Trip Count</th>
                            <th>Average Trip Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query for popular destinations
                        $destinations_query = "SELECT d.location_name, 
                                              COUNT(DISTINCT td.trip_id) as trip_count,
                                              AVG(DATEDIFF(t.end_date, t.start_date)) as avg_duration
                                              FROM Destinations d
                                              JOIN Trip_Destinations td ON d.destination_id = td.destination_id
                                              JOIN Trips t ON td.trip_id = t.trip_id
                                              WHERE t.start_date IS NOT NULL AND t.end_date IS NOT NULL
                                              GROUP BY d.destination_id
                                              ORDER BY trip_count DESC
                                              LIMIT 5";
                        
                        $dest_result = $conn->query($destinations_query);
                        
                        if ($dest_result && $dest_result->num_rows > 0) {
                            while ($dest = $dest_result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($dest['location_name']) . '</td>';
                                echo '<td>' . $dest['trip_count'] . '</td>';
                                echo '<td>' . round($dest['avg_duration'], 1) . ' days</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No destination data available</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>