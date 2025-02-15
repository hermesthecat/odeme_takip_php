/**
 * @author A. Kerem Gök
 * Kimlik doğrulama işlemleri
 */

// Oturum kontrolü
async function checkSession() {
    try {
        const response = await fetchAPI('/api/auth?action=check');
        if (!response.success) {
            window.location.href = '/login.php';
        }
    } catch (error) {
        console.error('Oturum kontrolü hatası:', error);
        // API hatası durumunda sessizce devam et
    }
}

// Giriş formu işleme
async function handleLogin(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);

        const response = await fetchAPI('/api/auth', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            window.location.href = '/index.php';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Giriş Başarısız',
                text: response.error || 'Kullanıcı adı veya şifre hatalı.',
                confirmButtonText: 'Tamam'
            });
        }
    } catch (error) {
        console.error('Giriş hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Giriş yapılırken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
    }
}

// Kayıt formu işleme
async function handleRegister(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);

        // Şifre kontrolü
        if (data.password !== data.password_confirm) {
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: 'Şifreler eşleşmiyor.',
                confirmButtonText: 'Tamam'
            });
            return;
        }

        const response = await fetchAPI('/api/auth', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Hesabınız oluşturuldu. Giriş yapabilirsiniz.',
                confirmButtonText: 'Tamam'
            }).then(() => {
                window.location.href = '/login.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Kayıt Başarısız',
                text: response.error || 'Kayıt olurken bir hata oluştu.',
                confirmButtonText: 'Tamam'
            });
        }
    } catch (error) {
        console.error('Kayıt hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Kayıt olurken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
    }
}

// Çıkış işlemi
async function handleLogout() {
    try {
        const response = await fetchAPI('/api/auth', {
            method: 'DELETE'
        });

        if (response.success) {
            window.location.href = '/login.php';
        }
    } catch (error) {
        console.error('Çıkış hatası:', error);
    }
}

// Şifre sıfırlama isteği
async function handlePasswordReset(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);

        const response = await fetchAPI('/api/auth', {
            method: 'PATCH',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
                confirmButtonText: 'Tamam'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: response.error || 'Şifre sıfırlama isteği gönderilemedi.',
                confirmButtonText: 'Tamam'
            });
        }
    } catch (error) {
        console.error('Şifre sıfırlama hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Şifre sıfırlama isteği gönderilirken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
    }
}

// Şifre değiştirme
async function handlePasswordChange(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);

        // Şifre kontrolü
        if (data.new_password !== data.new_password_confirm) {
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: 'Yeni şifreler eşleşmiyor.',
                confirmButtonText: 'Tamam'
            });
            return;
        }

        const response = await fetchAPI('/api/auth', {
            method: 'PATCH',
            body: JSON.stringify({
                ...data,
                action: 'change_password'
            })
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Şifreniz başarıyla değiştirildi.',
                confirmButtonText: 'Tamam'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: response.error || 'Şifre değiştirilemedi.',
                confirmButtonText: 'Tamam'
            });
        }
    } catch (error) {
        console.error('Şifre değiştirme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Şifre değiştirilirken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
    }
}

// Kullanıcı tercihlerini güncelleme
async function updateUserPreferences(preferences) {
    try {
        const response = await fetchAPI('/api/auth', {
            method: 'PATCH',
            body: JSON.stringify({
                action: 'update_preferences',
                preferences
            })
        });

        if (!response.success) {
            throw new Error(response.error || 'Tercihler güncellenemedi');
        }
    } catch (error) {
        console.error('Tercih güncelleme hatası:', error);
        throw error;
    }
}

// Sayfa yüklendiğinde oturum kontrolü yap (login sayfası hariç)
document.addEventListener('DOMContentLoaded', () => {
    const loginPages = ['/login.php', '/register.php', '/forgot-password.php', '/reset-password.php'];
    const currentPath = window.location.pathname;
    
    if (!loginPages.includes(currentPath)) {
        checkSession();
    }
});
