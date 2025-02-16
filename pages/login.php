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
            alert(data.errors ? data.errors.join('\n') : 'Giriş başarısız');
        }
    } catch(error) {
        console.error('Login error:', error);
        alert('Giriş yapılırken bir hata oluştu');
    }
});
</script>
