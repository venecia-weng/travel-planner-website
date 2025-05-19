<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include database connection and security functions
require_once '../includes/db_connect.php';
require_once '../includes/security_functions.php';

// Set page title
$page_title = 'Unlock User Account - RoundTours Admin';
$active_page = 'users';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php?error=No user specified');
    exit;
}

$user_id = $_GET['id'];
$success_message = '';
$error_message = '';

// Get user data
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, account_locked, failed_login_attempts FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Check if account is already unlocked
    if ($user['account_locked'] == 0 && $user['failed_login_attempts'] == 0) {
        $error_message = 'This account is already unlocked.';
    }
} else {
    header('Location: users.php?error=User not found');
    exit;
}
$stmt->close();

// Process form submission for unlocking account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account'])) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Reset failed login attempts and unlock account
        $stmt = $conn->prepare("UPDATE users SET account_locked = 0, failed_login_attempts = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            // Log the action in security_log
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['admin_id'];
            $event_details = "Admin (ID: {$admin_id}) unlocked account for user: {$user['email']} (ID: {$user_id})";
            
            $log_stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES ('account_unlock', ?, ?)");
            $log_stmt->bind_param("ss", $event_details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Account for {$user['first_name']} {$user['last_name']} has been successfully unlocked.";
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT account_locked, failed_login_attempts FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user_update = $result->fetch_assoc();
                $user['account_locked'] = $user_update['account_locked'];
                $user['failed_login_attempts'] = $user_update['failed_login_attempts'];
            }
            $stmt->close();
        } else {
            throw new Exception("Failed to unlock account.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Unlock User Account</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Unlock Account</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Account Status for <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-envelope me-2 text-primary"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Account Status</label>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>
                            <?php if ($user['account_locked'] == 1): ?>
                                <span class="badge bg-danger">Locked</span>
                            <?php else: ?>
                                <span class="badge bg-success">Unlocked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Failed Login Attempts</label>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2 text-primary"></i>
                            <span><?php echo htmlspecialchars($user['failed_login_attempts']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5 class="alert-heading">Information</h5>
                <p>User accounts are automatically locked after 5 failed login attempts to protect against brute force attacks. Unlocking an account will reset the failed login attempts counter to zero.</p>
                <hr>
                <p class="mb-0">Users can also unlock their own accounts by using the "Forgot Password" feature to reset their password.</p>
            </div>
            
            <?php if ($user['account_locked'] == 1 || $user['failed_login_attempts'] > 0): ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $user_id; ?>">
                    <div class="d-flex justify-content-between">
                        <a href="user-view.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Back to User Details</a>
                        <button type="submit" name="unlock_account" class="btn btn-primary">Unlock Account</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="d-flex justify-content-between">
                    <a href="user-view.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Back to User Details</a>
                    <button type="button" class="btn btn-primary" disabled>Account Already Unlocked</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include admin footer
include 'admin-footer.php';
?>