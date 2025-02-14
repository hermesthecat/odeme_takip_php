<?php
/**
 * @author A. Kerem Gök
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

// CSRF token oluştur
$csrf_token = generateToken();

if (!isLoggedIn()) {
    header('Location: /pages/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kişisel finans takip sistemi">
    <meta name="author" content="A. Kerem Gök">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <title>Ödeme Takip Sistemi</title>
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4CAF50">
    <link rel="apple-touch-icon" href="/assets/img/icon-192x192.png">
    
    <!-- Stil dosyaları -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Ana JavaScript dosyası -->
    <script src="/assets/js/main.js"></script>
    
    <?php if (isLoggedIn()): ?>
        <!-- Sayfa özel JavaScript dosyaları -->
        <script src="/assets/js/auth.js"></script>
        <script src="/assets/js/charts.js"></script>
        
        <?php
        // Mevcut sayfaya göre JavaScript dosyalarını yükle
        $current_page = basename($_SERVER['PHP_SELF'], '.php');
        switch ($current_page) {
            case 'dashboard':
                echo '<script src="/assets/js/dashboard.js"></script>';
                break;
            case 'income':
                echo '<script src="/assets/js/income.js"></script>';
                break;
            case 'expenses':
                echo '<script src="/assets/js/expense.js"></script>';
                break;
            case 'savings':
                echo '<script src="/assets/js/savings.js"></script>';
                break;
            case 'bills':
                echo '<script src="/assets/js/bills.js"></script>';
                break;
            case 'reports':
                echo '<script src="/assets/js/reports.js"></script>';
                break;
        }
        ?>
    <?php endif; ?>
</head>
<body data-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
    <!-- Mobil Menü Butonu -->
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Ana Navigasyon -->
    <nav class="navbar">
        <div class="brand">
            <a href="/">
                <i class="fas fa-wallet"></i>
                Ödeme Takip
            </a>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <ul class="nav-menu">
                <li><a href="/"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                <li><a href="/pages/income.php"><i class="fas fa-plus-circle"></i> Gelirler</a></li>
                <li><a href="/pages/expenses.php"><i class="fas fa-minus-circle"></i> Giderler</a></li>
                <li><a href="/pages/savings.php"><i class="fas fa-piggy-bank"></i> Birikimler</a></li>
                <li><a href="/pages/bills.php"><i class="fas fa-file-invoice"></i> Faturalar</a></li>
                <li><a href="/pages/reports.php"><i class="fas fa-chart-line"></i> Raporlar</a></li>
            </ul>
            
            <div class="nav-right">
                <!-- Bildirim Butonu -->
                <button id="notificationButton" class="btn-icon" title="Bildirimler">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" style="display: none;">0</span>
                </button>
                
                <!-- Tema Değiştirme -->
                <button id="themeToggle" class="btn-icon" title="Temayı Değiştir">
                    <i class="fas fa-moon"></i>
                </button>
                
                <!-- Kullanıcı Menüsü -->
                <div class="user-menu">
                    <button class="btn-icon" title="Kullanıcı Menüsü">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="/pages/settings/profile.php">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a href="/pages/settings/settings.php">
                            <i class="fas fa-cog"></i> Ayarlar
                        </a>
                        <a href="/pages/auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Çıkış
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </nav>
    
    <!-- Bildirim Alanı -->
    <div id="notificationArea" class="notification-area" style="display: none;">
        <div class="notification-header">
            <h3>Bildirimler</h3>
            <button class="btn-icon" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i>
            </button>
        </div>
        <div class="notification-list">
            <!-- Bildirimler JavaScript ile doldurulacak -->
        </div>
    </div>
    
    <!-- Ana İçerik Alanı -->
    <main class="container"><?php echo "\n"; ?> 