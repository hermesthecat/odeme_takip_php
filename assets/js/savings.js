/**
 * @author A. Kerem Gök
 * Birikim hedefleri işlemleri
 */

// Birikim hedeflerini yükle
async function loadSavings() {
    try {
        const grid = document.getElementById('savingsGrid');
        grid.innerHTML = '<div class="loading">Yükleniyor...</div>';

        const response = await fetchAPI('/api/savings');

        if (response.success) {
            if (response.data.length === 0) {
                grid.innerHTML = '<div class="no-data">Birikim hedefi bulunamadı</div>';
                return;
            }

            grid.innerHTML = response.data.map(saving => {
                const progress = (saving.current_amount / saving.target_amount) * 100;
                const remainingAmount = saving.target_amount - saving.current_amount;
                const daysLeft = Math.ceil((new Date(saving.target_date) - new Date()) / (1000 * 60 * 60 * 24));

                return `
                    <div class="saving-card">
                        <div class="saving-header">
                            <h3>${saving.description}</h3>
                            <div class="saving-actions">
                                <button onclick="editSaving(${saving.id})" class="btn-icon" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteSaving(${saving.id})" class="btn-icon" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button onclick="showQuickUpdate(${saving.id})" class="btn-icon" title="Hızlı Güncelle">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="saving-progress">
                            <div class="progress-bar">
                                <div class="progress" style="width: ${progress}%"></div>
                            </div>
                            <div class="progress-text">%${progress.toFixed(1)}</div>
                        </div>
                        <div class="saving-details">
                            <div class="detail-item">
                                <span class="label">Hedef:</span>
                                <span class="value">${formatMoney(saving.target_amount, saving.currency)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Mevcut:</span>
                                <span class="value">${formatMoney(saving.current_amount, saving.currency)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Kalan:</span>
                                <span class="value">${formatMoney(remainingAmount, saving.currency)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Hedef Tarih:</span>
                                <span class="value">${formatDate(saving.target_date)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Kalan Gün:</span>
                                <span class="value">${daysLeft > 0 ? daysLeft : 'Süre doldu'}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // İstatistikleri güncelle
            updateSavingsStats(response.data);
        } else {
            throw new Error(response.error || 'Birikim hedefleri yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Birikim hedefleri yükleme hatası:', error);
        grid.innerHTML = '<div class="error">Veriler yüklenirken bir hata oluştu</div>';
    }
}

// Birikim istatistiklerini güncelle
function updateSavingsStats(data) {
    // Toplam birikim
    const totalSavings = data.reduce((sum, item) => sum + parseFloat(item.current_amount), 0);
    document.getElementById('totalSavings').textContent = formatMoney(totalSavings);

    // Hedeflere ulaşma oranı
    const totalProgress = data.reduce((sum, item) => {
        return sum + ((item.current_amount / item.target_amount) * 100);
    }, 0) / (data.length || 1);

    const progressCircle = document.getElementById('achievementRate');
    progressCircle.querySelector('.progress-text').textContent = `%${totalProgress.toFixed(1)}`;
    progressCircle.style.setProperty('--progress', totalProgress);

    // Aktif hedefler
    const activeGoals = data.filter(item => new Date(item.target_date) >= new Date()).length;
    document.getElementById('activeGoals').textContent = activeGoals;
}

// Yeni birikim hedefi ekle
async function handleAddSaving(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/savings', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Birikim hedefi başarıyla eklendi',
                confirmButtonText: 'Tamam'
            });
            closeAddSavingModal();
            loadSavings();
        } else {
            throw new Error(response.error || 'Birikim hedefi eklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Birikim hedefi ekleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Birikim hedefi düzenleme
async function editSaving(id) {
    try {
        const response = await fetchAPI(`/api/savings?id=${id}`);

        if (response.success) {
            const saving = response.data;

            // Form alanlarını doldur
            document.getElementById('edit_id').value = saving.id;
            document.getElementById('edit_description').value = saving.description;
            document.getElementById('edit_target_amount').value = saving.target_amount;
            document.getElementById('edit_current_amount').value = saving.current_amount;
            document.getElementById('edit_target_date').value = saving.target_date;
            document.getElementById('edit_currency').value = saving.currency;

            // Modalı aç
            document.getElementById('editSavingModal').classList.add('show');
        } else {
            throw new Error(response.error || 'Birikim hedefi bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Birikim hedefi düzenleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Birikim hedefi güncelleme
async function handleEditSaving(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/savings', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Birikim hedefi başarıyla güncellendi',
                confirmButtonText: 'Tamam'
            });
            closeEditSavingModal();
            loadSavings();
        } else {
            throw new Error(response.error || 'Birikim hedefi güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Birikim hedefi güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Birikim hedefi silme
async function deleteSaving(id) {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Bu birikim hedefi kalıcı olarak silinecek!',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });

        if (result.isConfirmed) {
            const response = await fetchAPI('/api/savings', {
                method: 'DELETE',
                body: JSON.stringify({
                    id: id,
                    csrf_token: CSRF_TOKEN
                })
            });

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı',
                    text: 'Birikim hedefi başarıyla silindi',
                    confirmButtonText: 'Tamam'
                });
                loadSavings();
            } else {
                throw new Error(response.error || 'Birikim hedefi silinirken bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Birikim hedefi silme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Hızlı güncelleme modalını göster
async function showQuickUpdate(id) {
    try {
        const response = await fetchAPI(`/api/savings?id=${id}`);

        if (response.success) {
            const saving = response.data;

            document.getElementById('quick_update_id').value = saving.id;
            document.getElementById('quick_amount').value = saving.current_amount;

            document.getElementById('quickUpdateModal').classList.add('show');
        } else {
            throw new Error(response.error || 'Birikim hedefi bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Hızlı güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Hızlı güncelleme işlemi
async function handleQuickUpdate(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/savings', {
            method: 'PUT',
            body: JSON.stringify({
                id: data.id,
                current_amount: data.amount,
                csrf_token: CSRF_TOKEN
            })
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Birikim miktarı güncellendi',
                confirmButtonText: 'Tamam'
            });
            closeQuickUpdateModal();
            loadSavings();
        } else {
            throw new Error(response.error || 'Birikim miktarı güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Hızlı güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Modal işlemleri
function showAddSavingModal() {
    document.getElementById('addSavingModal').classList.add('show');
}

function closeAddSavingModal() {
    document.getElementById('addSavingModal').classList.remove('show');
    document.getElementById('addSavingForm').reset();
}

function closeEditSavingModal() {
    document.getElementById('editSavingModal').classList.remove('show');
    document.getElementById('editSavingForm').reset();
}

function closeQuickUpdateModal() {
    document.getElementById('quickUpdateModal').classList.remove('show');
    document.getElementById('quickUpdateForm').reset();
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    loadSavings();
}); 