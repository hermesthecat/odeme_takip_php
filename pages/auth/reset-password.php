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

// Token kontrolü
if (!isset($_GET['token'])) {
    header('Location: /login.php');
    exit;
}

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-lock"></i>
            <h1>Şifre Sıfırlama</h1>
            <p>Yeni şifrenizi belirleyin</p>
        </div>
        
        <form id="resetPasswordForm" onsubmit="return handlePasswordChange(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Yeni Şifre
                </label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required
                           pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
                           title="En az 8 karakter, bir harf ve bir rakam içermelidir"
                           placeholder="Yeni şifrenizi girin">
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
                           placeholder="Yeni şifrenizi tekrar girin">
                    <button type="button" class="toggle-password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-block">
                    <i class="fas fa-save"></i>
                    Şifreyi Değiştir
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

<?php require_once 'includes/footer.php'; ?> 