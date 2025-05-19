<?php
/**
 * Enhanced currency_functions.php
 * Provides improved currency handling with fallback mechanisms and caching
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get the user's currently selected currency
 * 
 * @return string Currency code (e.g., 'SGD', 'USD')
 */
function getCurrentCurrency() {
    // Default currency if none is set
    $default_currency = 'SGD';
    
    if (!isset($_SESSION['currency'])) {
        return $default_currency;
    }
    
    return $_SESSION['currency'];
}

/**
 * Fetch exchange rate from API with caching
 * 
 * @param string $fromCurrency The source currency code
 * @param string $toCurrency The target currency code
 * @return float|string Exchange rate or error message
 */
function fetchExchangeRate($fromCurrency, $toCurrency) {
    // If currencies are the same, return 1
    if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
        return 1;
    }
    
    // Check if we have a cached rate that's less than 1 hour old
    $cache_key = "exchange_rate_{$fromCurrency}_{$toCurrency}";
    if (isset($_SESSION[$cache_key]) && isset($_SESSION["{$cache_key}_time"])) {
        $cache_age = time() - $_SESSION["{$cache_key}_time"];
        if ($cache_age < 3600) { // 1 hour cache
            return $_SESSION[$cache_key];
        }
    }
    
    // API key - replace with your actual key
    $apiKey = '457bc5227c6ac3421500cd5b';
    $fromCurrency = strtoupper(trim($fromCurrency));
    $toCurrency = strtoupper(trim($toCurrency));

    // Primary API endpoint
    $url = "https://v6.exchangerate-api.com/v6/$apiKey/latest/$fromCurrency";
    
    // Attempt to fetch rates
    $response_json = @file_get_contents($url);
    
    if ($response_json !== false) {
        try {
            $response = json_decode($response_json);
            
            if ('success' === $response->result) {
                if (isset($response->conversion_rates->$toCurrency)) {
                    $rate = $response->conversion_rates->$toCurrency;
                    
                    // Cache the rate
                    $_SESSION[$cache_key] = $rate;
                    $_SESSION["{$cache_key}_time"] = time();
                    
                    return $rate;
                }
                return "Error: Currency rate for $toCurrency not found.";
            } else {
                return "Error: API response unsuccessful.";
            }
        } catch (Exception $e) {
            return "Error: Unable to parse the API response.";
        }
    }
    
    // If API call fails, try fallback rates
    $fallback_rates = getFallbackRates($fromCurrency);
    if (isset($fallback_rates[$toCurrency])) {
        return $fallback_rates[$toCurrency];
    }
    
    return "Error: Unable to fetch exchange rate.";
}

/**
 * Provides fallback exchange rates for common currencies
 * 
 * @param string $base Base currency code
 * @return array Array of exchange rates
 */
function getFallbackRates($base) {
    // Common fallback rates (as of October 2023)
    $rates = [
        'SGD' => [
            'USD' => 0.73,
            'EUR' => 0.69,
            'THB' => 26.46,
        ],
        'USD' => [
            'SGD' => 1.37,
            'EUR' => 0.94,
            'THB' => 36.24,
        ],
        'EUR' => [
            'SGD' => 1.45,
            'USD' => 1.06,
            'THB' => 38.42,
        ],
        'THB' => [
            'SGD' => 0.038,
            'USD' => 0.028,
            'EUR' => 0.026,
        ]
    ];
    
    return isset($rates[$base]) ? $rates[$base] : [];
}

/**
 * Converts an amount from one currency to another
 * 
 * @param float $amount Amount to convert
 * @param string $fromCurrency Source currency code
 * @param string $toCurrency Target currency code
 * @return float|string Converted amount or error message
 */
function convertCurrency($amount, $fromCurrency, $toCurrency) {
    // If amount is zero or currencies are the same, return original amount
    if ($amount <= 0 || strtoupper($fromCurrency) === strtoupper($toCurrency)) {
        return $amount;
    }
    
    $conversionRate = fetchExchangeRate($fromCurrency, $toCurrency);
    
    if (!is_numeric($conversionRate)) {
        return $conversionRate;  // Return error message
    }
    
    $convertedAmount = $amount * $conversionRate;
    
    return $convertedAmount;
}

/**
 * Formats and displays a price converted to the specified currency
 * 
 * @param float $amount Amount to convert and display
 * @param string $fromCurrency Source currency code
 * @param string $toCurrency Target currency code (optional, uses session currency if not specified)
 * @return string Formatted price with currency
 */
function displayConvertedPrice($amount, $fromCurrency, $toCurrency = null) {
    if ($toCurrency === null) {
        $toCurrency = getCurrentCurrency();
    }
    
    // Handle special cases
    if ($amount <= 0) {
        return 'Free';
    }
    
    // Convert the amount
    $convertedAmount = convertCurrency($amount, $fromCurrency, $toCurrency);
    
    // Check for errors
    if (is_string($convertedAmount) && strpos($convertedAmount, 'Error') !== false) {
        return 'Unable to display price';
    }
    
    // Format with two decimal places and append currency code
    return number_format($convertedAmount, 2) . ' ' . $toCurrency;
}