<?php

/**
 * @author A. Kerem Gök
 * Şifremi unuttum API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';

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
    $email = $data['email'] ?? '';

    if (empty($email)) {
        throw new Exception('Email adresi gereklidir');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz email adresi');
    }

    // Email kontrolü
    $stmt = $pdo->prepare('
        SELECT id, username, first_name, email 
        FROM users 
        WHERE email = ? AND status = "active"
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Bu email adresi ile kayıtlı kullanıcı bulunamadı');
    }

    // Şifre sıfırlama token'ı oluştur
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->beginTransaction();

    try {
        // Varolan token'ları temizle
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->execute([$user['id']]);

        // Yeni token ekle
        $stmt = $pdo->prepare('
            INSERT INTO password_resets (
                user_id, token, expires_at, created_at
            ) VALUES (?, ?, ?, NOW())
        ');
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

        $pdo->commit();

        logActivity($user['id'], 'password_reset_request', 'Şifre sıfırlama talebi oluşturuldu');

        echo json_encode([
            'success' => true,
            'message' => 'Şifre sıfırlama bağlantısı email adresinize gönderildi'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 