<?php
// Start session
session_start();

// Include database connection and security functions
require_once 'includes/db_connect.php';
require_once 'includes/security_functions.php';

// Set security headers
set_basic_security_headers();

// Set page title
$page_title = 'Forgot Password - RoundTours';

// Initialize variables
$email = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get and sanitize email
        $email = sanitize_input(trim($_POST['email']));
        
        // Validate email
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Rate limiting for password reset attempts
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Check login attempts from database
            $stmt = $conn->prepare("SELECT COUNT(*) AS attempt_count FROM login_attempts 
                                   WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->bind_param("s", $ip);
            $stmt->execute();
            $result = $stmt->get_result();
            $attempt_data = $result->fetch_assoc();
            $stmt->close();
            
            if ($attempt_data['attempt_count'] >= 3) {
                $error = 'Too many requests. Please try again later.';
            } else {
                // Record attempt regardless of whether email exists
                $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
                $stmt->bind_param("ss", $ip, $email);
                $stmt->execute();
                $stmt->close();
                
                // Check if email exists in database
                $stmt = $conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Generate a secure OTP (6-digit number)
                    $otp = sprintf("%06d", mt_rand(0, 999999));
                    
                    // Set OTP expiry time (15 minutes)
                    $expiry_minutes = 15;
                    
                    // Use the CreatePasswordResetToken stored procedure
                    $stmt = $conn->prepare("CALL CreatePasswordResetToken(?, ?, ?)");
                    $stmt->bind_param("ssi", $email, $otp, $expiry_minutes);
                    
                    if ($stmt->execute()) {
                        // Send OTP to user's email
                        $success = sendOTPEmail($email, $otp, $user['first_name']);
                        
                        // Set session variables for reset-password.php
                        $_SESSION['reset_email'] = $email;
                        
                        // Log the event
                        $stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES (?, ?, ?)");
                        $event_type = 'password_reset_request';
                        $event_details = "Password reset requested for email: " . $email;
                        $stmt->bind_param("sss", $event_type, $event_details, $ip);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Redirect to OTP verification page
                        header('Location: verify-otp.php');
                        exit;
                    } else {
                        $error = 'Failed to process your request. Please try again later.';
                    }
                    $stmt->close();
                } else {
                    // Don't reveal that email doesn't exist for security reasons
                    // But still set a timer before redirecting to simulate processing
                    sleep(1); // Add a small delay to prevent timing attacks
                    $success = 'If your email address exists in our database, you will receive a password recovery link at your email address shortly.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

/**
 * Send OTP email to user with improved email security
 * 
 * @param string $to_email Email address to send OTP to
 * @param string $otp The OTP code
 * @param string $first_name User's first name
 * @return string Success or error message
 */
function sendOTPEmail($to_email, $otp, $first_name) {
    // Include PHPMailer classes
    require 'includes/Exception.php';
    require 'includes/PHPMailer.php';
    require 'includes/SMTP.php';
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'roundtours319@gmail.com'; // SMTP username
        $mail->Password   = 'fexkggwzdguagoox'; // SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('roundtours319@gmail.com', 'RoundTours');
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'RoundTours - Password Reset OTP';
        
        // Sanitize first name for email
        $safe_first_name = htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8');
        
        // Email body - keep your existing HTML message but sanitize inputs
        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #00a1ff; padding: 20px; color: white; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .otp-code { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; letter-spacing: 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Password Reset Request</h2>
                </div>
                <div class="content">
                    <p>Hello ' . $safe_first_name . ',</p>
                    <p>We received a request to reset your RoundTours account password. Enter the following OTP code to reset your password:</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p>This code is valid for 15 minutes and can only be used once.</p>
                    <p>If you did not request a password reset, you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' RoundTours. All rights reserved.</p>
                    <p>This is an automated email, please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->send();
        return 'If your email address exists in our database, you will receive a password recovery code at your email address shortly.';
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return 'Failed to send recovery email. Please try again later.';
    }
}

// Include header
include 'header.php';
?>

<!-- Forgot Password Form Section -->
<section class="py-5 theme-bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow-sm theme-border-radius">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fs-4 fw-bold text-center mb-4">Forgot Your Password?</h2>
                        <p class="text-center mb-4">Enter your email address below and we'll send you an OTP code to reset your password.</p>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo sanitize_output($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo sanitize_output($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo sanitize_output($email); ?>" required autofocus>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg theme-btn-primary">Send Recovery Code</button>
                            </div>
                            
                            <div class="text-center">
                                <a href="login.php" class="theme-text-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'footer.php';
?>