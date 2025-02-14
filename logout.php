<?php

/**
 * @author A. Kerem Gök
 */

require_once 'includes/functions.php';

// Aktivite kaydı
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'Kullanıcı çıkış yaptı');
}

// Oturumu sonlandır
session_start();
session_destroy();

// Çerezleri temizle
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Tema çerezi hariç diğer çerezleri temizle
foreach ($_COOKIE as $key => $value) {
    if ($key !== 'theme') {
        setcookie($key, '', time() - 3600, '/');
    }
}

// Giriş sayfasına yönlendir
header('Location: /login.php');
exit;
