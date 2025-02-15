<?php

/**
 * @author A. Kerem Gök
 * Şifre sıfırlama API endpoint'i
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
    $token = $data['token'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($token) || empty($password)) {
        throw new Exception('Token ve yeni şifre gereklidir');
    }

    if (strlen($password) < 8) {
        throw new Exception('Şifre en az 8 karakter olmalıdır');
    }

    $pdo->beginTransaction();

    try {
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

        $pdo->commit();

        logActivity($reset['user_id'], 'password_reset', 'Şifre sıfırlama işlemi tamamlandı');

        echo json_encode([
            'success' => true,
            'message' => 'Şifreniz başarıyla güncellendi'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
