<?php
// Start the session
session_start();

// Include database connection (optional, but good practice)
require_once 'includes/db_connect.php';

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page with logout success parameter
header("Location: index.php?logout=success");
exit;
?>