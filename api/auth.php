<?php
/**
 * @author A. Kerem Gök
 * Kimlik doğrulama API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// İstek tipini al
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Yanıt şablonu
$response = [
    'status' => false,
    'message' => '',
    'data' => null
];

try {
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception('Kullanıcı adı ve şifre gereklidir');
            }

            $stmt = $db->prepare('SELECT id, username, password, first_name, last_name FROM users WHERE username = ? AND status = "active"');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Geçersiz kullanıcı adı veya şifre');
            }

            // Oturum başlat
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            // Son giriş zamanını güncelle
            $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $stmt->execute([$user['id']]);

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
            $response['status'] = isset($_SESSION['user_id']);
            $response['data'] = $response['status'] ? [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'first_name' => $_SESSION['first_name'],
                'last_name' => $_SESSION['last_name']
            ] : null;
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
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Bu kullanıcı adı veya email zaten kullanımda');
            }

            // Yeni kullanıcı oluştur
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)');
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
            $stmt = $db->prepare('SELECT id, username, first_name FROM users WHERE email = ? AND status = "active"');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('Bu email adresi ile kayıtlı kullanıcı bulunamadı');
            }

            // Şifre sıfırlama token'ı oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expires]);

            // Email gönderme işlemi burada yapılacak
            // TODO: Email gönderme fonksiyonu eklenecek

            $response['status'] = true;
            $response['message'] = 'Şifre sıfırlama bağlantısı email adresinize gönderildi';
            break;

        default:
            throw new Exception('Geçersiz işlem');
    }
} catch (Exception $e) {
    $response['status'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 