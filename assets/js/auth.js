/**
 * @author A. Kerem Gök
 * Kimlik doğrulama işlemleri
 */

// Oturum kontrolü
async function checkSession() {
    try {
        const response = await fetchAPI('/api/auth?action=check');
        if (!response.status) {
            window.location.href = '/login.php';
        }
    } catch (error) {
        console.error('Oturum kontrolü hatası:', error);
    }
}

// Giriş formu işleme
async function handleLogin(event) {
    event.preventDefault();
    event.stopPropagation();

    try {
        const form = event.target;
        const data = formDataToJSON(form);

        const response = await fetchAPI('/api/auth', {
            method: 'POST',
            body: JSON.stringify({
                ...data,
                action: 'login'
            })
        });

        if (response.status) {
            window.location.assign('/index.php');
            return false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Giriş Başarısız',
                text: response.message || 'Kullanıcı adı veya şifre hatalı.',
                confirmButtonText: 'Tamam'
            });
            return false;
        }
    } catch (error) {
        console.error('Giriş hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Giriş yapılırken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
        return false;
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
            body: JSON.stringify({
                ...data,
                action: 'register'
            })
        });

        if (response.status) {
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
                text: response.message || 'Kayıt olurken bir hata oluştu.',
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
            method: 'DELETE',
            body: JSON.stringify({ action: 'logout' })
        });

        if (response.status) {
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
            body: JSON.stringify({
                ...data,
                action: 'reset-password'
            })
        });

        if (response.status) {
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
                text: response.message || 'Şifre sıfırlama isteği gönderilemedi.',
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

        if (response.status) {
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
                text: response.message || 'Şifre değiştirilemedi.',
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

        if (!response.status) {
            throw new Error(response.message || 'Tercihler güncellenemedi');
        }
    } catch (error) {
        console.error('Tercih güncelleme hatası:', error);
        throw error;
    }
}
