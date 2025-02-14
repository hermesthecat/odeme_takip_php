<?php
/**
 * @author A. Kerem Gök
 * Gelir yönetimi API endpoint'i
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
        // Gelirleri listele
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM incomes 
                WHERE user_id = ? 
                ORDER BY income_date DESC
            ");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'POST':
        // Yeni gelir ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO incomes (user_id, amount, description, income_date, category, currency)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $data['amount'],
                $data['description'],
                $data['income_date'],
                $data['category'],
                $data['currency'] ?? 'TRY'
            ]);

            logActivity($user_id, 'income_add', "Yeni gelir eklendi: {$data['amount']} {$data['currency']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Gelir başarıyla eklendi',
                'id' => $pdo->lastInsertId()
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'PUT':
        // Gelir güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE incomes 
                SET amount = ?, description = ?, income_date = ?, category = ?, currency = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['amount'],
                $data['description'],
                $data['income_date'],
                $data['category'],
                $data['currency'] ?? 'TRY',
                $data['id'],
                $user_id
            ]);

            logActivity($user_id, 'income_update', "Gelir güncellendi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Gelir başarıyla güncellendi'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'DELETE':
        // Gelir sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                DELETE FROM incomes 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$data['id'], $user_id]);

            logActivity($user_id, 'income_delete', "Gelir silindi: ID {$data['id']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Gelir başarıyla silindi'
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