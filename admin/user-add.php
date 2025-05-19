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
$page_title = 'Add New User - RoundTours Admin';
$active_page = 'users';

// Initialize variables for form fields
$first_name = '';
$last_name = '';
$email = '';
$phone_number = '';
$is_admin = false; // Changed from numeric to boolean for clarity
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']);

    // Validate first name
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    }

    // Validate last name
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already registered. Please use a different email.';
        }
        $stmt->close();
    }

    // Validate phone number - basic validation
    if (empty($phone_number)) {
        $errors['phone_number'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,15}$/', $phone_number)) {
        $errors['phone_number'] = 'Please enter a valid phone number';
    }

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

    // If no errors, insert user into database
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Set role based on admin checkbox
        $role = $is_admin ? 'Admin' : 'User';
        
        // Generate a username based on first name and last name
        $username = strtolower($first_name . '_' . $last_name);
        $username = preg_replace('/[^a-z0-9_]/', '', $username); // Remove special characters
        
        // Prepare and execute insert statement
        // FIXED: Using role column instead of is_admin and adding registration_date
        $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, phone_number, password_hash, role, registration_date, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssssss", $username, $first_name, $last_name, $email, $phone_number, $password_hash, $role);

        if ($stmt->execute()) {
            // Redirect to users list with success message
            header('Location: users.php?success=User created successfully');
            exit;
        } else {
            $errors['general'] = 'Failed to create user. Please try again later. Error: ' . $conn->error;
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
                <h1>Add New User</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add User</li>
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
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
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
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                               id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
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
                    
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                               id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['password']; ?>
                            </div>
                        <?php else: ?>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                               id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['confirm_password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_admin" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">
                                Grant administrator privileges
                            </label>
                            <div class="form-text">Administrators can manage users, trips, bookings, and system settings.</div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include admin footer
include 'admin-footer.php';
?>