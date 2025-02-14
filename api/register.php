<?php

/**
 * @author A. Kerem Gök
 * Kullanıcı kayıt API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

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
    checkToken($data['csrf_token'] ?? '');

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $email = $data['email'] ?? '';
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';

    // Validasyonlar
    if (empty($username) || empty($password) || empty($email)) {
        throw new Exception('Tüm zorunlu alanları doldurun');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz email adresi');
    }

    if (strlen($password) < 8) {
        throw new Exception('Şifre en az 8 karakter olmalıdır');
    }

    // Kullanıcı adı ve email kontrolü
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Bu kullanıcı adı veya email zaten kullanımda');
    }

    // Yeni kullanıcı oluştur
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO users (
            username, password, email, first_name, last_name, 
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, "active", NOW())
    ');
    
    $stmt->execute([
        $username, 
        $hashedPassword, 
        $email, 
        $firstName, 
        $lastName
    ]);

    $user_id = $pdo->lastInsertId();

    // Varsayılan kategorileri ekle
    $defaultCategories = json_decode(INCOME_CATEGORIES, true);
    foreach ($defaultCategories as $key => $name) {
        $stmt = $pdo->prepare('
            INSERT INTO categories (
                user_id, name, type, icon, color
            ) VALUES (?, ?, "income", ?, ?)
        ');
        $stmt->execute([
            $user_id, 
            $name, 
            'icon-' . $key, 
            '#' . substr(md5($key), 0, 6)
        ]);
    }

    $defaultCategories = json_decode(EXPENSE_CATEGORIES, true);
    foreach ($defaultCategories as $key => $name) {
        $stmt = $pdo->prepare('
            INSERT INTO categories (
                user_id, name, type, icon, color
            ) VALUES (?, ?, "expense", ?, ?)
        ');
        $stmt->execute([
            $user_id, 
            $name, 
            'icon-' . $key, 
            '#' . substr(md5($key), 0, 6)
        ]);
    }

    logActivity($user_id, 'user_register', 'Yeni kullanıcı kaydı');

    echo json_encode([
        'success' => true,
        'message' => 'Kayıt başarılı'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 