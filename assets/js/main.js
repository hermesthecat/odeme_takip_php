/**
 * @author A. Kerem Gök
 */

// Ajax istekleri için yardımcı fonksiyon
async function fetchAPI(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        credentials: 'include', // Add this to send cookies
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
        }
    };

    try {
        const response = await fetch(url, { ...defaultOptions, ...options });
        if (!response.ok) throw new Error('Network response was not ok');
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'İşlem sırasında bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
        throw error;
    }
}

// Tema değiştirme
document.getElementById('themeToggle')?.addEventListener('click', async function () {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    document.body.setAttribute('data-theme', newTheme);
    document.cookie = `theme=${newTheme};path=/;max-age=31536000`; // 1 yıl

    try {
        await fetchAPI('/api/user-preferences', {
            method: 'POST',
            body: JSON.stringify({ theme: newTheme })
        });
    } catch (error) {
        console.error('Tema kaydedilemedi:', error);
    }
});

// Para formatı
function formatMoney(amount, currency = 'TRY') {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Tarih formatı
function formatDate(date) {
    return new Intl.DateTimeFormat('tr-TR').format(new Date(date));
}

// Form verilerini JSON'a çevirme
function formDataToJSON(formElement) {
    const formData = new FormData(formElement);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    return data;
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function () {
    // Offline/Online durumu kontrolü
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
});

// Online/Offline durumu güncelleme
function updateOnlineStatus() {
    const status = navigator.onLine ? 'online' : 'offline';
    document.body.setAttribute('data-connection', status);

    if (!navigator.onLine) {
        Swal.fire({
            icon: 'warning',
            title: 'Çevrimdışı Mod',
            text: 'İnternet bağlantınız kesildi. Verileriniz çevrimiçi olduğunuzda senkronize edilecek.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}
