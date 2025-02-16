<?php

/**
 * Generate or retrieve CSRF token
 * @return string CSRF token
 */
function csrf_token() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format money amount
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function format_money($amount, $currency = 'TRY') {
    $currencies = [
        'TRY' => ['symbol' => '₺', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'decimals' => 2],
        'EUR' => ['symbol' => '€', 'decimals' => 2],
        'GBP' => ['symbol' => '£', 'decimals' => 2]
    ];

    $curr = $currencies[$currency] ?? ['symbol' => $currency, 'decimals' => 2];
    return $curr['symbol'] . ' ' . number_format($amount, $curr['decimals'], ',', '.');
}

/**
 * Get current date in MySQL format
 * @return string Current date
 */
function current_date() {
    return date('Y-m-d H:i:s');
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
} 