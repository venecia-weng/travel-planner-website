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
$page_title = 'Bookings - RoundTours';
$active_page = 'bookings';

// Handle Booking deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);

    // Delete the Booking
    $delete_query = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $delete_query->bind_param("i", $booking_id);

    if ($delete_query->execute()) {
        header("Location: bookings-view.php?deleted=1");
        exit;
    } else {
        $delete_error = "Error deleting Booking: " . $conn->error;
    }
    $delete_query->close();
}

// Get bookings list with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];
$param_types = '';

if (!empty($search)) {
    $search_condition = " WHERE b.booking_type LIKE ? OR p.transaction_id LIKE ? OR f.flight_number LIKE ? OR r.room_type LIKE ? OR a.name LIKE ? or b.total_price LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param];
    $param_types = 'ssssss';
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM bookings b
INNER JOIN users u ON b.user_id = u.user_id 
INNER JOIN payments p ON b.payment_id = p.payment_id 
LEFT JOIN flights f ON b.booking_type = 'flight' AND b.item_id = f.flight_id 
LEFT JOIN airlines air ON air.airline_id = f.airline_id 
LEFT JOIN rooms r ON b.booking_type = 'room' AND b.item_id = r.room_id 
LEFT JOIN attractions a ON b.booking_type = 'attraction' AND b.item_id = a.attraction_id" . $search_condition;
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Get bookings for current page
$query = "SELECT b.*, u.*, p.*, f.*, r.*, a.*, air.logo_url FROM bookings b 
INNER JOIN users u ON b.user_id = u.user_id 
INNER JOIN payments p ON b.payment_id = p.payment_id 
LEFT JOIN flights f ON b.booking_type = 'flight' AND b.item_id = f.flight_id 
LEFT JOIN airlines air ON air.airline_id = f.airline_id 
LEFT JOIN rooms r ON b.booking_type = 'room' AND b.item_id = r.room_id 
LEFT JOIN attractions a ON b.booking_type = 'attraction' AND b.item_id = a.attraction_id" . $search_condition . " LIMIT ?, ?";
$stmt = $conn->prepare($query);

$param_types .= 'ii';
$params[] = $offset;
$params[] = $records_per_page;

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Bookings</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bookings</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_GET['created']) && $_GET['created'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Booking has been created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Booking has been updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Booking has been deleted successfully.
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
                <h5 class="card-title">All Bookings</h5>
                <form class="d-flex" method="GET">
                    <input class="form-control me-2" type="search" placeholder="Search bookings..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="bookings-view.php" class="btn btn-outline-secondary ms-2">Clear</a>
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
                            <th scope="col">Booking Type</th>
                            <th scope="col">Transaction ID</th>
                            <th scope="col">Total Price</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No bookings found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo $booking['booking_id']; ?></td>
                                    <td>
                                        <?php
                                        $imgUrl = '';
                                        if ($booking['booking_type'] == 'flight') {
                                            $imgUrl = $booking['logo_url'];
                                        } elseif ($booking['booking_type'] == 'room') {
                                            $imgUrl = $booking['main_img_url'];
                                        } elseif ($booking['booking_type'] == 'attraction') {
                                            $imgUrl = $booking['image_url'];
                                        }

                                        if (!empty($imgUrl)):
                                        ?>
                                            <img src="../<?php echo htmlspecialchars($imgUrl); ?>"
                                                alt="<?php echo htmlspecialchars($booking['name'] ?? 'Booking Image'); ?>"
                                                class="img-thumbnail"
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php
                                        endif;
                                        ?>
                                    </td>
                                    <td><?php
                                        $name = '';
                                        if ($booking['booking_type'] == 'flight') $name = $booking['flight_number'];
                                        if ($booking['booking_type'] == 'room') $name = $booking['room_type'];
                                        if ($booking['booking_type'] == 'attraction') $name = $booking['name'];
                                        echo $name;
                                        ?></td>
                                    <td><?php echo htmlspecialchars($booking['booking_type']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['transaction_id']); ?></td>
                                    <td> <?php echo htmlspecialchars($booking['currency'] . $booking['total_price']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $booking['booking_id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete <strong>
                                                            <?php $name = '';
                                                            if ($booking['booking_type'] == 'flight') $name = $booking['flight_number'];
                                                            if ($booking['booking_type'] == 'room') $name = $booking['room_type'];
                                                            if ($booking['booking_type'] == 'attraction') $name = $booking['name'];
                                                            echo $name; ?>
                                                        </strong>? This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="bookings-view.php?delete=<?php echo $booking['booking_id']; ?>" class="btn btn-danger">Delete</a>
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