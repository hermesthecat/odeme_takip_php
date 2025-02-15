<?php

/**
 * @author A. Kerem Gök
 */

require_once 'includes/functions.php';

// Zaten oturum açıksa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

// CSRF token oluştur
$csrf_token = generateToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ödeme Takip Sistemi - Giriş">
    <meta name="author" content="A. Kerem Gök">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">

    <title>Giriş - Ödeme Takip Sistemi</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4CAF50">
    <link rel="apple-touch-icon" href="/assets/img/icon-192x192.png">

    <!-- Stil dosyaları -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Ana JavaScript dosyası -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/auth.js"></script>
</head>

<body data-theme="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light'; ?>">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-wallet"></i>
                <h1>Ödeme Takip</h1>
                <p>Finansal hayatınızı kontrol altına alın</p>
            </div>

            <form id="loginForm" onsubmit="return handleLogin(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Kullanıcı Adı
                    </label>
                    <input type="text" id="username" name="username" required
                        placeholder="Kullanıcı adınızı girin">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Şifre
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                            placeholder="Şifrenizi girin">
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Beni hatırla</span>
                    </label>
                    <a href="/forgot-password.php" class="forgot-password">
                        Şifremi unuttum
                    </a>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Giriş Yap
                    </button>
                </div>
            </form>

            <div class="auth-footer">
                <p>Hesabınız yok mu?</p>
                <a href="/register.php" class="btn-secondary btn-block">
                    <i class="fas fa-user-plus"></i>
                    Kayıt Ol
                </a>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

        // Şifre göster/gizle
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>
