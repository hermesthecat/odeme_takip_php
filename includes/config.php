<?php

/**
 * @author A. Kerem Gök
 * Uygulama yapılandırma dosyası
 */

// Uygulama sabitleri
define('APP_NAME', 'Kişisel Finans Yönetimi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://butce.local');

// CORS ve Güvenlik ayarları
define('ALLOWED_ORIGIN', 'https://butce.local');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi ve karakter seti
date_default_timezone_set('Europe/Istanbul');
ini_set('default_charset', 'UTF-8');

// Oturum ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// API ayarları
define('API_VERSION', '1.0.0');
define('API_RATE_LIMIT', 100);
define('API_CACHE_TIME', 300);

// Veritabanı ayarları
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'odeme_takip');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Email ayarları
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@example.com');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_FROM_ADDRESS', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

// Döviz kuru API ayarları
define('EXCHANGE_RATE_API_KEY', 'your-api-key');
define('EXCHANGE_RATE_API_URL', 'https://api.exchangerate.host');

// Desteklenen para birimleri
define('SUPPORTED_CURRENCIES', json_encode([
    'TRY' => 'Türk Lirası',
    'USD' => 'Amerikan Doları',
    'EUR' => 'Euro',
    'GBP' => 'İngiliz Sterlini'
]));

// Bildirim ayarları
define('NOTIFICATION_CHANNELS', json_encode([
    'email' => true,
    'sms' => false,
    'push' => false
]));

// Dosya yükleme ayarları
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', json_encode([
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
]));

// Cache ayarları
define('CACHE_DRIVER', 'file');
define('CACHE_PREFIX', 'butce_');
define('CACHE_PATH', __DIR__ . '/../cache');

// Loglama ayarları
define('LOG_CHANNEL', 'file');
define('LOG_LEVEL', 'debug');
define('LOG_PATH', __DIR__ . '/../logs');

// Varsayılan ayarlar
define('DEFAULT_CURRENCY', 'TRY');
define('DEFAULT_LANGUAGE', 'tr');
define('DEFAULT_TIMEZONE', 'Europe/Istanbul');
define('DEFAULT_DATE_FORMAT', 'Y-m-d');
define('DEFAULT_TIME_FORMAT', 'H:i:s');

// Fatura tekrar aralıkları
define('BILL_REPEAT_INTERVALS', json_encode([
    'daily' => 'Günlük',
    'weekly' => 'Haftalık',
    'monthly' => 'Aylık',
    'quarterly' => 'Üç Aylık',
    'yearly' => 'Yıllık'
]));

define('DEFAULT_BILL_NOTIFICATION_DAYS', 3);
define('DEFAULT_BILL_REPEAT_INTERVAL', 'monthly');
define('DEFAULT_BILL_STATUS', 'active');

// Rapor tipleri
define('REPORT_TYPES', json_encode([
    'overview' => 'Genel Bakış',
    'income' => 'Gelir Analizi',
    'expense' => 'Gider Analizi',
    'savings' => 'Birikim Analizi',
    'bills' => 'Fatura Analizi'
]));

// Tarih aralıkları
define('DATE_RANGES', json_encode([
    'this-month' => 'Bu Ay',
    'last-month' => 'Geçen Ay',
    'last-3-months' => 'Son 3 Ay',
    'last-6-months' => 'Son 6 Ay',
    'this-year' => 'Bu Yıl',
    'last-year' => 'Geçen Yıl',
    'custom' => 'Özel Aralık'
]));

// API yanıt formatları
define('RESPONSE_FORMATS', json_encode([
    'json' => 'application/json',
    'xml' => 'application/xml',
    'csv' => 'text/csv',
    'pdf' => 'application/pdf',
    'excel' => 'application/vnd.ms-excel'
]));

// Yardımcı fonksiyonlar
function getErrorMessage($code)
{
    $errors = [
        400 => 'Geçersiz istek',
        401 => 'Yetkilendirme gerekli',
        403 => 'Erişim reddedildi',
        404 => 'Kaynak bulunamadı',
        405 => 'Geçersiz metod',
        429 => 'Çok fazla istek',
        500 => 'Sunucu hatası',
        503 => 'Servis kullanılamıyor'
    ];
    return $errors[$code] ?? 'Bilinmeyen hata';
}

function formatResponse($data, $format = 'json')
{
    switch ($format) {
        case 'json':
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        case 'xml':
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
            array_to_xml($data, $xml);
            return $xml->asXML();
        case 'csv':
            $output = fopen('php://temp', 'r+');
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            return $csv;
        default:
            return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

function array_to_xml($data, &$xml)
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateCurrency($currency)
{
    $supported = json_decode(SUPPORTED_CURRENCIES, true);
    return array_key_exists($currency, $supported);
}

function validateCategory($category, $type = 'expense')
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND type = :type");
    $stmt->execute(['name' => $category, 'type' => $type]);
    return $stmt->fetchColumn() > 0;
}

function validateAmount($amount)
{
    return is_numeric($amount) && $amount >= 0;
}

function validateDateRange($start, $end)
{
    if (!validateDate($start) || !validateDate($end)) {
        return false;
    }
    return strtotime($start) <= strtotime($end);
}

function sanitizeOutput($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeOutput($value);
        }
    } else {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// API rate limiting
function checkApiRateLimit()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $cache_file = CACHE_PATH . "/rate_limit_$ip.txt";

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data['count'] >= API_RATE_LIMIT) {
            if (time() - $data['timestamp'] < 60) {
                http_response_code(429);
                die(json_encode(['error' => getErrorMessage(429)]));
            } else {
                $data = ['count' => 1, 'timestamp' => time()];
            }
        } else {
            $data['count']++;
        }
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }

    file_put_contents($cache_file, json_encode($data));
}

// API önbellek kontrolü
function checkCache($key)
{
    $cache_file = CACHE_PATH . "/$key.txt";

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if (time() - $data['timestamp'] < API_CACHE_TIME) {
            return $data['value'];
        }
    }
    return null;
}

function setCache($key, $value)
{
    $cache_file = CACHE_PATH . "/$key.txt";
    $data = [
        'value' => $value,
        'timestamp' => time()
    ];
    file_put_contents($cache_file, json_encode($data));
}

// Session ayarları
session_name('butce_session');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// Oturumu başlat
session_start();
