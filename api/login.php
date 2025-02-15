<?php

/**
 * @author A. Kerem Gök
 * Kullanıcı giriş API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Geçersiz metod']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception('Kullanıcı adı ve şifre gereklidir');
    }

    // Check if the username exists
    $stmt = $pdo->prepare('
        SELECT id, username, password, first_name, last_name, email, status,
               failed_login_attempts, lockout_until, last_login
        FROM users 
        WHERE username = ?
    ');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Geçersiz kullanıcı adı veya şifre');
    }

    // Check if account is locked
    if ($user['status'] !== 'active') {
        throw new Exception('Hesap aktif değil');
    }

    // Check if account is temporarily locked
    if ($user['lockout_until'] && new DateTime($user['lockout_until']) > new DateTime()) {
        $lockout_time = new DateTime($user['lockout_until']);
        $now = new DateTime();
        $remaining = $now->diff($lockout_time);
        throw new Exception(sprintf(
            'Çok fazla başarısız giriş denemesi. Lütfen %d dakika sonra tekrar deneyin.',
            ceil($remaining->i)
        ));
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Increment failed attempts
        $attempts = $user['failed_login_attempts'] + 1;
        $lockout_sql = '';
        
        // If max attempts reached, lock the account
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockout_sql = ', lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)';
        }
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = ?" . $lockout_sql . "
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $user['id']]);

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            throw new Exception('Çok fazla başarısız giriş denemesi. Hesap 15 dakika kilitlendi.');
        }
        
        throw new Exception('Geçersiz kullanıcı adı veya şifre');
    }

    // Oturum başlat
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];

    // Reset failed attempts and update last login
    $stmt = $pdo->prepare('
        UPDATE users 
        SET failed_login_attempts = 0,
            lockout_until = NULL,
            last_login = NOW() 
        WHERE id = ?
    ');
    $stmt->execute([$user['id']]);

    logActivity($user['id'], 'user_login', 'Kullanıcı girişi yapıldı');

    echo json_encode([
        'success' => true,
        'message' => 'Giriş başarılı',
        'data' => [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
