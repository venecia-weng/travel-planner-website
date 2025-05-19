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
$page_title = 'Manage Users - RoundTours Admin';
$active_page = 'users';

// Pagination setup
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($current_page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

// Special filters for admin/regular users and locked accounts
if ($search === 'admin') {
    // Case-insensitive comparison using LOWER function
    $search_condition = " WHERE LOWER(role) = 'admin'";
} elseif ($search === 'user') {
    // Case-insensitive comparison using LOWER function
    $search_condition = " WHERE LOWER(role) = 'user'";
} elseif ($search === 'locked') {
    // Filter for locked accounts
    $search_condition = " WHERE account_locked = 1";
} elseif (!empty($search)) {
    $search_condition = " WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $search_params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM users" . $search_condition;
$stmt = $conn->prepare($count_sql);

if (!empty($search_params)) {
    $stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with pagination
$sql = "SELECT user_id, first_name, last_name, email, phone_number, role, registration_date, account_locked, failed_login_attempts 
        FROM users" . $search_condition . " 
        ORDER BY registration_date DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);

if (!empty($search_params)) {
    // Add pagination parameters
    $search_params[] = $offset;
    $search_params[] = $records_per_page;
    
    // Creating the correct types string
    $types = str_repeat('s', count($search_params) - 2) . 'ii';
    
    $stmt->bind_param($types, ...$search_params);
} else {
    $stmt->bind_param('ii', $offset, $records_per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Handle unlock account if requested
if (isset($_POST['unlock_account']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Reset failed login attempts and unlock account
        $unlock_stmt = $conn->prepare("UPDATE users SET account_locked = 0, failed_login_attempts = 0 WHERE user_id = ?");
        $unlock_stmt->bind_param('i', $user_id);
        
        if ($unlock_stmt->execute()) {
            // Log the action
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['admin_id'];
            $event_details = "Admin (ID: {$admin_id}) unlocked account through users list for user ID: {$user_id}";
            
            $log_stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES ('account_unlock', ?, ?)");
            $log_stmt->bind_param("ss", $event_details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "User account unlocked successfully";
            // Redirect to refresh the page and prevent form resubmission
            header("Location: users.php?success=" . urlencode($success_message));
            exit;
        } else {
            throw new Exception("Error unlocking account: " . $conn->error);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Handle delete user if requested
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow deleting yourself
    if ($user_id != $_SESSION['admin_id']) {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $delete_stmt->bind_param('i', $user_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "User deleted successfully";
            // Redirect to refresh the page and prevent form resubmission
            header("Location: users.php?success=" . urlencode($success_message));
            exit;
        } else {
            $error_message = "Error deleting user: " . $conn->error;
        }
    } else {
        $error_message = "You cannot delete your own account";
    }
}

// Process success message from URL if present
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Manage Users</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
            </div>
            <div class="col-auto">
                <a href="user-add.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add New User
                </a>
            </div>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="users.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <a href="users.php" class="btn btn-outline-secondary <?php echo empty($search) ? 'active' : ''; ?>">All</a>
                        <a href="users.php?search=admin" class="btn btn-outline-secondary <?php echo ($search == 'admin') ? 'active' : ''; ?>">Admins</a>
                        <a href="users.php?search=user" class="btn btn-outline-secondary <?php echo ($search == 'user') ? 'active' : ''; ?>">Regular Users</a>
                        <a href="users.php?search=locked" class="btn btn-outline-secondary <?php echo ($search == 'locked') ? 'active' : ''; ?>">Locked Accounts</a>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Joined Date</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                    <td>
                                        <?php if (strtolower($user['role']) == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['account_locked'] == 1): ?>
                                            <span class="badge bg-danger">Locked</span>
                                        <?php else: ?>
                                            <?php if ($user['failed_login_attempts'] > 0): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $user['failed_login_attempts']; ?> Failed Attempts</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="user-view.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($user['account_locked'] == 1): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Unlock Account" data-bs-toggle="modal" data-bs-target="#unlockModal<?php echo $user['user_id']; ?>">
                                                    <i class="bi bi-unlock"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['user_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Unlock Modal -->
                                        <?php if ($user['account_locked'] == 1): ?>
                                        <div class="modal fade" id="unlockModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="unlockModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="unlockModalLabel<?php echo $user['user_id']; ?>">Confirm Unlock</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <p>Are you sure you want to unlock the account for <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>?</p>
                                                        <p>This will reset the failed login attempts counter and allow the user to log in again.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" action="users.php">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                            <button type="submit" name="unlock_account" class="btn btn-warning">Unlock Account</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $user['user_id']; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>? This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" action="users.php">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                                        </form>
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include admin footer
include 'admin-footer.php';
?>