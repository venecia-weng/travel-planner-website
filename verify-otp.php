<?php
// Start session
session_start();

// Include database connection and security functions
require_once 'includes/db_connect.php';
require_once 'includes/security_functions.php';

// Set security headers
set_basic_security_headers();

// Set page title
$page_title = 'Verify OTP - RoundTours';

// Check if email is set in session
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit;
}

$email = $_SESSION['reset_email'];
$error = '';
$success = '';

// Anti-brute force mechanism - using database now
$ip = $_SERVER['REMOTE_ADDR'];

// Check for OTP attempts from database
$stmt = $conn->prepare("SELECT COUNT(*) AS attempt_count FROM login_attempts 
                      WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$attempt_data = $result->fetch_assoc();
$stmt->close();

$lockout = false;
if ($attempt_data['attempt_count'] >= 5) {
    $lockout = true;
    $error = 'Too many failed attempts. Please try again later or request a new code.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockout) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Combine OTP digits into a single string
        $otp = '';
        for ($i = 1; $i <= 6; $i++) {
            if (isset($_POST['otp' . $i])) {
                // Only allow digits
                $digit = preg_replace('/[^0-9]/', '', $_POST['otp' . $i]);
                $otp .= $digit;
            } else {
                $error = 'Please enter all digits of the OTP';
                break;
            }
        }
        
        // If full OTP is collected, verify it
        if (strlen($otp) === 6) {
            // Get current time
            $current_time = date('Y-m-d H:i:s');
            
            // Check if OTP is valid and not expired with prepared statement
            $stmt = $conn->prepare("SELECT user_id FROM password_reset_tokens 
                                  WHERE email = ? AND token = ? AND expires_at > ? AND used = 0");
            $stmt->bind_param("sss", $email, $otp, $current_time);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Set session variables for reset-password.php
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['otp_verified'] = true;
                
                // Log successful verification
                $event_type = 'otp_verified';
                $event_details = "OTP verified for email: " . $email;
                $stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $event_type, $event_details, $ip);
                $stmt->execute();
                $stmt->close();
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Redirect to reset password page
                header('Location: reset-password.php');
                exit;
            } else {
                // Record failed attempt
                $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
                $stmt->bind_param("ss", $ip, $email);
                $stmt->execute();
                $stmt->close();
                
                // Log failed verification
                $event_type = 'otp_verification_failed';
                $event_details = "OTP verification failed for email: " . $email;
                $stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $event_type, $event_details, $ip);
                $stmt->execute();
                $stmt->close();
                
                $error = 'Invalid or expired OTP. Please try again or request a new code.';
            }
            $stmt->close();
        } elseif (empty($error)) {
            $error = 'Please enter all 6 digits of the OTP';
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Include header
include 'header.php';
?>

<!-- Verify OTP Form Section -->
<section class="py-5 theme-bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card shadow-sm theme-border-radius">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fs-4 fw-bold text-center mb-4">Verify Your Email</h2>
                        <p class="text-center mb-4">We've sent a 6-digit code to your email <strong><?php echo sanitize_output($email); ?></strong>. Enter the code below to continue.</p>
                        
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
                        
                        <?php if (!$lockout): ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="otpForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- OTP Input -->
                            <div class="mb-4">
                                <label class="form-label">Enter 6-Digit Code</label>
                                <div class="d-flex justify-content-between gap-2">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-input" 
                                           name="otp<?php echo $i; ?>" id="otp<?php echo $i; ?>" 
                                           maxlength="1" inputmode="numeric" pattern="[0-9]" required
                                           autocomplete="one-time-code">
                                    <?php endfor; ?>
                                </div>
                                <div class="form-text text-center">The code will expire in 15 minutes</div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg theme-btn-primary">Verify & Continue</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">Didn't receive the code? <a href="forgot-password.php" class="theme-text-primary">Request a new one</a></p>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center mt-3">
                            <p>You've been temporarily locked out due to too many failed attempts.</p>
                            <p>Please wait 15 minutes before trying again or <a href="forgot-password.php" class="theme-text-primary">request a new code</a>.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript for OTP field behavior with additional security -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const otpInputs = document.querySelectorAll('.otp-input');
    
    // Focus on first input when page loads
    if (otpInputs.length > 0) {
        otpInputs[0].focus();
    }
    
    // Add event listeners to all OTP inputs
    otpInputs.forEach((input, index) => {
        // Move to next input after entering a digit
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Only allow numeric input
            if (!/^\d*$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input if available
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            
            // Submit form if all fields are filled and last field is entered
            if (index === otpInputs.length - 1 && value) {
                const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
                if (allFilled) {
                    // Add a small delay to prevent accidental submissions
                    setTimeout(() => {
                        document.getElementById('otpForm').submit();
                    }, 300);
                }
            }
        });
        
        // Handle backspace to go to previous input
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        // Select all text on focus
        input.addEventListener('focus', function(e) {
            e.target.select();
        });
        
        // Prevent non-numeric input
        input.addEventListener('keypress', function(e) {
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        // Prevent paste except for numeric content
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text');
            
            // If pasted data is a 6-digit number, distribute across inputs
            if (/^\d{6}$/.test(pasteData)) {
                otpInputs.forEach((input, i) => {
                    input.value = pasteData.charAt(i);
                });
                
                // Focus on last input
                otpInputs[otpInputs.length - 1].focus();
                
                // Submit form if all fields are filled
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 300);
            } else if (/^\d+$/.test(pasteData)) {
                // If just numbers but not 6 digits, fill what we can
                const digits = pasteData.split('');
                for (let i = 0; i < Math.min(digits.length, otpInputs.length - index); i++) {
                    otpInputs[index + i].value = digits[i];
                }
                
                // Focus on next empty input or last input
                const nextIndex = Math.min(index + digits.length, otpInputs.length - 1);
                otpInputs[nextIndex].focus();
            }
        });
    });
});
</script>

<?php
// Include footer
include 'footer.php';
?>