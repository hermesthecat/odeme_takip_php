<?php
/**
 * @author A. Kerem Gök
 * Birikim hedefleri API endpoint'i
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
        // Birikim hedeflerini listele
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM savings_goals 
                WHERE user_id = ? 
                ORDER BY target_date ASC
            ");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'POST':
        // Yeni birikim hedefi ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (
                    user_id, target_amount, current_amount, 
                    description, target_date, currency
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $data['target_amount'],
                $data['current_amount'] ?? 0,
                $data['description'],
                $data['target_date'],
                $data['currency'] ?? 'TRY'
            ]);

            logActivity($user_id, 'savings_goal_add', "Yeni birikim hedefi eklendi: {$data['target_amount']} {$data['currency']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Birikim hedefi başarıyla eklendi',
                'id' => $pdo->lastInsertId()
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'PUT':
        // Birikim hedefi güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE savings_goals 
                SET target_amount = ?, current_amount = ?, 
                    description = ?, target_date = ?, currency = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['target_amount'],
                $data['current_amount'],
                $data['description'],
                $data['target_date'],
                $data['currency'] ?? 'TRY',
                $data['id'],
                $user_id
            ]);

            logActivity($user_id, 'savings_goal_update', "Birikim hedefi güncellendi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Birikim hedefi başarıyla güncellendi'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'DELETE':
        // Birikim hedefi sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                DELETE FROM savings_goals 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$data['id'], $user_id]);

            logActivity($user_id, 'savings_goal_delete', "Birikim hedefi silindi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Birikim hedefi başarıyla silindi'
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