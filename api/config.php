<?php

/**
 * @author A. Kerem Gök
 * API yapılandırma dosyası
 */

// CORS ayarları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Karakter seti
ini_set('default_charset', 'UTF-8');

// Oturum ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// API sabitleri
define('API_VERSION', '1.0.0');
define('API_RATE_LIMIT', 100); // Dakikada maksimum istek sayısı
define('API_CACHE_TIME', 300); // 5 dakika

// Desteklenen para birimleri
define('SUPPORTED_CURRENCIES', json_encode([
    'TRY' => 'Türk Lirası',
    'USD' => 'Amerikan Doları',
    'EUR' => 'Euro',
    'GBP' => 'İngiliz Sterlini'
]));

// Kategori listeleri
define('INCOME_CATEGORIES', json_encode([
    'salary' => 'Maaş',
    'freelance' => 'Serbest Çalışma',
    'investment' => 'Yatırım',
    'other' => 'Diğer'
]));

define('EXPENSE_CATEGORIES', json_encode([
    'bills' => 'Faturalar',
    'rent' => 'Kira',
    'food' => 'Gıda',
    'transportation' => 'Ulaşım',
    'shopping' => 'Alışveriş',
    'health' => 'Sağlık',
    'education' => 'Eğitim',
    'entertainment' => 'Eğlence',
    'other' => 'Diğer'
]));

// Fatura tekrar aralıkları
define('BILL_REPEAT_INTERVALS', json_encode([
    'monthly' => 'Aylık',
    'quarterly' => '3 Aylık',
    'yearly' => 'Yıllık',
    'once' => 'Tek Seferlik'
]));

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
            // XML formatına çevir
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
            array_to_xml($data, $xml);
            return $xml->asXML();
        case 'csv':
            // CSV formatına çevir
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
    $categories = json_decode($type === 'income' ? INCOME_CATEGORIES : EXPENSE_CATEGORIES, true);
    return array_key_exists($category, $categories);
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
function checkRateLimit()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $cache_file = sys_get_temp_dir() . "/rate_limit_$ip.txt";
    
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
    $cache_file = sys_get_temp_dir() . "/cache_$key.txt";
    
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
    $cache_file = sys_get_temp_dir() . "/cache_$key.txt";
    $data = [
        'value' => $value,
        'timestamp' => time()
    ];
    file_put_contents($cache_file, json_encode($data));
}

// API isteği başlatma
session_start();
checkRateLimit();
