<?php

/**
 * @author A. Kerem Gök
 * Kimlik doğrulama API endpoint'i
 */

require_once '../includes/config.php';
require_once '../includes/security.php';
//require_once '../includes/mail.php';

// Initialize secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

    // Set session cookie parameters
$parsed_url = parse_url(ALLOWED_ORIGIN);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $parsed_url['host'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

    session_start();
}

if (!initSecureSession()) {
    http_response_code(440); // Login Time-out
    die(json_encode([
        'status' => false,
        'message' => 'Oturum süresi doldu. Lütfen tekrar giriş yapın.'
    ]));
}

header('Content-Type: application/json');
require_once '../includes/db.php';


// CORS ayarları - Sadece izin verilen originlere
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// İstek tipini al
$action = isset($_GET['action']) ? $_GET['action'] : 
         (isset($_POST['action']) ? $_POST['action'] : 
         (json_decode(file_get_contents('php://input'), true)['action'] ?? ''));

// Yanıt şablonu
$response = [
    'status' => false,
    'message' => '',
    'data' => null
];

try {
    // Rate limiting kontrolü
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'], $action)) {
        throw new Exception('Çok fazla deneme. Lütfen daha sonra tekrar deneyin.');
    }

    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $username = sanitizeInput($data['username'] ?? '');
            $password = $data['password'] ?? '';
            $remember = $data['remember'] ?? false;

            if (empty($username) || empty($password)) {
                throw new Exception('Kullanıcı adı ve şifre gereklidir');
            }

            $stmt = $pdo->prepare('
                SELECT id, username, password, first_name, last_name, status, 
                       failed_login_attempts, lockout_until
                FROM users 
                WHERE username = ? AND status != "banned"
            ');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Brute force koruması
            if ($user && $user['lockout_until'] && new DateTime($user['lockout_until']) > new DateTime()) {
                $lockout_time = new DateTime($user['lockout_until']);
                $now = new DateTime();
                $remaining = $now->diff($lockout_time);
                throw new Exception(sprintf(
                    'Hesabınız kilitlendi. Lütfen %d dakika sonra tekrar deneyin.',
                    ceil($remaining->i)
                ));
            }

            if (!$user || !password_verify($password, $user['password'])) {
                // Başarısız giriş denemesini kaydet
                if ($user) {
                    $stmt = $pdo->prepare('
                        UPDATE users 
                        SET failed_login_attempts = failed_login_attempts + 1,
                            lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                        WHERE id = ?
                    ');
                    $stmt->execute([$user['id']]);
                }
                logSecurityEvent('failed_login', [
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
                throw new Exception('Geçersiz kullanıcı adı veya şifre');
            }

            if ($user['status'] === 'inactive') {
                throw new Exception('Hesabınız aktif değil');
            }

            // Clear any existing session data and start fresh
            $_SESSION = array();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            session_start();
            session_regenerate_id(true);

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            // Ensure cookie settings
            $parsed_url = parse_url(ALLOWED_ORIGIN);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $parsed_url['host'],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

        // Remember me token oluştur
            if ($remember) {
                $token = generateRememberMeToken($user['id']);
                setRememberMeCookie($token);
            }
            

            // Başarılı girişi kaydet ve sayaçları sıfırla
            $stmt = $pdo->prepare('
                UPDATE users 
                SET last_login = NOW(),
                    failed_login_attempts = 0,
                    lockout_until = NULL
                WHERE id = ?
            ');
            $stmt->execute([$user['id']]);

            logSecurityEvent('successful_login', [
                'user_id' => $user['id'],
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);

            $response['status'] = true;
            $response['message'] = 'Giriş başarılı';
            $response['data'] = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ];
            break;

        case 'logout':
            session_destroy();
            $response['status'] = true;
            $response['message'] = 'Çıkış başarılı';
            break;

        case 'check':
            if (isset($_SESSION['user_id'])) {
                // Update session timestamp
                $_SESSION['last_activity'] = time();
                
                // Verify session integrity
                if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || 
                    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                    session_destroy();
                    $response['status'] = false;
                    $response['message'] = 'Güvenlik kontrolü başarısız. Lütfen tekrar giriş yapın.';
                } else {
                    $response['status'] = true;
                    $response['data'] = [
                        'user_id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'first_name' => $_SESSION['first_name'],
                        'last_name' => $_SESSION['last_name']
                    ];
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'Oturum bulunamadı.';
            }
            break;

        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $email = $data['email'] ?? '';
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';

            if (empty($username) || empty($password) || empty($email)) {
                throw new Exception('Tüm zorunlu alanları doldurun');
            }

            // Kullanıcı adı ve email kontrolü
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu kullanıcı adı veya email zaten kullanımda');
            }

            // Yeni kullanıcı oluştur
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$username, $hashedPassword, $email, $firstName, $lastName]);

            $response['status'] = true;
            $response['message'] = 'Kayıt başarılı';
            break;

        case 'reset-password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? '';

            if (empty($email)) {
                throw new Exception('Email adresi gereklidir');
            }

            // Email kontrolü
            $stmt = $pdo->prepare('SELECT id, username, first_name, email FROM users WHERE email = ? ');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('Bu email adresi ile kayıtlı kullanıcı bulunamadı');
            }

            // Şifre sıfırlama token'ı oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Varolan token'ları temizle
            $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $stmt->execute([$user['id']]);

            // Yeni token ekle
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expires]);

            // Email gönder
            $emailSent = sendPasswordResetEmail(
                $user['email'],
                $user['username'],
                $user['first_name'],
                $token
            );

            if (!$emailSent) {
                throw new Exception('Email gönderirken bir hata oluştu');
            }

            $response['status'] = true;
            $response['message'] = 'Şifre sıfırlama bağlantısı email adresinize gönderildi';
            break;

        case 'verify-reset-token':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? '';

            if (empty($token)) {
                throw new Exception('Token gereklidir');
            }

            // Token kontrolü
            $stmt = $pdo->prepare('
                SELECT user_id 
                FROM password_resets 
                WHERE token = ? 
                AND expires_at > NOW() 
                AND used = 0
            ');
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                throw new Exception('Geçersiz veya süresi dolmuş token');
            }

            $response['status'] = true;
            $response['message'] = 'Token geçerli';
            $response['data'] = ['user_id' => $reset['user_id']];
            break;

        case 'set-new-password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($token) || empty($password)) {
                throw new Exception('Token ve yeni şifre gereklidir');
            }

            // Token kontrolü
            $stmt = $pdo->prepare('
                SELECT user_id 
                FROM password_resets 
                WHERE token = ? 
                AND expires_at > NOW() 
                AND used = 0
            ');
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                throw new Exception('Geçersiz veya süresi dolmuş token');
            }

            // Şifreyi güncelle
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashedPassword, $reset['user_id']]);

            // Token'ı kullanıldı olarak işaretle
            $stmt = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
            $stmt->execute([$token]);

            $response['status'] = true;
            $response['message'] = 'Şifreniz başarıyla güncellendi';
            break;

        default:
            throw new Exception('Geçersiz işlem');
    }
} catch (Exception $e) {
    $response['status'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
