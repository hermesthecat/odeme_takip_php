<?php

/**
 * @author A. Kerem Gök
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
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
    <meta name="description" content="Ödeme Takip Sistemi - Kayıt">
    <meta name="author" content="A. Kerem Gök">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">

    <title>Kayıt Ol - Ödeme Takip Sistemi</title>

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

            <form id="registerForm" onsubmit="return handleRegister(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">
                            <i class="fas fa-user"></i>
                            Ad
                        </label>
                        <input type="text" id="first_name" name="first_name" required
                            placeholder="Adınız">
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user"></i>
                            Soyad
                        </label>
                        <input type="text" id="last_name" name="last_name" required
                            placeholder="Soyadınız">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        E-posta
                    </label>
                    <input type="email" id="email" name="email" required
                        placeholder="E-posta adresiniz">
                </div>

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-circle"></i>
                        Kullanıcı Adı
                    </label>
                    <input type="text" id="username" name="username" required
                        pattern="[a-zA-Z0-9_-]{3,20}"
                        title="3-20 karakter arası, harf, rakam, tire ve alt çizgi kullanabilirsiniz"
                        placeholder="Kullanıcı adınız">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Şifre
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                            pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
                            title="En az 8 karakter, bir harf ve bir rakam içermelidir"
                            placeholder="Şifreniz">
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">
                        <i class="fas fa-lock"></i>
                        Şifre Tekrar
                    </label>
                    <div class="password-input">
                        <input type="password" id="password_confirm" name="password_confirm" required
                            placeholder="Şifrenizi tekrar girin">
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>
                            <a href="/terms.php" target="_blank">Kullanım koşullarını</a> ve
                            <a href="/privacy.php" target="_blank">gizlilik politikasını</a> kabul ediyorum
                        </span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-block">
                        <i class="fas fa-user-plus"></i>
                        Kayıt Ol
                    </button>
                </div>
            </form>

            <div class="auth-footer">
                <p>Zaten hesabınız var mı?</p>
                <a href="/login.php" class="btn-secondary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </a>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

        // Şifre göster/gizle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
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
        });

        // Şifre eşleşme kontrolü
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>

</html>