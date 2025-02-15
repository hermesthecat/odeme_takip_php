<?php

/**
 * @author A. Kerem Gök
 * Raporlar API endpoint'i
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

// Rapor tipi ve tarih aralığı kontrolü
$report_type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Ay başı
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Ay sonu

// Rapor tipinin geçerliliğini kontrol et
$valid_types = json_decode(REPORT_TYPES, true);
if (!array_key_exists($report_type, $valid_types)) {
    http_response_code(400);
    die(json_encode(['error' => 'Geçersiz rapor tipi']));
}

// Tarih formatını kontrol et
if (!validateDateRange($start_date, $end_date)) {
    http_response_code(400);
    die(json_encode(['error' => 'Geçersiz tarih aralığı']));
}

try {
    $response = ['success' => true, 'data' => []];

    switch ($report_type) {
        case 'overview':
            // Genel bakış raporu
            // Toplam gelir
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_income,
                       COUNT(*) as transaction_count,
                       currency
                FROM incomes 
                WHERE user_id = ? 
                AND income_date BETWEEN ? AND ?
                GROUP BY currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['income'] = $stmt->fetchAll();

            // Toplam gider
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_expense,
                       COUNT(*) as transaction_count,
                       currency
                FROM expenses 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['expense'] = $stmt->fetchAll();

            // Birikim hedefleri
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_goals,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_goals,
                    SUM(target_amount) as total_target,
                    SUM(current_amount) as total_saved,
                    currency
                FROM savings_goals 
                WHERE user_id = ? 
                AND target_date BETWEEN ? AND ?
                GROUP BY currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['savings'] = $stmt->fetchAll();

            // Fatura ödemeleri
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_bills,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_bills,
                    COALESCE(SUM(amount), 0) as total_amount,
                    currency
                FROM bill_reminders 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['bills'] = $stmt->fetchAll();
            break;

        case 'income':
            // Gelir analizi raporu
            // Kategori bazlı gelirler
            $stmt = $pdo->prepare("
                SELECT 
                    category,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM incomes 
                WHERE user_id = ? 
                AND income_date BETWEEN ? AND ?
                GROUP BY category, currency
                ORDER BY total DESC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_category'] = $stmt->fetchAll();

            // Aylık gelir trendi
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(income_date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM incomes 
                WHERE user_id = ? 
                AND income_date BETWEEN ? AND ?
                GROUP BY month, currency
                ORDER BY month
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['monthly_trend'] = $stmt->fetchAll();

            // Tekrarlanan gelirler
            $stmt = $pdo->prepare("
                SELECT 
                    rt.*,
                    COUNT(i.id) as occurrence_count,
                    COALESCE(SUM(i.amount), 0) as total_amount
                FROM recurring_transactions rt
                LEFT JOIN incomes i ON i.recurring_id = rt.id
                WHERE rt.user_id = ? 
                AND rt.type = 'income'
                AND rt.start_date BETWEEN ? AND ?
                GROUP BY rt.id
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['recurring'] = $stmt->fetchAll();
            break;

        case 'expense':
            // Gider analizi raporu
            // Kategori bazlı giderler
            $stmt = $pdo->prepare("
                SELECT 
                    category,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM expenses 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY category, currency
                ORDER BY total DESC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_category'] = $stmt->fetchAll();

            // Aylık gider trendi
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(due_date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM expenses 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY month, currency
                ORDER BY month
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['monthly_trend'] = $stmt->fetchAll();

            // Ödeme durumuna göre giderler
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM expenses 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY status, currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_status'] = $stmt->fetchAll();
            break;

        case 'savings':
            // Birikim analizi raporu
            // Hedef durumuna göre birikimler
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(target_amount) as total_target,
                    SUM(current_amount) as total_saved,
                    currency
                FROM savings_goals 
                WHERE user_id = ? 
                AND target_date BETWEEN ? AND ?
                GROUP BY status, currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_status'] = $stmt->fetchAll();

            // Öncelik seviyesine göre birikimler
            $stmt = $pdo->prepare("
                SELECT 
                    priority,
                    COUNT(*) as count,
                    SUM(target_amount) as total_target,
                    SUM(current_amount) as total_saved,
                    currency
                FROM savings_goals 
                WHERE user_id = ? 
                AND target_date BETWEEN ? AND ?
                GROUP BY priority, currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_priority'] = $stmt->fetchAll();
            break;

        case 'bills':
            // Fatura analizi raporu
            // Kategori bazlı faturalar
            $stmt = $pdo->prepare("
                SELECT 
                    category,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM bill_reminders 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY category, currency
                ORDER BY total DESC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_category'] = $stmt->fetchAll();

            // Tekrar aralığına göre faturalar
            $stmt = $pdo->prepare("
                SELECT 
                    repeat_interval,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    currency
                FROM bill_reminders 
                WHERE user_id = ? 
                AND due_date BETWEEN ? AND ?
                GROUP BY repeat_interval, currency
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['by_interval'] = $stmt->fetchAll();

            // Fatura ödemeleri
            $stmt = $pdo->prepare("
                SELECT 
                    br.title,
                    br.amount as bill_amount,
                    br.currency,
                    bp.payment_date,
                    bp.amount as paid_amount,
                    bp.payment_method
                FROM bill_reminders br
                LEFT JOIN bill_payments bp ON bp.bill_id = br.id
                WHERE br.user_id = ? 
                AND br.due_date BETWEEN ? AND ?
                ORDER BY bp.payment_date DESC
            ");
            $stmt->execute([$user_id, $start_date, $end_date]);
            $response['data']['payments'] = $stmt->fetchAll();
            break;
    }

    // Rapor meta verileri
    $response['data']['meta'] = [
        'type' => $report_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası']);
}
