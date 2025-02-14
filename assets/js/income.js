/**
 * @author A. Kerem Gök
 * Gelir yönetimi işlemleri
 */

// Gelir listesini yükle
async function loadIncomes() {
    try {
        const table = document.getElementById('incomesTable').querySelector('tbody');
        table.innerHTML = '<tr><td colspan="6" class="loading">Yükleniyor...</td></tr>';

        // Filtre değerlerini al
        const dateRange = document.getElementById('dateRange').value;
        const category = document.getElementById('category').value;
        const currency = document.getElementById('currency').value;

        // API parametrelerini oluştur
        const params = new URLSearchParams();
        if (dateRange !== 'all') params.append('date_range', dateRange);
        if (category !== 'all') params.append('category', category);
        if (currency !== 'all') params.append('currency', currency);

        const response = await fetchAPI(`/api/income.php?${params.toString()}`);

        if (response.success) {
            if (response.data.length === 0) {
                table.innerHTML = '<tr><td colspan="6" class="no-data">Gelir kaydı bulunamadı</td></tr>';
                return;
            }

            table.innerHTML = response.data.map(income => `
                <tr>
                    <td>${formatDate(income.income_date)}</td>
                    <td>${income.description}</td>
                    <td>${income.category}</td>
                    <td class="amount">${formatMoney(income.amount, income.currency)}</td>
                    <td>${income.currency}</td>
                    <td class="actions">
                        <button onclick="editIncome(${income.id})" class="btn-icon" title="Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteIncome(${income.id})" class="btn-icon" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            throw new Error(response.error || 'Gelirler yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gelir listesi yükleme hatası:', error);
        table.innerHTML = '<tr><td colspan="6" class="error">Veriler yüklenirken bir hata oluştu</td></tr>';
    }
}

// Yeni gelir ekle
async function handleAddIncome(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/income.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Gelir başarıyla eklendi',
                confirmButtonText: 'Tamam'
            });
            closeAddIncomeModal();
            loadIncomes();
        } else {
            throw new Error(response.error || 'Gelir eklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gelir ekleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gelir düzenleme
async function editIncome(id) {
    try {
        const response = await fetchAPI(`/api/income.php?id=${id}`);

        if (response.success) {
            const income = response.data;

            // Form alanlarını doldur
            document.getElementById('edit_id').value = income.id;
            document.getElementById('edit_amount').value = income.amount;
            document.getElementById('edit_description').value = income.description;
            document.getElementById('edit_income_date').value = income.income_date;
            document.getElementById('edit_category').value = income.category;
            document.getElementById('edit_currency').value = income.currency;

            // Modalı aç
            document.getElementById('editIncomeModal').classList.add('show');
        } else {
            throw new Error(response.error || 'Gelir bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Gelir düzenleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gelir güncelleme
async function handleEditIncome(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/income.php', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Gelir başarıyla güncellendi',
                confirmButtonText: 'Tamam'
            });
            closeEditIncomeModal();
            loadIncomes();
        } else {
            throw new Error(response.error || 'Gelir güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gelir güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gelir silme
async function deleteIncome(id) {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Bu gelir kaydı kalıcı olarak silinecek!',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });

        if (result.isConfirmed) {
            const response = await fetchAPI('/api/income.php', {
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
                    text: 'Gelir başarıyla silindi',
                    confirmButtonText: 'Tamam'
                });
                loadIncomes();
            } else {
                throw new Error(response.error || 'Gelir silinirken bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Gelir silme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Modal işlemleri
function showAddIncomeModal() {
    document.getElementById('addIncomeModal').classList.add('show');
}

function closeAddIncomeModal() {
    document.getElementById('addIncomeModal').classList.remove('show');
    document.getElementById('addIncomeForm').reset();
}

function closeEditIncomeModal() {
    document.getElementById('editIncomeModal').classList.remove('show');
    document.getElementById('editIncomeForm').reset();
}

// Filtre işlemleri
function filterIncomes() {
    loadIncomes();
}

// Özel tarih aralığı kontrolü
document.getElementById('dateRange')?.addEventListener('change', function () {
    const customDateRange = document.getElementById('customDateRange');
    if (customDateRange) {
        customDateRange.style.display = this.value === 'custom' ? 'block' : 'none';
    }
});

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    loadIncomes();
}); 