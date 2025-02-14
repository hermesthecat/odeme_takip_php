<?php
/**
 * @author A. Kerem Gök
 */

require_once 'includes/header.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-key"></i>
            <h1>Şifremi Unuttum</h1>
            <p>Şifre sıfırlama bağlantısı için e-posta adresinizi girin</p>
        </div>
        
        <form id="forgotPasswordForm" onsubmit="return handlePasswordReset(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    E-posta
                </label>
                <input type="email" id="email" name="email" required
                       placeholder="E-posta adresinizi girin">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i>
                    Sıfırlama Bağlantısı Gönder
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Şifrenizi hatırladınız mı?</p>
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
</script>

<?php require_once 'includes/footer.php'; ?> 