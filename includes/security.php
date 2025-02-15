<?php

/**
 * Güvenlik fonksiyonları
 */

// Güvenlik sabitleri
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_COOLDOWN_PERIOD', 900); // 15 dakika
define('SESSION_LIFETIME', 3600); // 1 saat
define('REMEMBER_ME_LIFETIME', 2592000); // 30 gün

/**
 * Güvenli session başlatma
 */
function initSecureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    session_start();
    
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Rate limiting kontrolü
 */
function checkRateLimit($ip, $action) {
    global $db;
    
    $timeWindow = 900; // 15 dakika
    $maxAttempts = [
        'login' => 10,
        'register' => 5,
        'reset-password' => 3
    ];
    
    $limit = $maxAttempts[$action] ?? 10;
    
    $stmt = $db->prepare('
        SELECT COUNT(*) 
        FROM activity_log 
        WHERE ip_address = ? 
        AND action = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ');
    $stmt->execute([$ip, $action, $timeWindow]);
    
    return $stmt->fetchColumn() < $limit;
}

/**
 * Input sanitization
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Remember me token oluşturma
 */
function generateRememberMeToken($userId) {
    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_DEFAULT);
    
    global $db;
    $stmt = $db->prepare('
        INSERT INTO remember_me_tokens (
            user_id, token_hash, expires_at
        ) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
    ');
    $stmt->execute([$userId, $hash, REMEMBER_ME_LIFETIME]);
    
    return $token;
}

/**
 * Remember me cookie ayarlama
 */
function setRememberMeCookie($token) {
    setcookie(
        'remember_me',
        $token,
        [
            'expires' => time() + REMEMBER_ME_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

/**
 * Güvenlik olaylarını loglama
 */
function logSecurityEvent($event, $details = []) {
    global $db;
    
    $stmt = $db->prepare('
        INSERT INTO activity_log (
            user_id, action, entity_type,
            entity_id, details, ip_address,
            user_agent
        ) VALUES (?, ?, "security", 0, ?, ?, ?)
    ');
    
    $stmt->execute([
        $details['user_id'] ?? null,
        $event,
        json_encode($details),
        $details['ip'] ?? $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * CSRF token oluşturma ve doğrulama
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('Geçersiz CSRF token');
    }
    return true;
}

/**
 * Şifre karmaşıklığı kontrolü
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        throw new Exception('Şifre en az 8 karakter olmalıdır');
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        throw new Exception('Şifre en az bir büyük harf içermelidir');
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        throw new Exception('Şifre en az bir küçük harf içermelidir');
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        throw new Exception('Şifre en az bir rakam içermelidir');
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        throw new Exception('Şifre en az bir özel karakter içermelidir');
    }
    
    return true;
} 