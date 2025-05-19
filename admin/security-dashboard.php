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
$page_title = 'Security Dashboard - RoundTours Admin';
$active_page = 'security';

// Get security logs
$security_logs = [];
$logs_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

// Filter options
$event_type = isset($_GET['event_type']) ? $_GET['event_type'] : '';
$ip_address = isset($_GET['ip_address']) ? $_GET['ip_address'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the WHERE clause for filtering
$where_clauses = [];
$params = [];
$types = '';

if (!empty($event_type)) {
    $where_clauses[] = "event_type = ?";
    $params[] = $event_type;
    $types .= 's';
}

if (!empty($ip_address)) {
    $where_clauses[] = "ip_address = ?";
    $params[] = $ip_address;
    $types .= 's';
}

if (!empty($date_from)) {
    $where_clauses[] = "timestamp >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "timestamp <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Count total logs for pagination
$count_sql = "SELECT COUNT(*) as total FROM security_log" . $where_sql;
if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_logs = $row['total'];
    $stmt->close();
} else {
    $result = $conn->query($count_sql);
    $row = $result->fetch_assoc();
    $total_logs = $row['total'];
}

$total_pages = ceil($total_logs / $logs_per_page);

// Get security logs with pagination and filters
$sql = "SELECT * FROM security_log" . $where_sql . " ORDER BY timestamp DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $offset;
$params[] = $logs_per_page;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $security_logs[] = $row;
}
$stmt->close();

// Get unique event types for filtering
$event_types = [];
$result = $conn->query("SELECT DISTINCT event_type FROM security_log ORDER BY event_type ASC");
while ($row = $result->fetch_assoc()) {
    $event_types[] = $row['event_type'];
}

// Get unique IP addresses for filtering
$ip_addresses = [];
$result = $conn->query("SELECT DISTINCT ip_address FROM security_log ORDER BY ip_address ASC");
while ($row = $result->fetch_assoc()) {
    $ip_addresses[] = $row['ip_address'];
}

// Get locked accounts count
$locked_accounts = 0;
$result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE account_locked = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $locked_accounts = $row['count'];
}

// Get failed login attempts in the last 24 hours
$recent_failed_logins = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM login_attempts WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $recent_failed_logins = $row['count'];
}
$stmt->close();

// Get password policy
$password_policy = [];
$result = $conn->query("SELECT * FROM password_policy LIMIT 1");
if ($result && $result->num_rows > 0) {
    $password_policy = $result->fetch_assoc();
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Security Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Security</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Security Overview Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Locked Accounts</h5>
                            <h2 class="mt-2 mb-0"><?php echo $locked_accounts; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-danger-subtle">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <?php if ($locked_accounts > 0): ?>
                            <a href="users.php?search=locked" class="text-decoration-none">View locked accounts <i class="bi bi-arrow-right"></i></a>
                        <?php else: ?>
                            No locked accounts
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Failed Logins (24h)</h5>
                            <h2 class="mt-2 mb-0"><?php echo $recent_failed_logins; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-warning-subtle">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#failedLoginsModal">
                            View details <i class="bi bi-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Password Policy</h5>
                            <h2 class="mt-2 mb-0"><?php echo (!empty($password_policy)) ? $password_policy['min_length'] . ' chars' : 'Not Set'; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-primary-subtle">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#passwordPolicyModal">
                            View policy <i class="bi bi-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted mb-0">Security Logs</h5>
                            <h2 class="mt-2 mb-0"><?php echo $total_logs; ?></h2>
                        </div>
                        <div class="dashboard-icon bg-info-subtle">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                    <p class="card-text mt-3 mb-0">
                        <a href="#security-logs" class="text-decoration-none">
                            View logs <i class="bi bi-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Security Logs Section -->
    <div class="card mb-4" id="security-logs">
        <div class="card-header">
            <h5 class="card-title mb-0">Security Activity Logs</h5>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form class="mb-4" action="security-dashboard.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="event_type" name="event_type">
                            <option value="">All Events</option>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($event_type === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <select class="form-select" id="ip_address" name="ip_address">
                            <option value="">All IP Addresses</option>
                            <?php foreach ($ip_addresses as $ip): ?>
                                <option value="<?php echo htmlspecialchars($ip); ?>" <?php echo ($ip_address === $ip) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ip); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <a href="security-dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Logs Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>Event Type</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($security_logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No security logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($security_logs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        if (strpos($log['event_type'], 'failed') !== false) {
                                            $badge_class = 'bg-danger';
                                        } elseif (strpos($log['event_type'], 'success') !== false) {
                                            $badge_class = 'bg-success';
                                        } elseif (strpos($log['event_type'], 'unlock') !== false) {
                                            $badge_class = 'bg-warning';
                                        } elseif (strpos($log['event_type'], 'login') !== false) {
                                            $badge_class = 'bg-primary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['event_type']))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['event_details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Security logs pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($event_type) ? '&event_type=' . urlencode($event_type) : ''; ?><?php echo !empty($ip_address) ? '&ip_address=' . urlencode($ip_address) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($event_type) ? '&event_type=' . urlencode($event_type) : ''; ?><?php echo !empty($ip_address) ? '&ip_address=' . urlencode($ip_address) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php elseif (($i == 2 && $page > 4) || ($i == $total_pages - 1 && $page < $total_pages - 3)): ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">...</a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($event_type) ? '&event_type=' . urlencode($event_type) : ''; ?><?php echo !empty($ip_address) ? '&ip_address=' . urlencode($ip_address) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Failed Logins Modal -->
<div class="modal fade" id="failedLoginsModal" tabindex="-1" aria-labelledby="failedLoginsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="failedLoginsModalLabel">Failed Login Attempts (Last 24 Hours)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Username</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM login_attempts 
                                                    WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                                                    ORDER BY attempt_time DESC LIMIT 50");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No failed login attempts in the last 24 hours</td>
                                </tr>
                            <?php else: 
                                while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['attempt_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['username'] ?? 'Not provided'); ?></td>
                                        <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                    </tr>
                                <?php endwhile; 
                            endif;
                            $stmt->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Policy Modal -->
<div class="modal fade" id="passwordPolicyModal" tabindex="-1" aria-labelledby="passwordPolicyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordPolicyModalLabel">Password Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($password_policy)): ?>
                    <div class="alert alert-warning">
                        No password policy has been set up yet.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <div class="list-group-item">
                            <h6 class="mb-1">Minimum Length</h6>
                            <p class="mb-0"><?php echo $password_policy['min_length']; ?> characters</p>
                        </div>
                        <div class="list-group-item">
                            <h6 class="mb-1">Require Uppercase</h6>
                            <p class="mb-0"><?php echo $password_policy['require_uppercase'] ? 'Yes' : 'No'; ?></p>
                        </div>
                        <div class="list-group-item">
                            <h6 class="mb-1">Require Lowercase</h6>
                            <p class="mb-0"><?php echo $password_policy['require_lowercase'] ? 'Yes' : 'No'; ?></p>
                        </div>
                        <div class="list-group-item">
                            <h6 class="mb-1">Require Number</h6>
                            <p class="mb-0"><?php echo $password_policy['require_number'] ? 'Yes' : 'No'; ?></p>
                        </div>
                        <div class="list-group-item">
                            <h6 class="mb-1">Require Special Character</h6>
                            <p class="mb-0"><?php echo $password_policy['require_special'] ? 'Yes' : 'No'; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <!-- You could add an edit button here that takes the admin to a policy edit page -->
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
</style>

<?php
// Include admin footer
include 'admin-footer.php';
?>