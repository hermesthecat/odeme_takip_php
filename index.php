<?php
require_once __DIR__ . '/app/config/config.php';

// Start or resume session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Set headers
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Check if it's an API request
if(strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    // API requests should be handled by their respective endpoints
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'errors' => ['Invalid API endpoint']
    ]);
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get requested path
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$path = empty($path) ? 'index' : $path;

// Define allowed pages
$allowedPages = [
    'index',
    'login',
    'register',
    'dashboard',
    'income',
    'expense',
    'savings',
    'budget',
    'reminders',
    'profile',
    'settings'
];

// Validate requested page
$page = in_array($path, $allowedPages) ? $path : '404';

// Check authentication requirements
if(!$isLoggedIn && !in_array($page, ['index', 'login', 'register'])) {
    // Redirect to login page
    header('Location: /login');
    exit;
}

if($isLoggedIn && in_array($page, ['login', 'register'])) {
    // Redirect to dashboard
    header('Location: /dashboard');
    exit;
}

// Load the HTML template
$pageTitle = ucfirst($page);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bütçe Kontrol - <?php echo $pageTitle; ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="Bütçe Kontrol Sistemi">
    <meta name="keywords" content="bütçe, kontrol, finans, gelir, gider, tasarruf">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if(file_exists(__DIR__ . "/assets/css/{$page}.css")): ?>
    <link rel="stylesheet" href="/assets/css/<?php echo $page; ?>.css">
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/chart.min.js"></script>
    <script src="/assets/js/app.js"></script>
</head>
<body>
    <!-- Navigation -->
    <?php if($isLoggedIn): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">Bütçe Kontrol</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/income">Gelirler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/expense">Giderler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/savings">Birikimler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/budget">Bütçe</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/reminders">Hatırlatıcılar</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/profile">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/settings">Ayarlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/api/auth?action=logout">Çıkış</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container my-4">
        <?php
        // Load page content
        $pageFile = __DIR__ . "/pages/{$page}.php";
        if(file_exists($pageFile)) {
            require_once $pageFile;
        } else {
            echo '<div class="alert alert-danger">Sayfa bulunamadı!</div>';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© 2025 Bütçe Kontrol Sistemi</span>
        </div>
    </footer>

    <!-- Page specific scripts -->
    <?php if(file_exists(__DIR__ . "/assets/js/{$page}.js")): ?>
    <script src="/assets/js/<?php echo $page; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
