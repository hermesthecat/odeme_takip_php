<?php

/**
 * @author A. Kerem Gök
 * Genel yardımcı fonksiyonlar
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']) 
        && isset($_SESSION['username']) 
        && isset($_SESSION['last_activity'])
        && isset($_SESSION['ip'])
        && isset($_SESSION['user_agent'])
        && $_SESSION['ip'] === $_SERVER['REMOTE_ADDR']
        && $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT']
        && (time() - $_SESSION['last_activity']) <= SESSION_LIFETIME;
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
    return generateCsrfToken();
}

function checkToken($token)
{
    return checkCsrfToken($token);
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
