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
$page_title = 'Admin Dashboard - RoundTours';
$active_page = 'dashboard';

// Initialize counts
$users_count = 0;
$destinations_count = 0;
$attractions_count = 0;
$bookings_count = 0;
$blogs_count = 0; // ✅ NEW
$hotels_count = 0;
$rooms_count = 0;
$flights_count = 0;

// Count users
$result = $conn->query("SELECT COUNT(*) AS count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $users_count = $row['count'];
}

// Count destinations
$result = $conn->query("SELECT COUNT(*) AS count FROM destinations");
if ($result) {
    $row = $result->fetch_assoc();
    $destinations_count = $row['count'];
}

// Count attractions
$result = $conn->query("SELECT COUNT(*) AS count FROM attractions");
if ($result) {
    $row = $result->fetch_assoc();
    $attractions_count = $row['count'];
}

// Count bookings
$result = $conn->query("SELECT COUNT(*) AS count FROM bookings");
if ($result) {
    $row = $result->fetch_assoc();
    $bookings_count = $row['count'];
}

// ✅ Count blog posts
$result = $conn->query("SELECT COUNT(*) AS count FROM Blogs");
if ($result) {
    $row = $result->fetch_assoc();
    $blogs_count = $row['count'];
}

// Count hotels posts
$result = $conn->query("SELECT COUNT(*) AS count FROM hotels");
if ($result) {
    $row = $result->fetch_assoc();
    $hotels_count = $row['count'];
}

// Count rooms posts
$result = $conn->query("SELECT COUNT(*) AS count FROM rooms");
if ($result) {
    $row = $result->fetch_assoc();
    $rooms_count = $row['count'];
}

// Count flights posts
$result = $conn->query("SELECT COUNT(*) AS count FROM flights");
if ($result) {
    $row = $result->fetch_assoc();
    $flights_count = $row['count'];
}

// Recent users
$recent_users = [];
$result = $conn->query("SELECT user_id, first_name, last_name, email, registration_date FROM users ORDER BY registration_date DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Users</h5>
                            <h2 class="mt-2 mb-0"><?php echo $users_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-primary-subtle">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="users.php" class="text-decoration-none">View all users <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Destinations</h5>
                            <h2 class="mt-2 mb-0"><?php echo $destinations_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-success-subtle">
                            <i class="bi bi-map"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="destinations-view.php" class="text-decoration-none">View all destinations <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Attractions</h5>
                            <h2 class="mt-2 mb-0"><?php echo $attractions_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-info-subtle">
                            <i class="bi bi-compass"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="attractions-view.php" class="text-decoration-none">View all attractions <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Bookings</h5>
                            <h2 class="mt-2 mb-0"><?php echo $bookings_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-warning-subtle">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="bookings-view.php" class="text-decoration-none">View all bookings <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Blogs -->
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Blogs</h5>
                            <h2 class="mt-2 mb-0"><?php echo $blogs_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-secondary-subtle">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="blog-list.php" class="text-decoration-none">Manage blogs <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Hotels</h5>
                            <h2 class="mt-2 mb-0"><?php echo $hotels_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-secondary-subtle">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="hotels-view.php" class="text-decoration-none">Manage hotels <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Rooms</h5>
                            <h2 class="mt-2 mb-0"><?php echo $rooms_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-secondary-subtle">
                            <i class="bi bi-hospital"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="rooms-view.php" class="text-decoration-none">Manage rooms <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Total Flights</h5>
                            <h2 class="mt-2 mb-0"><?php echo $flights_count; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-secondary-subtle">
                            <i class="bi bi-airplane"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="flights-view.php" class="text-decoration-none">Manage flights <i class="bi bi-arrow-right"></i></a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Date Joined</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_users)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                            <td>
                                                <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="user-view.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="user-add.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Add New User
                        </a>
                        <a href="destinations-add.php" class="btn btn-outline-success">
                            <i class="bi bi-plus-circle"></i> Add New Destination
                        </a>
                        <a href="attractions-add.php" class="btn btn-outline-info">
                            <i class="bi bi-compass"></i> Add New Attraction
                        </a>
                        <a href="blog-create.php" class="btn btn-outline-secondary">
                            <i class="bi bi-journal-plus"></i> Add New Blog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include admin footer
include 'admin-footer.php';
?>