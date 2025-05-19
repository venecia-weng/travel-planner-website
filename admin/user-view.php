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
$page_title = 'User Details - RoundTours Admin';
$active_page = 'users';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php?error=No user specified');
    exit;
}

$user_id = $_GET['id'];

// Get user data
// Added account_locked and failed_login_attempts to the query
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, phone_number, role, registration_date, updated_at, account_locked, failed_login_attempts, last_login, last_login_attempt FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    header('Location: users.php?error=User not found');
    exit;
}
$stmt->close();

// Get user's security log events if security_log table exists
$security_events = [];
try {
    $stmt = $conn->prepare("SELECT event_type, event_details, ip_address, timestamp FROM security_log 
                           WHERE event_details LIKE ? ORDER BY timestamp DESC LIMIT 5");
    $search_term = "%ID: {$user_id}%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $log_result = $stmt->get_result();

    while ($row = $log_result->fetch_assoc()) {
        $security_events[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    // Security log table might not exist yet, ignore the error
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>User Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">User Details</li>
                    </ol>
                </nav>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <a href="user-edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit User
                    </a>
                    <?php if ($user['account_locked'] == 1): ?>
                    <a href="user-unlock.php?id=<?php echo $user_id; ?>" class="btn btn-warning">
                        <i class="bi bi-unlock"></i> Unlock Account
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-placeholder mb-3 mx-auto">
                            <i class="bi bi-person-circle display-4"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <div class="d-flex justify-content-center gap-2">
                            <p class="mb-0">
                                <?php 
                                // Using case-insensitive role comparison
                                echo (strtolower($user['role']) == 'admin') ? 
                                    '<span class="badge bg-danger">Administrator</span>' : 
                                    '<span class="badge bg-info">Regular User</span>'; 
                                ?>
                            </p>
                            <?php if ($user['account_locked'] == 1): ?>
                                <p class="mb-0">
                                    <span class="badge bg-danger">Account Locked</span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="user-details">
                        <div class="mb-3">
                            <label class="form-label text-muted">Email</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope me-2 text-primary"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone me-2 text-primary"></i>
                                <span><?php echo htmlspecialchars($user['phone_number']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Joined</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check me-2 text-primary"></i>
                                <span><?php echo date('F d, Y', strtotime($user['registration_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Last Login</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-box-arrow-in-right me-2 text-primary"></i>
                                <span>
                                <?php 
                                    if (!empty($user['last_login']) && $user['last_login'] != '0000-00-00 00:00:00') {
                                        echo date('F d, Y H:i', strtotime($user['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Failed Login Attempts</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle me-2 text-primary"></i>
                                <span>
                                    <?php 
                                        echo htmlspecialchars($user['failed_login_attempts']); 
                                        if ($user['failed_login_attempts'] > 0 && !empty($user['last_login_attempt'])) {
                                            echo ' (Last attempt: ' . date('F d, Y H:i', strtotime($user['last_login_attempt'])) . ')';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="form-label text-muted">Last Updated</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-arrow-clockwise me-2 text-primary"></i>
                                <span><?php echo date('F d, Y', strtotime($user['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <a href="users.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Users
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                            <i class="bi bi-trash"></i> Delete User
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <?php if ($user['account_locked'] == 1): ?>
            <div class="alert alert-danger mb-4">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i> Account Locked</h5>
                <p>This user account has been automatically locked due to multiple failed login attempts. This is a security measure to protect against unauthorized access attempts.</p>
                <hr>
                <p class="mb-0">
                    <a href="user-unlock.php?id=<?php echo $user_id; ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-unlock"></i> Unlock Account
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activity Log</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon bg-primary">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Account Created</h6>
                                <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($user['registration_date'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($user['last_login']) && $user['last_login'] != '0000-00-00 00:00:00'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-success">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Last Successful Login</h6>
                                <p class="text-muted mb-0"><?php echo date('F d, Y H:i', strtotime($user['last_login'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user['failed_login_attempts'] > 0 && !empty($user['last_login_attempt'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Failed Login Attempts</h6>
                                <p class="text-muted mb-0">
                                    <?php echo $user['failed_login_attempts']; ?> attempt(s), last on 
                                    <?php echo date('F d, Y H:i', strtotime($user['last_login_attempt'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon bg-info">
                                <i class="bi bi-arrow-clockwise"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Profile Updated</h6>
                                <p class="text-muted mb-0"><?php echo date('F d, Y', strtotime($user['updated_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php foreach ($security_events as $event): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon 
                                <?php 
                                    if (stripos($event['event_type'], 'login') !== false) echo 'bg-success';
                                    elseif (stripos($event['event_type'], 'fail') !== false) echo 'bg-warning';
                                    elseif (stripos($event['event_type'], 'unlock') !== false) echo 'bg-info';
                                    elseif (stripos($event['event_type'], 'lock') !== false) echo 'bg-danger';
                                    elseif (stripos($event['event_type'], 'password') !== false) echo 'bg-primary';
                                    else echo 'bg-secondary';
                                ?>">
                                <i class="bi 
                                <?php 
                                    if (stripos($event['event_type'], 'login') !== false) echo 'bi-box-arrow-in-right';
                                    elseif (stripos($event['event_type'], 'fail') !== false) echo 'bi-x-circle';
                                    elseif (stripos($event['event_type'], 'unlock') !== false) echo 'bi-unlock';
                                    elseif (stripos($event['event_type'], 'lock') !== false) echo 'bi-lock';
                                    elseif (stripos($event['event_type'], 'password') !== false) echo 'bi-key';
                                    else echo 'bi-activity';
                                ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1"><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></h6>
                                <p class="text-muted mb-0">
                                    <?php echo date('F d, Y H:i', strtotime($event['timestamp'])); ?>
                                    <?php if (!empty($event['ip_address']) && $event['ip_address'] != 'system'): ?>
                                        - IP: <?php echo htmlspecialchars($event['ip_address']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($event['event_details'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($event['event_details']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>? This action cannot be undone and will remove all associated data.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="users.php">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -40px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 5px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
}

.avatar-placeholder {
    width: 100px;
    height: 100px;
    background-color: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}
</style>

<?php
// Include admin footer
include 'admin-footer.php';
?>