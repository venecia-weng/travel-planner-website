<?php
/**
 * Currency Switcher Endpoint
 * 
 * This file handles requests to change the selected currency.
 * It accepts the 'currency' parameter via GET request.
 */

session_start();

// Check if currency parameter exists
if (isset($_GET['currency'])) {
    $currency = strtoupper($_GET['currency']);
    
    // Define allowed currencies
    $validCurrencies = ['SGD', 'USD', 'EUR', 'THB'];
    
    // Validate the requested currency
    if (in_array($currency, $validCurrencies)) {
        // Set the currency in session
        $_SESSION['currency'] = $currency;
    } else {
        // Invalid currency
        echo 'Invalid currency';
        exit();
    }
}

// If it's an AJAX request, don't redirect
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Return success status for AJAX requests
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'currency' => $_SESSION['currency']]);
    exit();
}

// For non-AJAX requests, redirect back to the referring page
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $referer);
exit();
?>