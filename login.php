<?php
// Start session
session_start();

// Include database connection and security functions
require_once 'includes/db_connect.php';
require_once 'includes/security_functions.php';

// Set security headers
set_basic_security_headers();

// Set page title
$page_title = 'Login - RoundTours';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$email = '';
$error = '';
$success_message = '';

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success_message = 'Registration successful! Please login with your credentials.';
    unset($_SESSION['registration_success']);
}

// Check for password reset success message
if (isset($_SESSION['password_reset_success'])) {
    $success_message = 'Your password has been reset successfully. Please login with your new password.';
    unset($_SESSION['password_reset_success']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get and sanitize form data
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password']; // Don't sanitize passwords

        // Get IP address for rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = sanitize_input($_SERVER['HTTP_USER_AGENT']);

        // Check login attempts from database
        $stmt = $conn->prepare("SELECT COUNT(*) AS attempt_count FROM login_attempts 
                              WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempt_data = $result->fetch_assoc();
        $stmt->close();

        if ($attempt_data['attempt_count'] >= 5) {
            $error = 'Too many login attempts. Please try again later.';
        } else {
            // Validate inputs
            if (empty($email) || empty($password)) {
                $error = 'Please enter both email and password';
            } else {
                // Prepare and execute select statement
                $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password_hash, role, account_locked FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Check if account is locked
                    if ($user['account_locked'] == 1) {
                        $error = 'Your account is locked. Please contact support or try the forgot password option.';
                        
                        // Log the failed attempt
                        $stmt = $conn->prepare("CALL TrackFailedLogin(?, ?)");
                        $stmt->bind_param("ss", $email, $ip);
                        $stmt->execute();
                        $stmt->close();
                    } 
                    // Verify password
                    else if (password_verify($password, $user['password_hash'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['role'] = $user['role'];
                        
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Use stored procedure to track successful login
                        $stmt = $conn->prepare("CALL TrackSuccessfulLogin(?, ?, ?)");
                        $stmt->bind_param("iss", $user['user_id'], $ip, $user_agent);
                        $stmt->execute();
                        $stmt->close();

                        // Redirect to homepage or dashboard
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Invalid email or password';
                        
                        // Use stored procedure to track failed login
                        $stmt = $conn->prepare("CALL TrackFailedLogin(?, ?)");
                        $stmt->bind_param("ss", $email, $ip);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    $error = 'Invalid email or password';
                    
                    // Track failed login attempt for non-existent user
                    $stmt = $conn->prepare("CALL TrackFailedLogin(?, ?)");
                    $stmt->bind_param("ss", $email, $ip);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

// Generate a new CSRF token
$csrf_token = generate_csrf_token();

// Include header
include 'header.php';
?>

<!-- Login Form Section -->
<section class="py-5 theme-bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Sign In to Your Account</h2>
                            <p class="text-muted">Welcome back! Please enter your details</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <?php echo sanitize_output($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo sanitize_output($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-envelope text-primary"></i>
                                    </span>
                                    <input type="email" class="form-control border-start-0 bg-light" 
                                           id="email" name="email" placeholder="name@example.com"
                                           value="<?php echo sanitize_output($email); ?>" required autofocus>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label fw-medium">Password</label>
                                    <a href="forgot-password.php" class="text-decoration-none small text-primary">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0 border-end-0 bg-light" 
                                           id="password" name="password" required autocomplete="current-password">
                                    <button class="input-group-text bg-light border-start-0" type="button" id="togglePassword" style="cursor: pointer">
                                        <i class="bi bi-eye text-muted" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg py-2">Sign In</button>
                            </div>
                        </form>

                        <div class="text-center">
                            <p class="mb-2">Don't have an account? <a href="register.php" class="text-decoration-none fw-medium text-primary">Register</a></p>
                            <p class="mb-0">Sign in as employee? <a href="admin-login.php" class="text-decoration-none fw-medium text-primary">Employee Sign-in</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
});
</script>

<?php
// Include footer
include 'footer.php';
?>