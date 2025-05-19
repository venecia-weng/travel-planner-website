<?php
// Include configuration and database connection
require_once 'includes/db_connect.php';

// Start session
session_start();

// Set page title
$page_title = 'Admin Login - RoundTours';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

// Initialize variables
$email = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Prepare and execute select statement
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if user is an admin - using case-insensitive comparison
            if (strtolower($user['role']) === 'admin') {
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Password is correct, set session variables
                    $_SESSION['admin_id'] = $user['user_id'];
                    $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['is_admin'] = true;
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Redirect to admin dashboard
                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                $error = 'You do not have administrator privileges';
            }
        } else {
            $error = 'Invalid email or password';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding-top: 100px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card shadow border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Sign In as Admin</h2>
                    <p class="text-muted">Welcome back! Please enter your details</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-envelope text-primary"></i>
                            </span>
                            <input type="email" class="form-control border-start-0 bg-light" 
                                   id="email" name="email" placeholder="name@example.com"
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   required autofocus>
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
                                   id="password" name="password" required>
                            <button class="input-group-text bg-light border-start-0" 
                                    type="button" 
                                    id="togglePassword" 
                                    aria-label="Toggle password visibility"
                                    style="cursor: pointer">
                                <i class="bi bi-eye text-muted" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg py-2">Sign In</button>
                    </div>
                    
                    <div class="text-center">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Website
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (togglePassword && password && toggleIcon) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                toggleIcon.classList.toggle('bi-eye');
                toggleIcon.classList.toggle('bi-eye-slash');
            });
        }
    });
    </script>
</body>
</html>