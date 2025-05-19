<?php
/**
 * Currency Conversion Endpoint
 * 
 * This file handles AJAX requests to convert currency amounts.
 * It accepts POST parameters:
 * - amount: The amount to convert
 * - fromCurrency: The source currency code
 * - currency: The target currency code
 */

session_start();
require_once 'currency_functions.php';

// Set response headers for AJAX
header('Content-Type: text/plain');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get parameters from POST
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $fromCurrency = isset($_POST['fromCurrency']) ? $_POST['fromCurrency'] : 'SGD';
    
    // Default to session currency if not provided
    $toCurrency = isset($_POST['currency']) ? $_POST['currency'] : getCurrentCurrency();
    
    // Store selected currency in session
    $_SESSION['currency'] = $toCurrency;
    
    // Perform conversion
    echo displayConvertedPrice($amount, $fromCurrency, $toCurrency);
    exit;
}

// If not a POST request, return an error
http_response_code(400);
echo "Error: Invalid request method. This endpoint requires POST.";