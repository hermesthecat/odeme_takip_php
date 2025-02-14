<?php

/**
 * @author A. Kerem Gök
 * Genel yardımcı fonksiyonlar
 */

session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function checkAuth()
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function checkToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('CSRF token doğrulaması başarısız.');
    }
}

function formatMoney($amount, $currency = 'TRY')
{
    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

function getExchangeRates()
{
    $rates = [];
    $ch = curl_init('https://api.exchangerate.host/latest?base=TRY');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['rates'])) {
            $rates = $data['rates'];
        }
    }
    return $rates;
}

function logActivity($user_id, $action, $details = '')
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $details]);
}
