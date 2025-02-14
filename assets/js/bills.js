/**
 * @author A. Kerem Gök
 * Fatura hatırlatıcıları işlemleri
 */

// Yaklaşan faturaları yükle
async function loadUpcomingBills() {
    try {
        const container = document.getElementById('upcomingBills');
        container.innerHTML = '<div class="loading">Yükleniyor...</div>';

        const response = await fetchAPI('/api/bills.php?status=pending');

        if (response.success) {
            if (response.data.length === 0) {
                container.innerHTML = '<div class="no-data">Yaklaşan fatura bulunamadı</div>';
                return;
            }

            // Tarihe göre sırala
            const bills = response.data.sort((a, b) => new Date(a.due_date) - new Date(b.due_date));

            container.innerHTML = bills.map(bill => {
                const daysLeft = Math.ceil((new Date(bill.due_date) - new Date()) / (1000 * 60 * 60 * 24));
                const urgencyClass = daysLeft <= 3 ? 'urgent' : daysLeft <= 7 ? 'warning' : '';

                return `
                    <div class="bill-item ${urgencyClass}">
                        <div class="bill-date">
                            <div class="date">${formatDate(bill.due_date)}</div>
                            <div class="days-left">${daysLeft > 0 ? `${daysLeft} gün kaldı` : 'Bugün son gün!'}</div>
                        </div>
                        <div class="bill-info">
                            <div class="bill-title">${bill.title}</div>
                            <div class="bill-amount">${formatMoney(bill.amount, bill.currency)}</div>
                        </div>
                        <div class="bill-actions">
                            <button onclick="markBillAsPaid(${bill.id})" class="btn-icon" title="Ödendi İşaretle">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            throw new Error(response.error || 'Yaklaşan faturalar yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Yaklaşan faturalar yükleme hatası:', error);
        container.innerHTML = '<div class="error">Veriler yüklenirken bir hata oluştu</div>';
    }
}

// Fatura listesini yükle
async function loadBills() {
    try {
        const table = document.getElementById('billsTable').querySelector('tbody');
        table.innerHTML = '<tr><td colspan="7" class="loading">Yükleniyor...</td></tr>';

        // Filtre değerlerini al
        const repeatInterval = document.getElementById('repeatInterval').value;
        const status = document.getElementById('status').value;
        const currency = document.getElementById('currency').value;

        // API parametrelerini oluştur
        const params = new URLSearchParams();
        if (repeatInterval !== 'all') params.append('repeat_interval', repeatInterval);
        if (status !== 'all') params.append('status', status);
        if (currency !== 'all') params.append('currency', currency);

        const response = await fetchAPI(`/api/bills.php?${params.toString()}`);

        if (response.success) {
            if (response.data.length === 0) {
                table.innerHTML = '<tr><td colspan="7" class="no-data">Fatura kaydı bulunamadı</td></tr>';
                return;
            }

            table.innerHTML = response.data.map(bill => `
                <tr>
                    <td>${bill.title}</td>
                    <td class="amount">${formatMoney(bill.amount, bill.currency)}</td>
                    <td>${bill.currency}</td>
                    <td>${formatDate(bill.due_date)}</td>
                    <td>${getRepeatText(bill.repeat_interval)}</td>
                    <td>
                        <span class="status-badge ${getBillStatus(bill)}">
                            ${getBillStatusText(bill)}
                        </span>
                    </td>
                    <td class="actions">
                        <button onclick="editBill(${bill.id})" class="btn-icon" title="Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteBill(${bill.id})" class="btn-icon" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                        ${getBillStatus(bill) === 'pending' ? `
                            <button onclick="markBillAsPaid(${bill.id})" class="btn-icon" title="Ödendi İşaretle">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');

            // İstatistikleri güncelle
            updateBillStats(response.data);
        } else {
            throw new Error(response.error || 'Faturalar yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Fatura listesi yükleme hatası:', error);
        table.innerHTML = '<tr><td colspan="7" class="error">Veriler yüklenirken bir hata oluştu</td></tr>';
    }
}

// Fatura istatistiklerini güncelle
function updateBillStats(data) {
    // Bu ay ödenecek toplam
    const currentMonth = new Date().getMonth();
    const monthlyTotal = data
        .filter(bill => new Date(bill.due_date).getMonth() === currentMonth)
        .reduce((sum, bill) => sum + parseFloat(bill.amount), 0);

    document.getElementById('monthlyTotal').textContent = formatMoney(monthlyTotal);

    // Geciken ödemeler toplamı
    const overdueTotal = data
        .filter(bill => getBillStatus(bill) === 'overdue')
        .reduce((sum, bill) => sum + parseFloat(bill.amount), 0);

    document.getElementById('overdueTotal').textContent = formatMoney(overdueTotal);

    // Aktif hatırlatıcılar
    const activeReminders = data.filter(bill => getBillStatus(bill) === 'pending').length;
    document.getElementById('activeReminders').textContent = activeReminders;
}

// Tekrar aralığı metni
function getRepeatText(interval) {
    const repeatTexts = {
        'monthly': 'Aylık',
        'quarterly': '3 Aylık',
        'yearly': 'Yıllık',
        'once': 'Tek Seferlik'
    };
    return repeatTexts[interval] || interval;
}

// Fatura durumu
function getBillStatus(bill) {
    const now = new Date();
    const dueDate = new Date(bill.due_date);

    if (dueDate < now) return 'overdue';
    return 'pending';
}

// Fatura durum metni
function getBillStatusText(bill) {
    const status = getBillStatus(bill);
    const statusTexts = {
        'pending': 'Bekleyen',
        'overdue': 'Gecikmiş'
    };
    return statusTexts[status] || status;
}

// Yeni fatura ekle
async function handleAddBill(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/bills.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Fatura hatırlatıcısı başarıyla eklendi',
                confirmButtonText: 'Tamam'
            });
            closeAddBillModal();
            loadBills();
            loadUpcomingBills();
        } else {
            throw new Error(response.error || 'Fatura eklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Fatura ekleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Fatura düzenleme
async function editBill(id) {
    try {
        const response = await fetchAPI(`/api/bills.php?id=${id}`);

        if (response.success) {
            const bill = response.data;

            // Form alanlarını doldur
            document.getElementById('edit_id').value = bill.id;
            document.getElementById('edit_title').value = bill.title;
            document.getElementById('edit_amount').value = bill.amount;
            document.getElementById('edit_due_date').value = bill.due_date;
            document.getElementById('edit_repeat_interval').value = bill.repeat_interval;
            document.getElementById('edit_currency').value = bill.currency;

            // Modalı aç
            document.getElementById('editBillModal').classList.add('show');
        } else {
            throw new Error(response.error || 'Fatura bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Fatura düzenleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Fatura güncelleme
async function handleEditBill(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/bills.php', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Fatura başarıyla güncellendi',
                confirmButtonText: 'Tamam'
            });
            closeEditBillModal();
            loadBills();
            loadUpcomingBills();
        } else {
            throw new Error(response.error || 'Fatura güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Fatura güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Fatura silme
async function deleteBill(id) {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Bu fatura hatırlatıcısı kalıcı olarak silinecek!',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });

        if (result.isConfirmed) {
            const response = await fetchAPI('/api/bills.php', {
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
                    text: 'Fatura başarıyla silindi',
                    confirmButtonText: 'Tamam'
                });
                loadBills();
                loadUpcomingBills();
            } else {
                throw new Error(response.error || 'Fatura silinirken bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Fatura silme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Faturayı ödendi olarak işaretle
async function markBillAsPaid(id) {
    try {
        // Yeni fatura tarihi hesapla
        const response = await fetchAPI(`/api/bills.php?id=${id}`);
        if (!response.success) {
            throw new Error(response.error || 'Fatura bilgileri alınamadı');
        }

        const bill = response.data;
        const nextDueDate = calculateNextDueDate(bill.due_date, bill.repeat_interval);

        // Faturayı güncelle
        const updateResponse = await fetchAPI('/api/bills.php', {
            method: 'PUT',
            body: JSON.stringify({
                id: id,
                due_date: nextDueDate,
                csrf_token: CSRF_TOKEN
            })
        });

        if (updateResponse.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Fatura ödendi olarak işaretlendi',
                confirmButtonText: 'Tamam'
            });
            loadBills();
            loadUpcomingBills();
        } else {
            throw new Error(updateResponse.error || 'Fatura durumu güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Fatura durumu güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Sonraki fatura tarihini hesapla
function calculateNextDueDate(currentDate, repeatInterval) {
    const date = new Date(currentDate);

    switch (repeatInterval) {
        case 'monthly':
            date.setMonth(date.getMonth() + 1);
            break;
        case 'quarterly':
            date.setMonth(date.getMonth() + 3);
            break;
        case 'yearly':
            date.setFullYear(date.getFullYear() + 1);
            break;
        case 'once':
            return null;
    }

    return date.toISOString().split('T')[0];
}

// Modal işlemleri
function showAddBillModal() {
    document.getElementById('addBillModal').classList.add('show');
}

function closeAddBillModal() {
    document.getElementById('addBillModal').classList.remove('show');
    document.getElementById('addBillForm').reset();
}

function closeEditBillModal() {
    document.getElementById('editBillModal').classList.remove('show');
    document.getElementById('editBillForm').reset();
}

// Filtre işlemleri
function filterBills() {
    loadBills();
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    loadBills();
    loadUpcomingBills();
}); 