<?php

/**
 * @author A. Kerem Gök
 */

require_once 'includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="settings-page">
    <div class="page-header">
        <h1>Ayarlar</h1>
    </div>

    <div class="settings-content">
        <!-- Görünüm Ayarları -->
        <div class="settings-section">
            <h2>Görünüm</h2>
            <form id="appearanceForm" onsubmit="return handleAppearanceUpdate(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="theme">
                        <i class="fas fa-palette"></i>
                        Tema
                    </label>
                    <select id="theme" name="theme">
                        <option value="light" <?php echo ($_COOKIE['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>
                            Açık Tema
                        </option>
                        <option value="dark" <?php echo ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>
                            Koyu Tema
                        </option>
                        <option value="auto">Sistem Teması</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language">
                        <i class="fas fa-language"></i>
                        Dil
                    </label>
                    <select id="language" name="language">
                        <option value="tr" selected>Türkçe</option>
                        <option value="en" disabled>English (Yakında)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="currency">
                        <i class="fas fa-money-bill"></i>
                        Varsayılan Para Birimi
                    </label>
                    <select id="currency" name="currency">
                        <option value="TRY" selected>Türk Lirası (₺)</option>
                        <option value="USD">Amerikan Doları ($)</option>
                        <option value="EUR">Euro (€)</option>
                        <option value="GBP">İngiliz Sterlini (£)</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Bildirim Ayarları -->
        <div class="settings-section">
            <h2>Bildirimler</h2>
            <form id="notificationForm" onsubmit="return handleNotificationUpdate(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="email_notifications" value="1" checked>
                        <span>E-posta Bildirimleri</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="push_notifications" value="1" checked>
                        <span>Tarayıcı Bildirimleri</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="notification_time">
                        <i class="fas fa-clock"></i>
                        Bildirim Zamanı
                    </label>
                    <select id="notification_time" name="notification_time">
                        <option value="1">1 gün önce</option>
                        <option value="3" selected>3 gün önce</option>
                        <option value="7">1 hafta önce</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Veri Ayarları -->
        <div class="settings-section">
            <h2>Veri ve Gizlilik</h2>
            <form id="dataForm" onsubmit="return handleDataUpdate(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="analytics" value="1" checked>
                        <span>Analitik verileri paylaş</span>
                    </label>
                    <p class="help-text">Uygulamamızı geliştirmemize yardımcı olun</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="backup" value="1" checked>
                        <span>Otomatik yedekleme</span>
                    </label>
                    <p class="help-text">Her gün verilerinizi yedekleyin</p>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Verileri İndir
                    </button>

                    <button type="button" class="btn-secondary" onclick="importData()">
                        <i class="fas fa-upload"></i>
                        Verileri Yükle
                    </button>
                </div>
            </form>
        </div>

        <!-- Güvenlik Ayarları -->
        <div class="settings-section">
            <h2>Güvenlik</h2>
            <form id="securityForm" onsubmit="return handleSecurityUpdate(event)">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="two_factor" value="1">
                        <span>İki Faktörlü Doğrulama</span>
                    </label>
                    <p class="help-text">Yakında kullanıma sunulacak</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="session_logout" value="1" checked>
                        <span>Diğer cihazlardan çıkış yap</span>
                    </label>
                    <button type="button" class="btn-secondary" onclick="logoutOtherSessions()">
                        <i class="fas fa-sign-out-alt"></i>
                        Oturumları Sonlandır
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSRF Token -->
<script>
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

    // Form değişikliklerini otomatik kaydet
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', () => {
                const formId = form.id;
                const handler = {
                    'appearanceForm': handleAppearanceUpdate,
                    'notificationForm': handleNotificationUpdate,
                    'dataForm': handleDataUpdate,
                    'securityForm': handleSecurityUpdate
                } [formId];

                if (handler) {
                    handler(new Event('change'));
                }
            });
        });
    });

    // Veri dışa aktarma
    function exportData() {
        Swal.fire({
            title: 'Veriler hazırlanıyor...',
            text: 'Lütfen bekleyin',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/api/settings.php/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: CSRF_TOKEN
                })
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'odeme-takip-yedek.json';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı',
                    text: 'Verileriniz başarıyla indirildi'
                });
            })
            .catch(error => {
                console.error('Veri dışa aktarma hatası:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Veriler indirilirken bir hata oluştu'
                });
            });
    }

    // Veri içe aktarma
    function importData() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'application/json';

        input.onchange = e => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = event => {
                try {
                    const data = JSON.parse(event.target.result);

                    Swal.fire({
                        title: 'Emin misiniz?',
                        text: 'Mevcut verilerinizin üzerine yazılacak',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Evet, yükle',
                        cancelButtonText: 'İptal',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return fetch('/api/settings.php/import', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        data: data,
                                        csrf_token: CSRF_TOKEN
                                    })
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Veri yükleme hatası');
                                    }
                                    return response.json();
                                });
                        }
                    }).then(result => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı',
                                text: 'Verileriniz başarıyla yüklendi'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    }).catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Veriler yüklenirken bir hata oluştu'
                        });
                    });
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: 'Geçersiz dosya formatı'
                    });
                }
            };
            reader.readAsText(file);
        };

        input.click();
    }

    // Diğer oturumları sonlandır
    function logoutOtherSessions() {
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Diğer tüm cihazlardaki oturumlarınız sonlandırılacak',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, sonlandır',
            cancelButtonText: 'İptal'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('/api/auth.php/sessions', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf_token: CSRF_TOKEN
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı',
                                text: 'Diğer oturumlar sonlandırıldı'
                            });
                        } else {
                            throw new Error(data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Oturum sonlandırma hatası:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Oturumlar sonlandırılırken bir hata oluştu'
                        });
                    });
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>