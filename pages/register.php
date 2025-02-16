<?php
// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="card-title mb-0">Hesap Oluştur</h4>
            </div>
            <div class="card-body">
                <form id="registerForm" method="POST" action="/api/auth?action=register">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               minlength="3" maxlength="50" pattern="[a-zA-Z0-9_-]+"
                               title="Sadece harf, rakam, tire ve alt çizgi kullanabilirsiniz">
                        <small class="form-text text-muted">
                            En az 3, en fazla 50 karakter. Sadece harf, rakam, tire ve alt çizgi kullanabilirsiniz.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="6" maxlength="50"
                               pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,}$"
                               title="En az 6 karakter, en az bir harf ve bir rakam içermelidir">
                        <small class="form-text text-muted">
                            En az 6 karakter, en az bir harf ve bir rakam içermelidir.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Şifre Tekrar</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <small class="form-text text-muted">
                            Şifrenizi tekrar girin.
                        </small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Kayıt Ol</button>
                        <a href="/login" class="btn btn-link">Giriş Yap</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate password match
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if(password !== confirmPassword) {
        alert('Şifreler eşleşmiyor');
        return;
    }

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            window.location.href = '/dashboard';
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Kayıt başarısız');
        }
    } catch(error) {
        console.error('Registration error:', error);
        alert('Kayıt olurken bir hata oluştu');
    }
});

// Real-time password validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const isValid = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,}$/.test(password);
    
    this.setCustomValidity(isValid ? '' : 'Şifre en az 6 karakter, en az bir harf ve bir rakam içermelidir');
});

// Real-time password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    this.setCustomValidity(password === confirmPassword ? '' : 'Şifreler eşleşmiyor');
});
</script>
