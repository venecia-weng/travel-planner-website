<?php
// Start session
session_start();

// Include database connection and security functions
require_once 'includes/db_connect.php';
require_once 'includes/security_functions.php';

// Set security headers
set_basic_security_headers();

// Set page title
$page_title = 'Register - RoundTours';

// Initialize variables for form fields
$first_name = '';
$last_name = '';
$username = '';
$email = '';
$phone_number = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    } else {
        // Get and sanitize form data
        $first_name = sanitize_input(trim($_POST['first_name']));
        $last_name = sanitize_input(trim($_POST['last_name']));
        $username = sanitize_input(trim($_POST['username']));
        $email = sanitize_input(trim($_POST['email']));
        $phone_number = sanitize_input(trim($_POST['phone_number']));
        $password = $_POST['password']; // Don't sanitize passwords
        $confirm_password = $_POST['confirm_password'];

        // Validate first name
        if (empty($first_name)) {
            $errors['first_name'] = 'First name is required';
        } elseif (strlen($first_name) > 50) {
            $errors['first_name'] = 'First name cannot exceed 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $first_name)) {
            $errors['first_name'] = 'First name contains invalid characters';
        }

        // Validate last name
        if (empty($last_name)) {
            $errors['last_name'] = 'Last name is required';
        } elseif (strlen($last_name) > 50) {
            $errors['last_name'] = 'Last name cannot exceed 50 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $last_name)) {
            $errors['last_name'] = 'Last name contains invalid characters';
        }

        // Validate username
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 4 || strlen($username) > 30) {
            $errors['username'] = 'Username must be between 4 and 30 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors['username'] = 'Username already taken. Please choose another.';
            }
            $stmt->close();
        }

        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (strlen($email) > 100) {
            $errors['email'] = 'Email cannot exceed 100 characters';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors['email'] = 'Email already registered. Please use a different email or login.';
            }
            $stmt->close();
        }

        // Validate phone number - more comprehensive validation
        if (empty($phone_number)) {
            $errors['phone_number'] = 'Phone number is required';
        } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone_number)) {
            $errors['phone_number'] = 'Please enter a valid phone number';
        }

        // Enhanced password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character';
        }

        // Validate password confirmation
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        // If no errors, insert user into database
        if (empty($errors)) {
            // Hash the password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Set current timestamp
            $created_at = date('Y-m-d H:i:s');
            
            // Prepare and execute insert statement
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, phone_number, password_hash, registration_date, updated_at, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssssssss", $first_name, $last_name, $username, $email, $phone_number, $password_hash, $created_at, $created_at);

            if ($stmt->execute()) {
                // Registration success - set session variables
                $_SESSION['registration_success'] = true;
                
                // Log successful registration (without sensitive data)
                error_log("New user registered: {$email}");
                
                // Redirect to login page
                header('Location: login.php');
                exit;
            } else {
                $errors['general'] = 'Registration failed. Please try again later.';
                // Log error (safely)
                error_log("Registration failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Include header
include 'header.php';
?>

<!-- Registration Form Section -->
<section class="py-5 theme-bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm theme-border-radius">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fs-4 fw-bold text-center mb-4">Create Your Account</h2>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo sanitize_output($errors['general']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- First Name -->
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                       id="first_name" name="first_name" value="<?php echo sanitize_output($first_name); ?>" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['first_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                       id="last_name" name="last_name" value="<?php echo sanitize_output($last_name); ?>" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['last_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                    id="username" name="username" value="<?php echo sanitize_output($username); ?>" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['username']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" value="<?php echo sanitize_output($email); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['email']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Phone Number -->
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control <?php echo isset($errors['phone_number']) ? 'is-invalid' : ''; ?>" 
                                       id="phone_number" name="phone_number" value="<?php echo sanitize_output($phone_number); ?>" required>
                                <?php if (isset($errors['phone_number'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['phone_number']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                        id="password" name="password" required autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['password']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="form-text">Password must include uppercase & lowercase letters, numbers, and special characters.</div>
                                <?php endif; ?>
                                
                                <!-- Password Requirements Checklist -->
                                <div class="password-requirements mt-2 small">
                                    <p class="mb-1">Password must contain:</p>
                                    <ul class="list-unstyled ps-3 mb-0">
                                        <li id="length-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 8 characters</li>
                                        <li id="uppercase-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 uppercase letter</li>
                                        <li id="lowercase-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 lowercase letter</li>
                                        <li id="number-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 number</li>
                                        <li id="special-check"><i class="bi bi-x-circle-fill text-danger"></i> At least 1 special character</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                        id="confirm_password" name="confirm_password" required autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo sanitize_output($errors['confirm_password']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg theme-btn-primary">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? <a href="login.php" class="theme-text-primary">Sign in</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle for password field
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle icon
                togglePasswordIcon.classList.toggle('bi-eye');
                togglePasswordIcon.classList.toggle('bi-eye-slash');
            });
        }
        
        // Toggle for confirm password field
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirm_password');
        const toggleConfirmPasswordIcon = document.getElementById('toggleConfirmPasswordIcon');
        
        if (toggleConfirmPassword) {
            toggleConfirmPassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                
                // Toggle icon
                toggleConfirmPasswordIcon.classList.toggle('bi-eye');
                toggleConfirmPasswordIcon.classList.toggle('bi-eye-slash');
            });
        }
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.createElement('div');
        strengthMeter.className = 'password-strength-meter mt-2';
        strengthMeter.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%;" id="password-strength-bar"></div>
            </div>
            <small class="text-muted mt-1" id="password-strength-text">Password strength</small>
        `;

        // Insert the strength meter after the password requirements
        const passwordRequirements = document.querySelector('.password-requirements');
        passwordRequirements.insertAdjacentElement('afterend', strengthMeter);

        // Password strength check function
        function checkPasswordStrength(password) {
            let strength = 0;
            const feedback = [];

            // Check length
            if (password.length < 8) {
                feedback.push('At least 8 characters');
            } else {
                strength += 25;
            }

            // Check for uppercase letters
            if (!password.match(/[A-Z]/)) {
                feedback.push('At least 1 uppercase letter');
            } else {
                strength += 25;
            }

            // Check for numbers
            if (!password.match(/[0-9]/)) {
                feedback.push('At least 1 number');
            } else {
                strength += 25;
            }

            // Check for special characters
            if (!password.match(/[^A-Za-z0-9]/)) {
                feedback.push('At least 1 special character');
            } else {
                strength += 25;
            }

            return {
                strength: strength,
                feedback: feedback
            };
        }

        // Update password requirement checklist as user types
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            updateRequirement('length-check', password.length >= 8);
            
            // Check uppercase
            updateRequirement('uppercase-check', /[A-Z]/.test(password));
            
            // Check lowercase
            updateRequirement('lowercase-check', /[a-z]/.test(password));
            
            // Check number
            updateRequirement('number-check', /[0-9]/.test(password));
            
            // Check special character
            updateRequirement('special-check', /[^A-Za-z0-9]/.test(password));
            
            // Update strength meter
            const result = checkPasswordStrength(password);
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            // Update progress bar
            strengthBar.style.width = `${result.strength}%`;
            
            // Update progress bar color
            if (result.strength <= 25) {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Weak password';
            } else if (result.strength <= 50) {
                strengthBar.className = 'progress-bar bg-warning';
                strengthText.textContent = 'Fair password';
            } else if (result.strength <= 75) {
                strengthBar.className = 'progress-bar bg-info';
                strengthText.textContent = 'Good password';
            } else {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.textContent = 'Strong password';
            }
        });

        function updateRequirement(id, isValid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (isValid) {
                icon.className = 'bi bi-check-circle-fill text-success';
            } else {
                icon.className = 'bi bi-x-circle-fill text-danger';
            }
        }
        
        // Add password match checking
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchText = document.createElement('small');
        passwordMatchText.className = 'text-muted mt-1';
        passwordMatchText.id = 'password-match-text';
        confirmPasswordInput.parentNode.parentNode.insertAdjacentElement('afterend', passwordMatchText);

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatchText.className = 'text-success mt-1';
                passwordMatchText.textContent = 'Passwords match';
            } else {
                passwordMatchText.className = 'text-danger mt-1';
                passwordMatchText.textContent = 'Passwords do not match';
            }
        }

        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submission
        document.querySelector('form').addEventListener('submit', function(event) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check all password requirements
            if (password.length < 8 || 
                !/[A-Z]/.test(password) || 
                !/[a-z]/.test(password) || 
                !/[0-9]/.test(password) || 
                !/[^A-Za-z0-9]/.test(password)) {
                
                event.preventDefault();
                alert('Please make sure your password meets all requirements.');
                return false;
            }
            
            // Check if passwords match
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
        });
    });
</script>

<?php
// Include footer
include 'footer.php';
?>