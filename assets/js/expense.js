/**
 * @author A. Kerem Gök
 * Gider yönetimi işlemleri
 */

// Gider listesini yükle
async function loadExpenses() {
    try {
        const table = document.getElementById('expensesTable').querySelector('tbody');
        table.innerHTML = '<tr><td colspan="8" class="loading">Yükleniyor...</td></tr>';

        // Filtre değerlerini al
        const dateRange = document.getElementById('dateRange').value;
        const category = document.getElementById('category').value;
        const status = document.getElementById('status').value;
        const currency = document.getElementById('currency').value;

        // API parametrelerini oluştur
        const params = new URLSearchParams();
        if (dateRange !== 'all') params.append('date_range', dateRange);
        if (category !== 'all') params.append('category', category);
        if (status !== 'all') params.append('status', status);
        if (currency !== 'all') params.append('currency', currency);

        const response = await fetchAPI(`/api/expense.php?${params.toString()}`);

        if (response.success) {
            if (response.data.length === 0) {
                table.innerHTML = '<tr><td colspan="8" class="no-data">Gider kaydı bulunamadı</td></tr>';
                return;
            }

            table.innerHTML = response.data.map(expense => `
                <tr class="${expense.status}">
                    <td>${formatDate(expense.due_date)}</td>
                    <td>${expense.payment_date ? formatDate(expense.payment_date) : '-'}</td>
                    <td>${expense.description}</td>
                    <td>${expense.category}</td>
                    <td class="amount">${formatMoney(expense.amount, expense.currency)}</td>
                    <td>${expense.currency}</td>
                    <td>
                        <span class="status-badge ${expense.status}">
                            ${getStatusText(expense.status)}
                        </span>
                    </td>
                    <td class="actions">
                        <button onclick="editExpense(${expense.id})" class="btn-icon" title="Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteExpense(${expense.id})" class="btn-icon" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                        ${expense.status === 'pending' ? `
                            <button onclick="markAsPaid(${expense.id})" class="btn-icon" title="Ödendi İşaretle">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            throw new Error(response.error || 'Giderler yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gider listesi yükleme hatası:', error);
        table.innerHTML = '<tr><td colspan="8" class="error">Veriler yüklenirken bir hata oluştu</td></tr>';
    }
}

// Durum metni
function getStatusText(status) {
    const statusTexts = {
        'pending': 'Bekleyen',
        'paid': 'Ödendi',
        'overdue': 'Gecikmiş'
    };
    return statusTexts[status] || status;
}

// Yeni gider ekle
async function handleAddExpense(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        // Ödeme tarihi kontrolü
        if (data.status === 'paid' && !data.payment_date) {
            data.payment_date = new Date().toISOString().split('T')[0];
        }

        const response = await fetchAPI('/api/expense.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Gider başarıyla eklendi',
                confirmButtonText: 'Tamam'
            });
            closeAddExpenseModal();
            loadExpenses();
        } else {
            throw new Error(response.error || 'Gider eklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gider ekleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gider düzenleme
async function editExpense(id) {
    try {
        const response = await fetchAPI(`/api/expense.php?id=${id}`);

        if (response.success) {
            const expense = response.data;

            // Form alanlarını doldur
            document.getElementById('edit_id').value = expense.id;
            document.getElementById('edit_amount').value = expense.amount;
            document.getElementById('edit_description').value = expense.description;
            document.getElementById('edit_due_date').value = expense.due_date;
            document.getElementById('edit_payment_date').value = expense.payment_date || '';
            document.getElementById('edit_category').value = expense.category;
            document.getElementById('edit_status').value = expense.status;
            document.getElementById('edit_currency').value = expense.currency;

            // Modalı aç
            document.getElementById('editExpenseModal').classList.add('show');
        } else {
            throw new Error(response.error || 'Gider bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Gider düzenleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gider güncelleme
async function handleEditExpense(event) {
    event.preventDefault();

    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        // Ödeme tarihi kontrolü
        if (data.status === 'paid' && !data.payment_date) {
            data.payment_date = new Date().toISOString().split('T')[0];
        }

        const response = await fetchAPI('/api/expense.php', {
            method: 'PUT',
            body: JSON.stringify(data)
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Gider başarıyla güncellendi',
                confirmButtonText: 'Tamam'
            });
            closeEditExpenseModal();
            loadExpenses();
        } else {
            throw new Error(response.error || 'Gider güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gider güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Gider silme
async function deleteExpense(id) {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Bu gider kaydı kalıcı olarak silinecek!',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });

        if (result.isConfirmed) {
            const response = await fetchAPI('/api/expense.php', {
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
                    text: 'Gider başarıyla silindi',
                    confirmButtonText: 'Tamam'
                });
                loadExpenses();
            } else {
                throw new Error(response.error || 'Gider silinirken bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Gider silme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Ödendi olarak işaretle
async function markAsPaid(id) {
    try {
        const response = await fetchAPI('/api/expense.php', {
            method: 'PUT',
            body: JSON.stringify({
                id: id,
                status: 'paid',
                payment_date: new Date().toISOString().split('T')[0],
                csrf_token: CSRF_TOKEN
            })
        });

        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı',
                text: 'Gider ödendi olarak işaretlendi',
                confirmButtonText: 'Tamam'
            });
            loadExpenses();
        } else {
            throw new Error(response.error || 'Gider durumu güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Gider durumu güncelleme hatası:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: error.message,
            confirmButtonText: 'Tamam'
        });
    }
}

// Modal işlemleri
function showAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.add('show');
}

function closeAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.remove('show');
    document.getElementById('addExpenseForm').reset();
}

function closeEditExpenseModal() {
    document.getElementById('editExpenseModal').classList.remove('show');
    document.getElementById('editExpenseForm').reset();
}

// Filtre işlemleri
function filterExpenses() {
    loadExpenses();
}

// Özel tarih aralığı kontrolü
document.getElementById('dateRange')?.addEventListener('change', function () {
    const customDateRange = document.getElementById('customDateRange');
    if (customDateRange) {
        customDateRange.style.display = this.value === 'custom' ? 'block' : 'none';
    }
});

// Durum değişikliğinde ödeme tarihi kontrolü
document.getElementById('status')?.addEventListener('change', function () {
    const paymentDateField = document.getElementById('payment_date');
    if (paymentDateField) {
        paymentDateField.required = this.value === 'paid';
        if (this.value === 'paid' && !paymentDateField.value) {
            paymentDateField.value = new Date().toISOString().split('T')[0];
        }
    }
});

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    loadExpenses();
}); 