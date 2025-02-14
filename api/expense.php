<?php

/**
 * @author A. Kerem Gök
 * Gider yönetimi API endpoint'i
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
        // Giderleri listele
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM expenses 
                WHERE user_id = ? 
                ORDER BY due_date DESC
            ");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'POST':
        // Yeni gider ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    user_id, amount, description, due_date, 
                    payment_date, category, status, currency
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $data['amount'],
                $data['description'],
                $data['due_date'],
                $data['payment_date'] ?? null,
                $data['category'],
                $data['status'] ?? 'pending',
                $data['currency'] ?? 'TRY'
            ]);

            logActivity($user_id, 'expense_add', "Yeni gider eklendi: {$data['amount']} {$data['currency']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla eklendi',
                'id' => $pdo->lastInsertId()
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'PUT':
        // Gider güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE expenses 
                SET amount = ?, description = ?, due_date = ?, 
                    payment_date = ?, category = ?, status = ?, currency = ?
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['amount'],
                $data['description'],
                $data['due_date'],
                $data['payment_date'] ?? null,
                $data['category'],
                $data['status'],
                $data['currency'] ?? 'TRY',
                $data['id'],
                $user_id
            ]);

            logActivity($user_id, 'expense_update', "Gider güncellendi: ID {$data['id']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla güncellendi'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'DELETE':
        // Gider sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $stmt = $pdo->prepare("
                DELETE FROM expenses 
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([$data['id'], $user_id]);

            logActivity($user_id, 'expense_delete', "Gider silindi: ID {$data['id']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla silindi'
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
