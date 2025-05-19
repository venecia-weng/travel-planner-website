<?php
// Start session
session_start();

// Include database connection and security functions
require_once 'includes/db_connect.php';
require_once 'includes/security_functions.php';

// Set security headers
set_basic_security_headers();

// Set page title
$page_title = 'Reset Password - RoundTours';

// Check if user is verified
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || !isset($_SESSION['reset_user_id'])) {
    header('Location: forgot-password.php');
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password
        if (empty($password)) {
            $error = 'Password is required';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least 1 uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least 1 lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least 1 number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = 'Password must contain at least 1 special character';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        }
        
        // If no errors, update password
        if (empty($error)) {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Since the password_history table was removed, we'll skip the history check
                // and directly update the password
                
                // Update password in database
                $updated_at = date('Y-m-d H:i:s');
                $last_password_change = $updated_at;
                
                $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = ?, last_password_change = ?, 
                                      failed_login_attempts = 0, account_locked = 0 WHERE user_id = ?");
                $stmt->bind_param("sssi", $password_hash, $updated_at, $last_password_change, $user_id);
                
                if ($stmt->execute()) {
                    // Mark all tokens for this user as used
                    $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log the password reset
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $event_type = 'password_reset';
                    $event_details = "Password reset for user ID: " . $user_id;
                    
                    $stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $event_type, $event_details, $ip);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Set success message and clear session variables
                    $_SESSION['password_reset_success'] = true;
                    unset($_SESSION['otp_verified']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_email']);
                    
                    // Regenerate session ID
                    session_regenerate_id(true);
                    
                    // Redirect to login page
                    header('Location: login.php');
                    exit;
                } else {
                    // Rollback transaction on error
                    $conn->rollback();
                    $error = 'Failed to update password. Please try again.';
                }
            } catch (Exception $e) {
                // Rollback transaction on exception
                $conn->rollback();
                $error = 'An error occurred. Please try again later.';
                error_log('Password reset error: ' . $e->getMessage());
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Include header
include 'header.php';
?>

<!-- Reset Password Form Section -->
<section class="py-5 theme-bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow-sm theme-border-radius">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fs-4 fw-bold text-center mb-4">Reset Your Password</h2>
                        <p class="text-center mb-4">Please enter your new password.</p>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo sanitize_output($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="resetPasswordForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                <div id="password-requirements" class="mt-2 small">
                                    <p class="mb-1">Password must contain:</p>
                                    <ul class="list-unstyled ps-3 mb-0">
                                        <li id="length-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 8 characters</li>
                                        <li id="uppercase-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 uppercase letter</li>
                                        <li id="lowercase-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 lowercase letter</li>
                                        <li id="number-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 number</li>
                                        <li id="special-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 special character</li>
                                    </ul>
                                </div>
                                <div class="mt-2">
                                    <p class="mb-1 small">Password strength</p>
                                    <div class="progress" style="height: 6px;">
                                        <div id="password-strength" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                    </button>
                                </div>
                                <div id="password-match" class="form-text"></div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg theme-btn-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    function setupPasswordToggle(passwordId, toggleId, iconId) {
        const passwordInput = document.getElementById(passwordId);
        const toggleButton = document.getElementById(toggleId);
        const toggleIcon = document.getElementById(iconId);
        
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                toggleIcon.classList.toggle('bi-eye');
                toggleIcon.classList.toggle('bi-eye-slash');
            });
        }
    }
    
    // Setup password toggles
    setupPasswordToggle('password', 'togglePassword', 'togglePasswordIcon');
    setupPasswordToggle('confirm_password', 'toggleConfirmPassword', 'toggleConfirmPasswordIcon');
    
    // Password strength validator
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatchDiv = document.getElementById('password-match');
    const submitButton = document.querySelector('button[type="submit"]');
    
    // Update requirement check
    function updateRequirement(id, isValid) {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');
        
        if (isValid) {
            icon.className = 'bi bi-check-circle-fill text-success';
        } else {
            icon.className = 'bi bi-x-circle-fill text-danger';
        }
    }
    
    // Check password strength
    function checkPasswordStrength(password) {
        let strength = 0;
        
        // Update requirements
        updateRequirement('length-check', password.length >= 8);
        updateRequirement('uppercase-check', /[A-Z]/.test(password));
        updateRequirement('lowercase-check', /[a-z]/.test(password));
        updateRequirement('number-check', /[0-9]/.test(password));
        updateRequirement('special-check', /[^A-Za-z0-9]/.test(password));
        
        // Calculate strength
        if (password.length >= 8) strength += 20;
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[a-z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;
        
        // Update strength meter
        const strengthBar = document.getElementById('password-strength');
        strengthBar.style.width = strength + '%';
        
        if (strength <= 40) {
            strengthBar.className = 'progress-bar bg-danger';
        } else if (strength <= 80) {
            strengthBar.className = 'progress-bar bg-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
        }
        
        return strength;
    }
    
    // Check password match
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            passwordMatchDiv.innerHTML = '';
            return false;
        }
        
        if (password === confirmPassword) {
            passwordMatchDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Passwords match</span>';
            return true;
        } else {
            passwordMatchDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Passwords do not match</span>';
            return false;
        }
    }
    
    // Add event listeners
    passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        if (confirmPasswordInput.value) {
            checkPasswordMatch();
        }
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    
    // Form validation
    document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
        const password = passwordInput.value;
        
        // Check all password requirements
        if (password.length < 8 || 
            !/[A-Z]/.test(password) || 
            !/[a-z]/.test(password) || 
            !/[0-9]/.test(password) || 
            !/[^A-Za-z0-9]/.test(password) ||
            !checkPasswordMatch()) {
            
            event.preventDefault();
            alert('Please make sure your password meets all requirements and passwords match.');
            return false;
        }
        
        return true;
    });
});
</script>

<?php
// Include footer
include 'footer.php';
?>