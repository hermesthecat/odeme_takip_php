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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            // Filtre parametrelerini al
            $type = $_GET['type'] ?? 'overview';
            $date_range = $_GET['date_range'] ?? 'this-month';
            $currency = $_GET['currency'] ?? 'TRY';

            // Tarih aralığını hesapla
            $dates = calculateDateRange($date_range, $_GET['start_date'] ?? null, $_GET['end_date'] ?? null);
            $start_date = $dates['start'];
            $end_date = $dates['end'];

            // Rapor verilerini hazırla
            $data = [];

            // Özet verileri
            $summary = getSummaryData($user_id, $start_date, $end_date, $currency);
            $data['summary'] = $summary;

            // Ana grafik verileri
            $data['main_chart'] = getMainChartData($user_id, $type, $start_date, $end_date, $currency);

            // Kategori dağılımı
            $data['category_chart'] = getCategoryChartData($user_id, $type, $start_date, $end_date, $currency);

            // Trend verileri
            $data['trend_chart'] = getTrendChartData($user_id, $type, $start_date, $end_date, $currency);

            // Detay tablosu
            $data['details'] = getDetailsData($user_id, $type, $start_date, $end_date, $currency);

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Veritabanı hatası']);
        }
        break;

    case 'POST':
        // Özel rapor oluştur
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            // Özel rapor verilerini hazırla
            $report_data = generateCustomReport(
                $user_id,
                $data['components'] ?? [],
                $data['charts'] ?? [],
                $data['grouping'] ?? 'monthly',
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['currency'] ?? 'TRY'
            );

            echo json_encode([
                'success' => true,
                'data' => $report_data
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

// Yardımcı fonksiyonlar
function calculateDateRange($range, $start = null, $end = null)
{
    $now = new DateTime();
    $start_date = new DateTime();
    $end_date = new DateTime();

    switch ($range) {
        case 'this-month':
            $start_date->modify('first day of this month');
            $end_date->modify('last day of this month');
            break;
        case 'last-month':
            $start_date->modify('first day of last month');
            $end_date->modify('last day of last month');
            break;
        case 'last-3-months':
            $start_date->modify('-3 months');
            break;
        case 'last-6-months':
            $start_date->modify('-6 months');
            break;
        case 'this-year':
            $start_date->modify('first day of january this year');
            $end_date->modify('last day of december this year');
            break;
        case 'last-year':
            $start_date->modify('first day of january last year');
            $end_date->modify('last day of december last year');
            break;
        case 'custom':
            if ($start && $end) {
                $start_date = new DateTime($start);
                $end_date = new DateTime($end);
            }
            break;
    }

    return [
        'start' => $start_date->format('Y-m-d'),
        'end' => $end_date->format('Y-m-d')
    ];
}

function getSummaryData($user_id, $start_date, $end_date, $currency)
{
    global $pdo;

    // Gelir toplamı
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM incomes 
        WHERE user_id = ? 
        AND income_date BETWEEN ? AND ?
        AND currency = ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date, $currency]);
    $total_income = $stmt->fetch()['total'];

    // Gider toplamı
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM expenses 
        WHERE user_id = ? 
        AND due_date BETWEEN ? AND ?
        AND currency = ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date, $currency]);
    $total_expense = $stmt->fetch()['total'];

    // Net bakiye
    $net_balance = $total_income - $total_expense;

    // Birikim oranı
    $savings_rate = $total_income > 0 ? (($total_income - $total_expense) / $total_income) * 100 : 0;

    // Trendleri hesapla
    $prev_start = (new DateTime($start_date))->modify('-1 month')->format('Y-m-d');
    $prev_end = (new DateTime($end_date))->modify('-1 month')->format('Y-m-d');

    // Önceki dönem gelir
    $stmt->execute([$user_id, $prev_start, $prev_end, $currency]);
    $prev_income = $stmt->fetch()['total'];
    $income_trend = $prev_income > 0 ? (($total_income - $prev_income) / $prev_income) * 100 : 0;

    // Önceki dönem gider
    $stmt->execute([$user_id, $prev_start, $prev_end, $currency]);
    $prev_expense = $stmt->fetch()['total'];
    $expense_trend = $prev_expense > 0 ? (($total_expense - $prev_expense) / $prev_expense) * 100 : 0;

    return [
        'total_income' => $total_income,
        'total_expense' => $total_expense,
        'net_balance' => $net_balance,
        'savings_rate' => $savings_rate,
        'income_trend' => $income_trend,
        'expense_trend' => $expense_trend,
        'balance_trend' => $income_trend - $expense_trend,
        'savings_trend' => $savings_rate,
        'currency' => $currency
    ];
}

function getMainChartData($user_id, $type, $start_date, $end_date, $currency)
{
    global $pdo;

    switch ($type) {
        case 'overview':
            // Aylık gelir/gider grafiği
            $data = [
                'labels' => [],
                'income' => [],
                'expense' => [],
                'net' => []
            ];

            $current = new DateTime($start_date);
            $end = new DateTime($end_date);

            while ($current <= $end) {
                $month = $current->format('Y-m');
                $data['labels'][] = $current->format('F Y');

                // Gelirler
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total
                    FROM incomes 
                    WHERE user_id = ? 
                    AND DATE_FORMAT(income_date, '%Y-%m') = ?
                    AND currency = ?
                ");
                $stmt->execute([$user_id, $month, $currency]);
                $income = $stmt->fetch()['total'];
                $data['income'][] = $income;

                // Giderler
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total
                    FROM expenses 
                    WHERE user_id = ? 
                    AND DATE_FORMAT(due_date, '%Y-%m') = ?
                    AND currency = ?
                ");
                $stmt->execute([$user_id, $month, $currency]);
                $expense = $stmt->fetch()['total'];
                $data['expense'][] = $expense;

                // Net
                $data['net'][] = $income - $expense;

                $current->modify('+1 month');
            }

            return $data;

        case 'income':
        case 'expense':
            $table = $type === 'income' ? 'incomes' : 'expenses';
            $date_field = $type === 'income' ? 'income_date' : 'due_date';

            $stmt = $pdo->prepare("
                SELECT DATE_FORMAT($date_field, '%Y-%m') as month,
                       COALESCE(SUM(amount), 0) as total
                FROM $table
                WHERE user_id = ?
                AND $date_field BETWEEN ? AND ?
                AND currency = ?
                GROUP BY month
                ORDER BY month
            ");
            $stmt->execute([$user_id, $start_date, $end_date, $currency]);

            $data = [
                'labels' => [],
                'values' => []
            ];

            while ($row = $stmt->fetch()) {
                $date = DateTime::createFromFormat('Y-m', $row['month']);
                $data['labels'][] = $date->format('F Y');
                $data['values'][] = $row['total'];
            }

            return $data;

        case 'savings':
            $stmt = $pdo->prepare("
                SELECT target_date, current_amount
                FROM savings_goals
                WHERE user_id = ?
                AND target_date BETWEEN ? AND ?
                AND currency = ?
                ORDER BY target_date
            ");
            $stmt->execute([$user_id, $start_date, $end_date, $currency]);

            $data = [
                'labels' => [],
                'values' => []
            ];

            while ($row = $stmt->fetch()) {
                $date = new DateTime($row['target_date']);
                $data['labels'][] = $date->format('F Y');
                $data['values'][] = $row['current_amount'];
            }

            return $data;

        case 'bills':
            $stmt = $pdo->prepare("
                SELECT due_date, amount
                FROM bill_reminders
                WHERE user_id = ?
                AND due_date BETWEEN ? AND ?
                AND currency = ?
                ORDER BY due_date
            ");
            $stmt->execute([$user_id, $start_date, $end_date, $currency]);

            $data = [
                'labels' => [],
                'values' => []
            ];

            while ($row = $stmt->fetch()) {
                $date = new DateTime($row['due_date']);
                $data['labels'][] = $date->format('F Y');
                $data['values'][] = $row['amount'];
            }

            return $data;
    }
}

function getCategoryChartData($user_id, $type, $start_date, $end_date, $currency)
{
    global $pdo;

    $table = $type === 'income' ? 'incomes' : 'expenses';
    $date_field = $type === 'income' ? 'income_date' : 'due_date';

    $stmt = $pdo->prepare("
        SELECT category, COALESCE(SUM(amount), 0) as total
        FROM $table
        WHERE user_id = ?
        AND $date_field BETWEEN ? AND ?
        AND currency = ?
        GROUP BY category
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date, $currency]);

    $data = [
        'labels' => [],
        'values' => []
    ];

    while ($row = $stmt->fetch()) {
        $data['labels'][] = $row['category'];
        $data['values'][] = $row['total'];
    }

    return $data;
}

function getTrendChartData($user_id, $type, $start_date, $end_date, $currency)
{
    global $pdo;

    $table = '';
    $date_field = '';

    switch ($type) {
        case 'income':
            $table = 'incomes';
            $date_field = 'income_date';
            break;
        case 'expense':
            $table = 'expenses';
            $date_field = 'due_date';
            break;
        case 'savings':
            $table = 'savings_goals';
            $date_field = 'target_date';
            break;
        case 'bills':
            $table = 'bill_reminders';
            $date_field = 'due_date';
            break;
        default:
            return [
                'labels' => [],
                'values' => []
            ];
    }

    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT($date_field, '%Y-%m') as month,
               COALESCE(SUM(amount), 0) as total
        FROM $table
        WHERE user_id = ?
        AND $date_field BETWEEN ? AND ?
        AND currency = ?
        GROUP BY month
        ORDER BY month
    ");
    $stmt->execute([$user_id, $start_date, $end_date, $currency]);

    $data = [
        'labels' => [],
        'values' => []
    ];

    while ($row = $stmt->fetch()) {
        $date = DateTime::createFromFormat('Y-m', $row['month']);
        $data['labels'][] = $date->format('F Y');
        $data['values'][] = $row['total'];
    }

    return $data;
}

function getDetailsData($user_id, $type, $start_date, $end_date, $currency)
{
    global $pdo;

    $details = [];

    if ($type === 'overview' || $type === 'income') {
        $stmt = $pdo->prepare("
            SELECT 'income' as type,
                   income_date as date,
                   description,
                   category,
                   amount,
                   currency
            FROM incomes
            WHERE user_id = ?
            AND income_date BETWEEN ? AND ?
            AND currency = ?
        ");
        $stmt->execute([$user_id, $start_date, $end_date, $currency]);
        $details = array_merge($details, $stmt->fetchAll());
    }

    if ($type === 'overview' || $type === 'expense') {
        $stmt = $pdo->prepare("
            SELECT 'expense' as type,
                   due_date as date,
                   description,
                   category,
                   amount,
                   currency
            FROM expenses
            WHERE user_id = ?
            AND due_date BETWEEN ? AND ?
            AND currency = ?
        ");
        $stmt->execute([$user_id, $start_date, $end_date, $currency]);
        $details = array_merge($details, $stmt->fetchAll());
    }

    // Tarihe göre sırala
    usort($details, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    return $details;
}

function generateCustomReport($user_id, $components, $charts, $grouping, $start_date, $end_date, $currency)
{
    $data = [
        'main_chart' => [],
        'category_chart' => [],
        'trend_chart' => [],
        'details' => []
    ];

    // Tarih aralığını belirle
    if (!$start_date || !$end_date) {
        $dates = calculateDateRange('this-month');
        $start_date = $dates['start'];
        $end_date = $dates['end'];
    }

    // Seçilen bileşenlere göre verileri topla
    foreach ($components as $component) {
        switch ($component) {
            case 'income':
                $data['main_chart'] = array_merge(
                    $data['main_chart'],
                    getMainChartData($user_id, 'income', $start_date, $end_date, $currency)
                );
                break;
            case 'expense':
                $data['main_chart'] = array_merge(
                    $data['main_chart'],
                    getMainChartData($user_id, 'expense', $start_date, $end_date, $currency)
                );
                break;
            case 'savings':
                $data['main_chart'] = array_merge(
                    $data['main_chart'],
                    getMainChartData($user_id, 'savings', $start_date, $end_date, $currency)
                );
                break;
            case 'bills':
                $data['main_chart'] = array_merge(
                    $data['main_chart'],
                    getMainChartData($user_id, 'bills', $start_date, $end_date, $currency)
                );
                break;
        }
    }

    // Kategori dağılımı
    if (in_array('pie', $charts)) {
        $data['category_chart'] = getCategoryChartData($user_id, 'expense', $start_date, $end_date, $currency);
    }

    // Trend grafiği
    if (in_array('line', $charts)) {
        $data['trend_chart'] = getTrendChartData($user_id, 'overview', $start_date, $end_date, $currency);
    }

    // Detay verileri
    $data['details'] = getDetailsData($user_id, 'overview', $start_date, $end_date, $currency);

    return $data;
}
