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
            $filters = [];
            $params = [$user_id];
            $sql = "SELECT sg.*, 
                          ROUND((sg.current_amount / sg.target_amount) * 100, 2) as progress,
                          DATEDIFF(sg.target_date, CURDATE()) as days_remaining
                   FROM savings_goals sg 
                   WHERE sg.user_id = ?";

            // Durum filtresi
            if (isset($_GET['status'])) {
                $sql .= " AND sg.status = ?";
                $params[] = $_GET['status'];
            }

            // Öncelik filtresi
            if (isset($_GET['priority'])) {
                $sql .= " AND sg.priority = ?";
                $params[] = $_GET['priority'];
            }

            // Para birimi filtresi
            if (isset($_GET['currency'])) {
                $sql .= " AND sg.currency = ?";
                $params[] = $_GET['currency'];
            }

            // Tarih filtresi
            if (isset($_GET['start_date'])) {
                $sql .= " AND sg.target_date >= ?";
                $params[] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $sql .= " AND sg.target_date <= ?";
                $params[] = $_GET['end_date'];
            }

            // Sıralama
            $sql .= " ORDER BY " . ($_GET['sort'] ?? 'target_date') . " " . ($_GET['order'] ?? 'ASC');

            // Sayfalama
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;

            // Toplam kayıt sayısı
            $countStmt = $pdo->prepare(str_replace("SELECT sg.*", "SELECT COUNT(*)", $sql));
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Sayfalı sorgu
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $savings = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => [
                    'savings' => $savings,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => ceil($total / $limit)
                    ]
                ]
            ]);
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

            // Validasyonlar
            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            if (!validateAmount($data['target_amount'])) {
                throw new Exception('Geçersiz hedef tutar');
            }

            if (!validateAmount($data['current_amount'] ?? 0)) {
                throw new Exception('Geçersiz mevcut tutar');
            }

            if (!validateDate($data['target_date'])) {
                throw new Exception('Geçersiz hedef tarihi');
            }

            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }

            if (!in_array($data['priority'] ?? 'medium', ['low', 'medium', 'high'])) {
                throw new Exception('Geçersiz öncelik seviyesi');
            }

            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (
                    user_id, title, target_amount, current_amount,
                    description, target_date, currency, status, priority
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $data['title'],
                $data['target_amount'],
                $data['current_amount'] ?? 0,
                $data['description'] ?? null,
                $data['target_date'],
                $data['currency'] ?? 'TRY',
                'active',
                $data['priority'] ?? 'medium'
            ]);

            $savings_id = $pdo->lastInsertId();

            logActivity($user_id, 'savings_add', "Yeni birikim hedefi eklendi: {$data['title']}");

            echo json_encode([
                'success' => true,
                'message' => 'Birikim hedefi başarıyla eklendi',
                'id' => $savings_id
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Birikim hedefi güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Validasyonlar
            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            if (!validateAmount($data['target_amount'])) {
                throw new Exception('Geçersiz hedef tutar');
            }

            if (!validateAmount($data['current_amount'])) {
                throw new Exception('Geçersiz mevcut tutar');
            }

            if (!validateDate($data['target_date'])) {
                throw new Exception('Geçersiz hedef tarihi');
            }

            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }

            if (!in_array($data['priority'] ?? 'medium', ['low', 'medium', 'high'])) {
                throw new Exception('Geçersiz öncelik seviyesi');
            }

            if (!in_array($data['status'] ?? 'active', ['active', 'completed', 'cancelled'])) {
                throw new Exception('Geçersiz durum');
            }

            $stmt = $pdo->prepare("
                UPDATE savings_goals 
                SET title = ?, target_amount = ?, current_amount = ?,
                    description = ?, target_date = ?, currency = ?,
                    status = ?, priority = ?
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['title'],
                $data['target_amount'],
                $data['current_amount'],
                $data['description'] ?? null,
                $data['target_date'],
                $data['currency'] ?? 'TRY',
                $data['status'] ?? 'active',
                $data['priority'] ?? 'medium',
                $data['id'],
                $user_id
            ]);

            logActivity($user_id, 'savings_update', "Birikim hedefi güncellendi: ID {$data['id']}");

            echo json_encode([
                'success' => true,
                'message' => 'Birikim hedefi başarıyla güncellendi'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
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

            logActivity($user_id, 'savings_delete', "Birikim hedefi silindi: ID {$data['id']}");

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
