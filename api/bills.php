<?php

/**
 * @author A. Kerem Gök
 * Fatura yönetimi API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Oturum açmanız gerekiyor']));
}

$user_id = $_SESSION['user_id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Faturaları listele
        try {
            $filters = [];
            $params = [$user_id];
            $sql = "SELECT br.*, 
                          bc.name as category_name,
                          bc.icon as category_icon,
                          bc.color as category_color,
                          DATEDIFF(br.due_date, CURDATE()) as days_remaining,
                          (
                              SELECT COALESCE(SUM(amount), 0)
                              FROM bill_payments
                              WHERE bill_id = br.id
                          ) as total_paid
                   FROM bill_reminders br 
                   LEFT JOIN bill_categories bc ON bc.id = br.category 
                   WHERE br.user_id = ?";

            // Durum filtresi
            if (isset($_GET['status'])) {
                $sql .= " AND br.status = ?";
                $params[] = $_GET['status'];
            }

            // Kategori filtresi
            if (isset($_GET['category'])) {
                $sql .= " AND br.category = ?";
                $params[] = $_GET['category'];
            }

            // Para birimi filtresi
            if (isset($_GET['currency'])) {
                $sql .= " AND br.currency = ?";
                $params[] = $_GET['currency'];
            }

            // Tekrar aralığı filtresi
            if (isset($_GET['repeat_interval'])) {
                $sql .= " AND br.repeat_interval = ?";
                $params[] = $_GET['repeat_interval'];
            }

            // Tarih filtresi
            if (isset($_GET['start_date'])) {
                $sql .= " AND br.due_date >= ?";
                $params[] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $sql .= " AND br.due_date <= ?";
                $params[] = $_GET['end_date'];
            }

            // Sıralama
            $sql .= " ORDER BY " . ($_GET['sort'] ?? 'due_date') . " " . ($_GET['order'] ?? 'ASC');

            // Sayfalama
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;

            // Toplam kayıt sayısı
            $countStmt = $pdo->prepare(str_replace("SELECT br.*", "SELECT COUNT(*)", $sql));
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Sayfalı sorgu
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bills = $stmt->fetchAll();

            // Fatura ödemelerini getir
            foreach ($bills as &$bill) {
                $stmt = $pdo->prepare("
                    SELECT * FROM bill_payments 
                    WHERE bill_id = ? 
                    ORDER BY payment_date DESC
                ");
                $stmt->execute([$bill['id']]);
                $bill['payments'] = $stmt->fetchAll();
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'bills' => $bills,
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

        // Kategori listeleme endpoint'i
        if (isset($_GET['categories'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM bill_categories
                    WHERE user_id = ?
                    ORDER BY name
                ");
                $stmt->execute([$user_id]);
                echo json_encode([
                    'success' => true,
                    'data' => $stmt->fetchAll()
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Veritabanı hatası']);
            }
            break;
        }
        break;

    case 'POST':
        // Yeni fatura ekle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Validasyonlar
            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            if (!validateAmount($data['amount'])) {
                throw new Exception('Geçersiz tutar');
            }

            if (!validateDate($data['due_date'])) {
                throw new Exception('Geçersiz vade tarihi');
            }

            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }

            // Tekrarlama aralığı validasyonu
            $validIntervals = json_decode(VALID_BILL_INTERVALS, true);
            if (!in_array($data['repeat_interval'] ?? DEFAULT_BILL_REPEAT_INTERVAL, $validIntervals)) {
                throw new Exception('Geçersiz tekrar aralığı');
            }

            $pdo->beginTransaction();

            try {
                $category_id = null;

                // Kategori işleme
                if (!empty($data['category'])) {
                    try {
                        // Kategori oluşturma veya güncelleme
                        $stmt = $pdo->prepare("
                            INSERT INTO bill_categories (
                                user_id, name, icon, color
                            ) VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            icon = VALUES(icon),
                            color = COALESCE(VALUES(color), color)
                        ");

                        $defaultColors = json_decode(DEFAULT_CATEGORY_COLORS, true);
                        $color = $data['category_color'] ??
                            $defaultColors[$data['category']] ??
                            '#' . substr(md5($data['category']), 0, 6);

                        $stmt->execute([
                            $user_id,
                            $data['category'],
                            $data['category_icon'] ?? null,
                            $color
                        ]);

                        $category_id = $pdo->lastInsertId() ?: (
                            $pdo->query("SELECT id FROM bill_categories WHERE user_id = {$user_id} AND name = " .
                                $pdo->quote($data['category']))->fetchColumn()
                        );
                    } catch (PDOException $e) {
                        if ($e->getCode() != '23000') throw $e;
                        // Duplicate key durumunda mevcut kategoriyi kullan
                        $stmt = $pdo->prepare("SELECT id FROM bill_categories WHERE user_id = ? AND name = ?");
                        $stmt->execute([$user_id, $data['category']]);
                        $category_id = $stmt->fetchColumn();
                    }
                }

                // Fatura kaydı
                $stmt = $pdo->prepare("
                    INSERT INTO bill_reminders (
                        user_id, title, amount, due_date,
                        repeat_interval, description, category,
                        currency, status, notification_days
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $user_id,
                    $data['title'],
                    $data['amount'],
                    $data['due_date'],
                    $data['repeat_interval'] ?? DEFAULT_BILL_REPEAT_INTERVAL,
                    $data['description'] ?? null,
                    $category_id ?? null,
                    $data['currency'] ?? DEFAULT_CURRENCY,
                    $data['status'] ?? DEFAULT_BILL_STATUS,
                    $data['notification_days'] ?? DEFAULT_BILL_NOTIFICATION_DAYS
                ]);

                $bill_id = $pdo->lastInsertId();

                // Bildirim ayarı
                $stmt = $pdo->prepare("
                    INSERT INTO bill_notifications (
                        user_id, notification_type, days_before
                    ) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE days_before = ?
                ");

                foreach (['email', 'sms', 'push'] as $type) {
                    if (isset($data['notifications'][$type])) {
                        $stmt->execute([
                            $user_id,
                            $type,
                            $data['notifications'][$type],
                            $data['notifications'][$type]
                        ]);
                    }
                }

                $pdo->commit();

                logActivity($user_id, 'bill_add', "Yeni fatura eklendi: {$data['title']}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla eklendi',
                    'id' => $bill_id
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Fatura güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Validasyonlar
            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            if (!validateAmount($data['amount'])) {
                throw new Exception('Geçersiz tutar');
            }

            if (!validateDate($data['due_date'])) {
                throw new Exception('Geçersiz vade tarihi');
            }

            if (!validateCurrency($data['currency'] ?? 'TRY')) {
                throw new Exception('Geçersiz para birimi');
            }

            // Tekrarlama aralığı validasyonu
            $validIntervals = json_decode(VALID_BILL_INTERVALS, true);
            if (!in_array($data['repeat_interval'] ?? DEFAULT_BILL_REPEAT_INTERVAL, $validIntervals)) {
                throw new Exception('Geçersiz tekrar aralığı');
            }

            $pdo->beginTransaction();

            try {
                // Fatura kategorisi kontrolü/güncelleme
                if (!empty($data['category'])) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM bill_categories 
                        WHERE user_id = ? AND name = ?
                    ");
                    $stmt->execute([$user_id, $data['category']]);
                    $category = $stmt->fetch();

                    if (!$category) {
                        $stmt = $pdo->prepare("
                            INSERT INTO bill_categories (
                                user_id, name, icon, color
                            ) VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $user_id,
                            $data['category'],
                            $data['category_icon'] ?? null,
                            $data['category_color'] ?? '#' . substr(md5($data['category']), 0, 6)
                        ]);
                        $category_id = $pdo->lastInsertId();
                    } else {
                        $category_id = $category['id'];
                    }
                }

                // Fatura güncelleme
                $stmt = $pdo->prepare("
                    UPDATE bill_reminders 
                    SET title = ?, amount = ?, due_date = ?,
                        repeat_interval = ?, description = ?, category = ?,
                        currency = ?, status = ?, notification_days = ?
                    WHERE id = ? AND user_id = ?
                ");

                $stmt->execute([
                    $data['title'],
                    $data['amount'],
                    $data['due_date'],
                    $data['repeat_interval'] ?? DEFAULT_BILL_REPEAT_INTERVAL,
                    $data['description'] ?? null,
                    $category_id ?? null,
                    $data['currency'] ?? 'TRY',
                    $data['status'] ?? 'active',
                    $data['notification_days'] ?? 3,
                    $data['id'],
                    $user_id
                ]);

                // Bildirim ayarlarını güncelle
                $stmt = $pdo->prepare("
                    INSERT INTO bill_notifications (
                        user_id, notification_type, days_before
                    ) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE days_before = ?
                ");

                foreach (['email', 'sms', 'push'] as $type) {
                    if (isset($data['notifications'][$type])) {
                        $stmt->execute([
                            $user_id,
                            $type,
                            $data['notifications'][$type],
                            $data['notifications'][$type]
                        ]);
                    }
                }

                $pdo->commit();

                logActivity($user_id, 'bill_update', "Fatura güncellendi: ID {$data['id']}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla güncellendi'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Fatura sil
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            $pdo->beginTransaction();

            try {
                // Fatura ödemelerini sil
                $stmt = $pdo->prepare("
                    DELETE FROM bill_payments 
                    WHERE bill_id = ? 
                    AND bill_id IN (
                        SELECT id FROM bill_reminders 
                        WHERE user_id = ?
                    )
                ");
                $stmt->execute([$data['id'], $user_id]);

                // Faturayı sil
                $stmt = $pdo->prepare("
                    DELETE FROM bill_reminders 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$data['id'], $user_id]);

                $pdo->commit();

                logActivity($user_id, 'bill_delete', "Fatura silindi: ID {$data['id']}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla silindi'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'PATCH':
        // Fatura ödemesi ekle/güncelle
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            if (!validateAmount($data['amount'])) {
                throw new Exception('Geçersiz ödeme tutarı');
            }

            if (!validateDate($data['payment_date'])) {
                throw new Exception('Geçersiz ödeme tarihi');
            }

            $pdo->beginTransaction();

            try {
                // Fatura kontrolü
                $stmt = $pdo->prepare("
                    SELECT * FROM bill_reminders 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$data['bill_id'], $user_id]);
                $bill = $stmt->fetch();

                if (!$bill) {
                    throw new Exception('Fatura bulunamadı');
                }

                // Ödeme kaydı
                $stmt = $pdo->prepare("
                    INSERT INTO bill_payments (
                        bill_id, amount, payment_date,
                        payment_method, reference_no, notes
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $data['bill_id'],
                    $data['amount'],
                    $data['payment_date'],
                    $data['payment_method'] ?? null,
                    $data['reference_no'] ?? null,
                    $data['notes'] ?? null
                ]);

                // Toplam ödeme tutarını kontrol et
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total_paid
                    FROM bill_payments
                    WHERE bill_id = ?
                ");
                $stmt->execute([$data['bill_id']]);
                $total_paid = $stmt->fetch()['total_paid'];

                // Fatura durumunu güncelle
                $status = $total_paid >= $bill['amount'] ? 'paid' : 'active';
                $stmt = $pdo->prepare("
                    UPDATE bill_reminders 
                    SET status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $data['bill_id']]);

                $pdo->commit();

                logActivity($user_id, 'bill_payment', "Fatura ödemesi eklendi: {$data['amount']} TL - Fatura ID: {$data['bill_id']}");

                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura ödemesi başarıyla kaydedildi'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Geçersiz metod']);
        break;
}
