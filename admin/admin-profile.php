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
$page_title = 'My Profile - RoundTours Admin';
$active_page = 'profile';

// Initialize variables
$user_id = $_SESSION['admin_id'];
$first_name = '';
$last_name = '';
$username = '';
$email = '';
$phone_number = '';
$profile_image = '';
$registration_date = '';
$last_login = '';
$role = '';
$success_message = '';
$error_message = '';
$change_password = false;

// Get admin data from database
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $username = $user['username'];
    $email = $user['email'];
    $phone_number = $user['phone_number'];
    $profile_image = $user['profile_image'] ?? '';
    $registration_date = $user['registration_date'];
    $last_login = $user['last_login'] ?? 'Never';
    $role = $user['role'];
} else {
    $error_message = 'Admin data not found';
}
$stmt->close();

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get and sanitize form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $phone_number = trim($_POST['phone_number']);
    
    // Validate input
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($phone_number)) {
        $errors[] = 'Phone number is required';
    }
    
    // Handle profile image upload if present
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (!in_array(strtolower($filetype), $allowed)) {
            $errors[] = 'Only JPG, JPEG, PNG, and GIF files are allowed';
        } else {
            // Generate unique filename
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
            $upload_path = '../uploads/profile_images/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/profile_images')) {
                mkdir('../uploads/profile_images', 0777, true);
            }
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $upload_path;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
    
    // If no errors, update user data in database
    if (empty($errors)) {
        $updated_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, phone_number = ?, profile_image = ?, updated_at = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $username, $phone_number, $profile_image, $updated_at, $user_id);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
            $success_message = 'Profile updated successfully';
        } else {
            $error_message = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Process form submission for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters long';
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = 'Password must contain at least 1 uppercase letter';
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = 'Password must contain at least 1 lowercase letter';
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = 'Password must contain at least 1 number';
    }
    
    // Check for at least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $errors[] = 'Password must contain at least 1 special character';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, verify current password and update
    if (empty($errors)) {
        // Get current password hash from database
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verify current password
        if (password_verify($current_password, $user['password_hash'])) {
            // Hash new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updated_at = date('Y-m-d H:i:s');
            
            // Update password in database
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $new_password_hash, $updated_at, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Password changed successfully';
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
            $stmt->close();
        } else {
            $error_message = 'Current password is incorrect';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Process form submission for deleting profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile_pic'])) {
    
    // Check if user actually has a profile image
    if (!empty($profile_image)) {
        // Delete the file from the server if it exists
        if (file_exists($profile_image)) {
            unlink($profile_image);
        }
        
        // Update the database to remove profile image reference
        $stmt = $conn->prepare("UPDATE users SET profile_image = NULL, updated_at = ? WHERE user_id = ?");
        $updated_at = date('Y-m-d H:i:s');
        $stmt->bind_param("si", $updated_at, $user_id);
        
        if ($stmt->execute()) {
            // Update the variable for this page load
            $profile_image = '';
            $success_message = 'Profile picture deleted successfully';
        } else {
            $error_message = 'Failed to delete profile picture. Please try again.';
        }
        $stmt->close();
    } else {
        $error_message = 'No profile picture to delete';
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1>My Profile</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Profile</li>
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
    
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Summary</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if (!empty($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="rounded-circle img-thumbnail mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle img-thumbnail d-flex align-items-center justify-content-center bg-primary mb-3 mx-auto" style="width: 120px; height: 120px;">
                                <i class="bi bi-person-fill text-white" style="font-size: 60px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h4><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h4>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge bg-danger"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                    
                    <div class="user-details">
                        <div class="mb-3">
                            <label class="form-label text-muted">Username</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person me-2 text-primary"></i>
                                <span><?php echo htmlspecialchars($username); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone me-2 text-primary"></i>
                                <span><?php echo htmlspecialchars($phone_number); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Member Since</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check me-2 text-primary"></i>
                                <span><?php echo date('F d, Y', strtotime($registration_date)); ?></span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="form-label text-muted">Last Login</label>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock-history me-2 text-primary"></i>
                                <span><?php echo $last_login !== 'Never' ? date('F d, Y', strtotime($last_login)) : 'Never'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="profile_image" class="form-label">Profile Picture</label>
                            <div class="d-flex justify-content-center mb-2">
                                <?php if (!empty($profile_image)): ?>
                                    <div>
                                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Current Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex">
                                <div class="flex-grow-1 me-2">
                                    <input type="file" class="form-control" id="profile_image" name="profile_image">
                                </div>
                                <?php if (!empty($profile_image)): ?>
                                    <button type="submit" name="delete_profile_pic" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your profile picture?')">
                                        <i class="bi bi-trash"></i> Delete Picture
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Upload a profile picture (JPG, PNG, GIF)</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly disabled>
                                    <div class="form-text">Email cannot be changed</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Account Role</label>
                                    <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($role); ?>" readonly disabled>
                                    <div class="form-text">Role cannot be changed</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="password-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           required oninput="validatePassword()">
                                    <div id="password-requirements" class="mt-2">
                                        <p class="mb-1">Password must contain:</p>
                                        <ul class="list-unstyled mb-0">
                                            <li id="length-check" class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i> At least 8 characters
                                            </li>
                                            <li id="uppercase-check" class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i> At least 1 uppercase letter
                                            </li>
                                            <li id="lowercase-check" class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i> At least 1 lowercase letter
                                            </li>
                                            <li id="number-check" class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i> At least 1 number
                                            </li>
                                            <li id="special-check" class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i> At least 1 special character
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="mt-2">
                                        <p class="mb-1">Password strength</p>
                                        <div class="progress">
                                            <div id="password-strength" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required oninput="checkPasswordMatch()">
                                    <div id="password-match" class="form-text"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="change_password" id="change-password-btn" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>

                    <script>
                        function validatePassword() {
                            const password = document.getElementById('new_password').value;
                            let strength = 0;
                            
                            // Check length
                            const lengthCheck = document.getElementById('length-check');
                            if (password.length >= 8) {
                                lengthCheck.classList.remove('text-danger');
                                lengthCheck.classList.add('text-success');
                                lengthCheck.innerHTML = '<i class="bi bi-check-circle me-1"></i> At least 8 characters';
                                strength += 20;
                            } else {
                                lengthCheck.classList.remove('text-success');
                                lengthCheck.classList.add('text-danger');
                                lengthCheck.innerHTML = '<i class="bi bi-x-circle me-1"></i> At least 8 characters';
                            }
                            
                            // Check uppercase
                            const uppercaseCheck = document.getElementById('uppercase-check');
                            if (/[A-Z]/.test(password)) {
                                uppercaseCheck.classList.remove('text-danger');
                                uppercaseCheck.classList.add('text-success');
                                uppercaseCheck.innerHTML = '<i class="bi bi-check-circle me-1"></i> At least 1 uppercase letter';
                                strength += 20;
                            } else {
                                uppercaseCheck.classList.remove('text-success');
                                uppercaseCheck.classList.add('text-danger');
                                uppercaseCheck.innerHTML = '<i class="bi bi-x-circle me-1"></i> At least 1 uppercase letter';
                            }
                            
                            // Check lowercase
                            const lowercaseCheck = document.getElementById('lowercase-check');
                            if (/[a-z]/.test(password)) {
                                lowercaseCheck.classList.remove('text-danger');
                                lowercaseCheck.classList.add('text-success');
                                lowercaseCheck.innerHTML = '<i class="bi bi-check-circle me-1"></i> At least 1 lowercase letter';
                                strength += 20;
                            } else {
                                lowercaseCheck.classList.remove('text-success');
                                lowercaseCheck.classList.add('text-danger');
                                lowercaseCheck.innerHTML = '<i class="bi bi-x-circle me-1"></i> At least 1 lowercase letter';
                            }
                            
                            // Check number
                            const numberCheck = document.getElementById('number-check');
                            if (/[0-9]/.test(password)) {
                                numberCheck.classList.remove('text-danger');
                                numberCheck.classList.add('text-success');
                                numberCheck.innerHTML = '<i class="bi bi-check-circle me-1"></i> At least 1 number';
                                strength += 20;
                            } else {
                                numberCheck.classList.remove('text-success');
                                numberCheck.classList.add('text-danger');
                                numberCheck.innerHTML = '<i class="bi bi-x-circle me-1"></i> At least 1 number';
                            }
                            
                            // Check special character
                            const specialCheck = document.getElementById('special-check');
                            if (/[^A-Za-z0-9]/.test(password)) {
                                specialCheck.classList.remove('text-danger');
                                specialCheck.classList.add('text-success');
                                specialCheck.innerHTML = '<i class="bi bi-check-circle me-1"></i> At least 1 special character';
                                strength += 20;
                            } else {
                                specialCheck.classList.remove('text-success');
                                specialCheck.classList.add('text-danger');
                                specialCheck.innerHTML = '<i class="bi bi-x-circle me-1"></i> At least 1 special character';
                            }
                            
                            // Update strength bar
                            const strengthBar = document.getElementById('password-strength');
                            strengthBar.style.width = strength + '%';
                            
                            if (strength <= 40) {
                                strengthBar.className = 'progress-bar bg-danger';
                            } else if (strength <= 80) {
                                strengthBar.className = 'progress-bar bg-warning';
                            } else {
                                strengthBar.className = 'progress-bar bg-success';
                            }
                            
                            checkPasswordMatch();
                        }
                        
                        function checkPasswordMatch() {
                            const password = document.getElementById('new_password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;
                            const matchMessage = document.getElementById('password-match');
                            
                            if (confirmPassword === '') {
                                matchMessage.innerHTML = '';
                                return;
                            }
                            
                            if (password === confirmPassword) {
                                matchMessage.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Passwords match</span>';
                            } else {
                                matchMessage.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Passwords do not match</span>';
                            }
                        }
                        
                        // Form submission validation
                        document.getElementById('password-form').addEventListener('submit', function(event) {
                            const password = document.getElementById('new_password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;
                            
                            if (password !== confirmPassword) {
                                event.preventDefault();
                                alert('Passwords do not match');
                                return false;
                            }
                            
                            // Check all password requirements
                            if (password.length < 8 || 
                                !/[A-Z]/.test(password) || 
                                !/[a-z]/.test(password) || 
                                !/[0-9]/.test(password) || 
                                !/[^A-Za-z0-9]/.test(password)) {
                                event.preventDefault();
                                alert('Your password does not meet all requirements');
                                return false;
                            }
                            
                            return true;
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include admin footer
include 'admin-footer.php';
?>