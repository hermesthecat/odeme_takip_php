<?php
// Application configuration

// Base URL configuration
define('BASE_URL', 'http://butce.local');

// Session configuration
define('SESSION_NAME', 'butce_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// API configuration
define('EXCHANGE_RATE_API_KEY', '4f467070688418cb9958422c637c880c');
define('EXCHANGE_RATE_API_URL', 'https://api.exchangerate.host/live');

// Currency configuration
define('DEFAULT_CURRENCY', 'TRY');
define('SUPPORTED_CURRENCIES', serialize([
    'TRY' => 'Turkish Lira',
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'GBP' => 'British Pound'
]));

// Date configuration
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('TIMEZONE', 'Europe/Istanbul');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set timezone
date_default_timezone_set(TIMEZONE);

// Database configuration - imported separately
require_once __DIR__ . '/database.php';

// Helper Functions
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

function sanitize($input) {
    return htmlspecialchars(strip_tags($input));
}

function formatMoney($amount, $currency = DEFAULT_CURRENCY) {
    return number_format($amount, 2) . ' ' . $currency;
}

function getFormattedDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// API Response Helper
function sendJsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Flash Messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
