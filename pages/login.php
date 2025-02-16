<?php
session_start();
require_once __DIR__ . '/../app/helpers/functions.php';

// Check if already logged in
if(is_logged_in()) {
    redirect('/dashboard');
}
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="card-title mb-0">Giriş Yap</h4>
            </div>
            <div class="card-body">
                <form id="loginForm" method="POST" action="/api/auth?action=login">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                        <a href="/register" class="btn btn-link">Hesap Oluştur</a>
                        <a href="/reset-password" class="btn btn-link">Şifremi Unuttum</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

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
            Swal.fire({
                icon: 'error',
                title: 'Giriş Başarısız',
                text: data.errors ? data.errors.join('\n') : 'Giriş yapılırken bir hata oluştu',
                confirmButtonText: 'Tamam'
            });
        }
    } catch(error) {
        console.error('Login error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Giriş yapılırken bir hata oluştu',
            confirmButtonText: 'Tamam'
        });
    }
});
</script>
