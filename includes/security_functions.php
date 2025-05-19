<?php
/**
 * Security Functions for XSS and SQL Injection Protection
 */

/**
 * Sanitize output to prevent XSS
 * 
 * @param mixed $data The data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_output($data) {
    if (is_null($data)) {
        return '';
    }
    if (is_array($data)) {
        return array_map('sanitize_output', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input data to prevent SQL injection
 * (Additional layer beyond prepared statements)
 * 
 * @param mixed $data The data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    global $conn;
    
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    if (is_string($data)) {
        // First, trim the string
        $data = trim($data);
        
        // Apply mysqli_real_escape_string if connection exists
        if (isset($conn) && $conn instanceof mysqli) {
            $data = $conn->real_escape_string($data);
        }
    }
    
    return $data;
}

/**
 * Generate a secure CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if token is valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Set basic security headers (simple version)
 */
function set_basic_security_headers() {
    // X-XSS-Protection
    @header("X-XSS-Protection: 1; mode=block");
    
    // X-Frame-Options
    @header("X-Frame-Options: SAMEORIGIN");
    
    // X-Content-Type-Options
    @header("X-Content-Type-Options: nosniff");
    
    // Referrer-Policy
    @header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Set full security headers (comprehensive version)
 */
function set_security_headers() {
    // Content Security Policy
    @header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
    
    // X-XSS-Protection
    @header("X-XSS-Protection: 1; mode=block");
    
    // X-Frame-Options
    @header("X-Frame-Options: SAMEORIGIN");
    
    // X-Content-Type-Options
    @header("X-Content-Type-Options: nosniff");
    
    // Referrer-Policy
    @header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Rate limiting for login attempts using database
 * 
 * @param string $ip IP address
 * @param int $max_attempts Maximum attempts allowed
 * @param int $timeframe_minutes Timeframe in minutes
 * @return bool True if limit reached
 */
function check_login_attempts_db($ip, $max_attempts = 5, $timeframe_minutes = 2) {
    global $conn;
    
    // Check login attempts from database
    $stmt = $conn->prepare("SELECT COUNT(*) AS attempt_count FROM login_attempts 
                          WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->bind_param("si", $ip, $timeframe_minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt_data = $result->fetch_assoc();
    $stmt->close();
    
    return ($attempt_data['attempt_count'] >= $max_attempts);
}

/**
 * Log security event to database
 * 
 * @param string $event_type Type of event
 * @param string $event_details Details of the event
 * @param string $ip IP address
 */
function log_security_event($event_type, $event_details, $ip = null) {
    global $conn;
    
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    $stmt = $conn->prepare("INSERT INTO security_log (event_type, event_details, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $event_type, $event_details, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Simple version of check_login_attempts using session
 * (Fallback if database approach isn't working)
 * 
 * @param string $ip IP address
 * @return bool True if limit reached
 */
function check_login_attempts($ip) {
    $max_attempts = 5; // Maximum attempts allowed
    $lockout_time = 2 * 60; // 15 minutes in seconds
    
    // Initialize the attempts array if it doesn't exist
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = array();
    }
    
    // Clean up old attempts
    foreach ($_SESSION['login_attempts'] as $attempt_ip => $data) {
        if (time() - $data['time'] > $lockout_time) {
            unset($_SESSION['login_attempts'][$attempt_ip]);
        }
    }
    
    // Check if IP is already blocked
    if (isset($_SESSION['login_attempts'][$ip])) {
        $attempts = $_SESSION['login_attempts'][$ip]['attempts'];
        $last_time = $_SESSION['login_attempts'][$ip]['time'];
        
        if ($attempts >= $max_attempts && (time() - $last_time) < $lockout_time) {
            return true; // Limit reached
        }
    }
    
    return false; // Limit not reached
}

/**
 * Record a login attempt (session-based approach)
 * 
 * @param string $ip IP address
 */
function record_login_attempt($ip) {
    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = array(
            'attempts' => 1,
            'time' => time()
        );
    } else {
        $_SESSION['login_attempts'][$ip]['attempts']++;
        $_SESSION['login_attempts'][$ip]['time'] = time();
    }
}
?>