<?php

/**
 * Uygulama yapılandırma dosyası
 */

// Uygulama sabitleri
define('APP_NAME', 'Kişisel Finans Yönetimi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://butce.local');

// Güvenlik ayarları
define('ALLOWED_ORIGIN', 'https://butce.local');
define('API_RATE_LIMIT', 100);
define('API_CACHE_TIME', 300);

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'odeme_takip');
define('DB_USER', 'user');
define('DB_PASS', 'password');
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
define('CACHE_DRIVER', 'file'); // file, redis, memcached
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

// Fatura kategorileri
define('BILL_CATEGORIES', json_encode([
    'utilities' => 'Faturalar',
    'rent' => 'Kira',
    'insurance' => 'Sigorta',
    'subscription' => 'Abonelikler',
    'other' => 'Diğer'
]));

// Tekrarlama aralıkları
define('BILL_REPEAT_INTERVALS', json_encode([
    'daily' => 'Günlük',
    'weekly' => 'Haftalık',
    'monthly' => 'Aylık',
    'quarterly' => 'Üç Aylık',
    'yearly' => 'Yıllık'
]));

// Varsayılan sistem ayarları
define('DEFAULT_BILL_NOTIFICATION_DAYS', 3);
define('DEFAULT_BILL_REPEAT_INTERVAL', 'monthly');
define('DEFAULT_BILL_STATUS', 'active');

// Sistem sabitleri
define('VALID_BILL_INTERVALS', json_encode([
    'daily',
    'weekly',
    'monthly',
    'quarterly',
    'yearly'
]));

// Varsayılan kategori renkleri
define('DEFAULT_CATEGORY_COLORS', json_encode([
    'utilities' => '#F44336',
    'rent' => '#2196F3',
    'insurance' => '#4CAF50',
    'subscription' => '#FFC107',
    'other' => '#9E9E9E'
]));
