<?php

/**
 * @author A. Kerem Gök
 */

require_once 'includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="profile-page">
    <div class="page-header">
        <h1>Profil Bilgileri</h1>
    </div>

    <div class="profile-content">
        <!-- Profil Bilgileri -->
        <div class="profile-section">
            <h2>Kişisel Bilgiler</h2>
            <form id="profileForm" onsubmit="return handleProfileUpdate(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">
                            <i class="fas fa-user"></i>
                            Ad
                        </label>
                        <input type="text" id="first_name" name="first_name" required
                            value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user"></i>
                            Soyad
                        </label>
                        <input type="text" id="last_name" name="last_name" required
                            value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        E-posta
                    </label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-circle"></i>
                        Kullanıcı Adı
                    </label>
                    <input type="text" id="username" name="username" required
                        value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                        readonly>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Şifre Değiştirme -->
        <div class="profile-section">
            <h2>Şifre Değiştirme</h2>
            <form id="passwordForm" onsubmit="return handlePasswordChange(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-lock"></i>
                        Mevcut Şifre
                    </label>
                    <div class="password-input">
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i>
                        Yeni Şifre
                    </label>
                    <div class="password-input">
                        <input type="password" id="new_password" name="new_password" required
                            pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
                            title="En az 8 karakter, bir harf ve bir rakam içermelidir">
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password_confirm">
                        <i class="fas fa-key"></i>
                        Yeni Şifre Tekrar
                    </label>
                    <div class="password-input">
                        <input type="password" id="new_password_confirm" name="new_password_confirm" required>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-key"></i>
                        Şifreyi Değiştir
                    </button>
                </div>
            </form>
        </div>

        <!-- Hesap Silme -->
        <div class="profile-section danger-zone">
            <h2>Tehlikeli Bölge</h2>
            <div class="warning-box">
                <h3>Hesabı Sil</h3>
                <p>Bu işlem geri alınamaz. Tüm verileriniz kalıcı olarak silinecektir.</p>
                <button onclick="confirmDeleteAccount()" class="btn-danger">
                    <i class="fas fa-trash"></i>
                    Hesabı Sil
                </button>
            </div>
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
    document.getElementById('new_password_confirm').addEventListener('input', function() {
        const password = document.getElementById('new_password').value;
        if (this.value !== password) {
            this.setCustomValidity('Şifreler eşleşmiyor');
        } else {
            this.setCustomValidity('');
        }
    });

    // Hesap silme onayı
    function confirmDeleteAccount() {
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Bu işlem geri alınamaz! Tüm verileriniz kalıcı olarak silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, hesabımı sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                handleDeleteAccount();
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>