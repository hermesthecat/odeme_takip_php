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
            $filters = [];
            $params = [$user_id];
            $sql = "SELECT e.*, c.name as category_name, c.display_name as category_display_name, 
                          c.icon as category_icon, c.color as category_color 
                   FROM expenses e 
                   LEFT JOIN categories c ON e.category = c.id AND c.user_id = e.user_id 
                   WHERE e.user_id = ?";

            // Tarih filtresi
            if (isset($_GET['start_date'])) {
                $sql .= " AND e.due_date >= ?";
                $params[] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $sql .= " AND e.due_date <= ?";
                $params[] = $_GET['end_date'];
            }

            // Kategori filtresi
            if (isset($_GET['category'])) {
                $sql .= " AND e.category = ?";
                $params[] = $_GET['category'];
            }

            // Durum filtresi
            if (isset($_GET['status'])) {
                $sql .= " AND e.status = ?";
                $params[] = $_GET['status'];
            }

            // Para birimi filtresi
            if (isset($_GET['currency'])) {
                $sql .= " AND e.currency = ?";
                $params[] = $_GET['currency'];
            }

            // Etiket filtresi
            if (isset($_GET['tag'])) {
                $sql .= " AND JSON_CONTAINS(e.tags, ?)";
                $params[] = json_encode($_GET['tag']);
            }

            // Sıralama
            $sql .= " ORDER BY " . ($_GET['sort'] ?? 'due_date') . " " . ($_GET['order'] ?? 'DESC');

            // Sayfalama
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;

            // Toplam kayıt sayısı
            $countStmt = $pdo->prepare(str_replace("SELECT e.*", "SELECT COUNT(*)", $sql));
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Sayfalı sorgu
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $expenses = $stmt->fetchAll();

            // Etiketleri JSON'dan diziye çevir
            foreach ($expenses as &$expense) {
                $expense['tags'] = json_decode($expense['tags'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'expenses' => $expenses,
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
        // Yeni gider ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Kategori kontrolü
            if (!validateCategory($data['category'], 'expense')) {
                throw new Exception('Geçersiz kategori');
            }

            // Para birimi kontrolü
            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }

            // Tarih kontrolü
            if (!validateDate($data['due_date'])) {
                throw new Exception('Geçersiz tarih formatı');
            }

            // Tutar kontrolü
            if (!validateAmount($data['amount'])) {
                throw new Exception('Geçersiz tutar');
            }

            $pdo->beginTransaction();

            // Kategori ID'sini al veya oluştur
            $category_id = null;
            if (!empty($data['category'])) {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = 'expense'");
                $stmt->execute([$user_id, $data['category']]);
                $category = $stmt->fetch();
                
                if (!$category) {
                    // Yeni kategori oluştur
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (user_id, name, display_name, type, color, icon)
                        VALUES (?, ?, ?, 'expense', ?, ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $data['category'],
                        $data['category_display_name'] ?? $data['category'],
                        $data['category_color'] ?? '#' . substr(md5($data['category']), 0, 6),
                        $data['category_icon'] ?? null
                    ]);
                    $category_id = $pdo->lastInsertId();
                } else {
                    $category_id = $category['id'];
                }
            }

            // Ana gider kaydı
            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    user_id, amount, description, due_date, 
                    payment_date, category, status, currency, 
                    tags, recurring_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $data['amount'],
                $data['description'],
                $data['due_date'],
                $data['payment_date'] ?? null,
                $category_id,
                $data['status'] ?? 'pending',
                $data['currency'] ?? 'TRY',
                json_encode($data['tags'] ?? []),
                $data['recurring_id'] ?? null
            ]);

            $expense_id = $pdo->lastInsertId();

            // Tekrarlanan işlem ise
            if (isset($data['is_recurring']) && $data['is_recurring']) {
                $stmt = $pdo->prepare("
                    INSERT INTO recurring_transactions (
                        user_id, type, amount, description,
                        category, currency, interval_type,
                        interval_count, start_date
                    ) VALUES (?, 'expense', ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $user_id,
                    $data['amount'],
                    $data['description'],
                    $data['category'],
                    $data['currency'] ?? 'TRY',
                    $data['interval_type'],
                    $data['interval_count'] ?? 1,
                    $data['due_date']
                ]);

                // Ana gider kaydını güncelle
                $stmt = $pdo->prepare("
                    UPDATE expenses 
                    SET recurring_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$pdo->lastInsertId(), $expense_id]);
            }

            $pdo->commit();

            logActivity($user_id, 'expense_add', "Yeni gider eklendi: {$data['amount']} {$data['currency']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla eklendi',
                'id' => $expense_id
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Gider güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Validasyonlar
            if (!validateCategory($data['category'], 'expense')) {
                throw new Exception('Geçersiz kategori');
            }
            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }
            if (!validateDate($data['due_date'])) {
                throw new Exception('Geçersiz tarih formatı');
            }
            if (!validateAmount($data['amount'])) {
                throw new Exception('Geçersiz tutar');
            }

            $pdo->beginTransaction();

            // Kategori ID'sini al veya oluştur
            $category_id = null;
            if (!empty($data['category'])) {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = 'expense'");
                $stmt->execute([$user_id, $data['category']]);
                $category = $stmt->fetch();
                
                if (!$category) {
                    // Yeni kategori oluştur
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (user_id, name, display_name, type, color, icon)
                        VALUES (?, ?, ?, 'expense', ?, ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $data['category'],
                        $data['category_display_name'] ?? $data['category'],
                        $data['category_color'] ?? '#' . substr(md5($data['category']), 0, 6),
                        $data['category_icon'] ?? null
                    ]);
                    $category_id = $pdo->lastInsertId();
                } else {
                    $category_id = $category['id'];
                }
            }

            // Gider güncelleme
            $stmt = $pdo->prepare("
                UPDATE expenses 
                SET amount = ?, description = ?, due_date = ?, 
                    payment_date = ?, category = ?, status = ?, 
                    currency = ?, tags = ?
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['amount'],
                $data['description'],
                $data['due_date'],
                $data['payment_date'] ?? null,
                $category_id,
                $data['status'],
                $data['currency'] ?? 'TRY',
                json_encode($data['tags'] ?? []),
                $data['id'],
                $user_id
            ]);

            // Tekrarlanan işlem güncelleme
            if (isset($data['recurring_id']) && $data['update_recurring']) {
                $stmt = $pdo->prepare("
                    UPDATE recurring_transactions 
                    SET amount = ?, description = ?, category = ?,
                        currency = ?, interval_type = ?, 
                        interval_count = ?
                    WHERE id = ? AND user_id = ?
                ");

                $stmt->execute([
                    $data['amount'],
                    $data['description'],
                    $data['category'],
                    $data['currency'] ?? 'TRY',
                    $data['interval_type'],
                    $data['interval_count'] ?? 1,
                    $data['recurring_id'],
                    $user_id
                ]);
            }

            $pdo->commit();

            logActivity($user_id, 'expense_update', "Gider güncellendi: ID {$data['id']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla güncellendi'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Gider sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $pdo->beginTransaction();

            // Tekrarlanan işlemi de sil
            if (isset($data['delete_recurring']) && $data['delete_recurring']) {
                $stmt = $pdo->prepare("
                    DELETE FROM recurring_transactions 
                    WHERE id = (
                        SELECT recurring_id 
                        FROM expenses 
                        WHERE id = ? AND user_id = ?
                    )
                ");
                $stmt->execute([$data['id'], $user_id]);
            }

            $stmt = $pdo->prepare("
                DELETE FROM expenses 
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([$data['id'], $user_id]);

            $pdo->commit();

            logActivity($user_id, 'expense_delete', "Gider silindi: ID {$data['id']}");

            echo json_encode([
                'success' => true,
                'message' => 'Gider başarıyla silindi'
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Geçersiz metod']);
        break;
}
