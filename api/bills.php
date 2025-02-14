<?php
/**
 * @author A. Kerem Gök
 * Fatura hatırlatıcıları API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Oturum açmanız gerekiyor']));
}

$user_id = $_SESSION['user_id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Fatura hatırlatıcılarını listele
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM bill_reminders 
                WHERE user_id = ? 
                ORDER BY due_date ASC
            ");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'POST':
        // Yeni fatura hatırlatıcısı ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO bill_reminders (
                    user_id, title, amount, due_date, 
                    repeat_interval, currency
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $data['title'],
                $data['amount'],
                $data['due_date'],
                $data['repeat_interval'] ?? 'monthly',
                $data['currency'] ?? 'TRY'
            ]);

            logActivity($user_id, 'bill_reminder_add', "Yeni fatura hatırlatıcısı eklendi: {$data['title']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Fatura hatırlatıcısı başarıyla eklendi',
                'id' => $pdo->lastInsertId()
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'PUT':
        // Fatura hatırlatıcısı güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE bill_reminders 
                SET title = ?, amount = ?, due_date = ?, 
                    repeat_interval = ?, currency = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['title'],
                $data['amount'],
                $data['due_date'],
                $data['repeat_interval'],
                $data['currency'] ?? 'TRY',
                $data['id'],
                $user_id
            ]);

            logActivity($user_id, 'bill_reminder_update', "Fatura hatırlatıcısı güncellendi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Fatura hatırlatıcısı başarıyla güncellendi'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'DELETE':
        // Fatura hatırlatıcısı sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                DELETE FROM bill_reminders 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$data['id'], $user_id]);

            logActivity($user_id, 'bill_reminder_delete', "Fatura hatırlatıcısı silindi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Fatura hatırlatıcısı başarıyla silindi'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Geçersiz metod']);
        break;
} 