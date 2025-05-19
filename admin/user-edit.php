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
$page_title = 'Edit User - RoundTours Admin';
$active_page = 'users';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php?error=No user specified');
    exit;
}

$user_id = $_GET['id'];

// Initialize variables for form fields
$first_name = '';
$last_name = '';
$email = '';
$phone_number = '';
$role = '';
$is_admin = false; // Used for checkbox state
$errors = [];
$change_password = false;

// Get user data
// FIXED: Changed is_admin to role
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
    $phone_number = $user['phone_number'];
    $role = $user['role'];
    $is_admin = (strtolower($role) == 'admin'); // FIXED: Case-insensitive check
} else {
    header('Location: users.php?error=User not found');
    exit;
}
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $is_admin = isset($_POST['is_admin']);
    $change_password = isset($_POST['change_password']);
    
    // Validate first name
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    }
    
    // Validate last name
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    // Validate phone number - basic validation
    if (empty($phone_number)) {
        $errors['phone_number'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,15}$/', $phone_number)) {
        $errors['phone_number'] = 'Please enter a valid phone number';
    }
    
    // Handle password change if requested
    if ($change_password) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }
        
        // Validate password confirmation
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // If no errors, update user in database
    if (empty($errors)) {
        // Set current timestamp
        $updated_at = date('Y-m-d H:i:s');
        
        // Set role based on admin checkbox
        $role = $is_admin ? 'Admin' : 'User';
        
        if ($change_password) {
            // Hash the new password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user with new password
            // FIXED: Changed is_admin to role
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, password_hash = ?, role = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $first_name, $last_name, $phone_number, $password_hash, $role, $updated_at, $user_id);
        } else {
            // Update user without changing password
            // FIXED: Changed is_admin to role
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, role = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $phone_number, $role, $updated_at, $user_id);
        }
        
        if ($stmt->execute()) {
            // Redirect to users list with success message
            header('Location: users.php?success=User updated successfully');
            exit;
        } else {
            $errors['general'] = 'Failed to update user. Please try again later. Error: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>Edit User</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $user_id; ?>" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                               id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['first_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                               id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['last_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled readonly>
                        <div class="form-text">Email address cannot be changed</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control <?php echo isset($errors['phone_number']) ? 'is-invalid' : ''; ?>" 
                               id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                        <?php if (isset($errors['phone_number'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['phone_number']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_admin" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">
                                Administrator privileges
                            </label>
                            <div class="form-text">Administrators can manage users, trips, bookings, and system settings.</div>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="change_password" name="change_password" <?php echo $change_password ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="change_password">
                                Change password
                            </label>
                        </div>
                    </div>
                    
                    <div id="passwordFields" class="col-12 <?php echo $change_password ? '' : 'd-none'; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                    id="password" name="password">
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['password']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                    id="confirm_password" name="confirm_password">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['confirm_password']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for password fields toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordFields = document.getElementById('passwordFields');
    
    changePasswordCheckbox.addEventListener('change', function() {
        if (this.checked) {
            passwordFields.classList.remove('d-none');
        } else {
            passwordFields.classList.add('d-none');
        }
    });
});
</script>

<?php
// Include admin footer
include 'admin-footer.php';
?>