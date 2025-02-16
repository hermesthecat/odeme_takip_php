<?php
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Gelir Yönetimi</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                <i class="bi bi-plus-circle"></i> Gelir Ekle
            </button>
        </div>
    </div>

    <!-- Income List -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Gelirler</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <span id="selectedMonth">Bu Ay</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="monthSelector"></ul>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Gelir Adı</th>
                                <th>Tutar</th>
                                <th>İlk Gelir Tarihi</th>
                                <th>Tekrar</th>
                                <th>Sonraki Ödeme</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="incomeList">
                            <tr>
                                <td colspan="6" class="text-center">Yükleniyor...</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>Toplam</th>
                                <th id="totalIncome" colspan="5">0,00 ₺</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Income Modal -->
<div class="modal fade" id="addIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addIncomeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Gelir Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Gelir Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <label for="amount" class="form-label">Tutar</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <label for="currency" class="form-label">Para Birimi</label>
                            <select class="form-select" id="currency" name="currency" required></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="first_income_date" class="form-label">İlk Gelir Tarihi</label>
                        <input type="date" class="form-control" id="first_income_date" name="first_income_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="frequency" class="form-label">Tekrar</label>
                        <select class="form-select" id="frequency" name="frequency" required>
                            <option value="0">Tek Seferlik</option>
                            <option value="1">Aylık</option>
                            <option value="2">2 Aylık</option>
                            <option value="3">3 Aylık</option>
                            <option value="6">6 Aylık</option>
                            <option value="12">Yıllık</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Income Modal -->
<div class="modal fade" id="editIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editIncomeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Gelir Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Gelir Adı</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <label for="edit_amount" class="form-label">Tutar</label>
                            <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <label for="edit_currency" class="form-label">Para Birimi</label>
                            <select class="form-select" id="edit_currency" name="currency" required></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_first_income_date" class="form-label">İlk Gelir Tarihi</label>
                        <input type="date" class="form-control" id="edit_first_income_date" name="first_income_date" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_frequency" class="form-label">Tekrar</label>
                        <select class="form-select" id="edit_frequency" name="frequency" required>
                            <option value="0">Tek Seferlik</option>
                            <option value="1">Aylık</option>
                            <option value="2">2 Aylık</option>
                            <option value="3">3 Aylık</option>
                            <option value="6">6 Aylık</option>
                            <option value="12">Yıllık</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize date inputs with current date
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.value = new Date().toISOString().split('T')[0];
});

// Load currencies
async function loadCurrencies() {
    try {
        const response = await fetch('/api/exchange?action=currencies');
        const data = await response.json();
        
        if(data.success) {
            const currencies = Object.entries(data.data.currencies).map(([code, info]) => 
                `<option value="${code}">${info.symbol} ${code}</option>`
            ).join('');

            document.getElementById('currency').innerHTML = currencies;
            document.getElementById('edit_currency').innerHTML = currencies;
        }
    } catch(error) {
        console.error('Currency loading error:', error);
    }
}

// Load and display incomes
async function loadIncomes(year, month) {
    try {
        const response = await fetch(`/api/incomes?year=${year}&month=${month}`);
        const data = await response.json();
        
        if(data.success) {
            const incomeList = document.getElementById('incomeList');
            const totalIncome = document.getElementById('totalIncome');
            
            if(data.data.incomes.length === 0) {
                incomeList.innerHTML = '<tr><td colspan="6" class="text-center">Gelir bulunamadı</td></tr>';
                totalIncome.textContent = '0,00 ₺';
                return;
            }

            incomeList.innerHTML = data.data.incomes.map(income => `
                <tr>
                    <td>${income.name}</td>
                    <td>${income.amount_formatted}</td>
                    <td>${income.first_income_date}</td>
                    <td>${getFrequencyText(income.frequency)}</td>
                    <td>${income.next_income_date}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editIncome(${income.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteIncome(${income.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            totalIncome.textContent = data.data.total_formatted;
        }
    } catch(error) {
        console.error('Income loading error:', error);
        alert('Gelirler yüklenirken bir hata oluştu');
    }
}

// Add new income
document.getElementById('addIncomeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const response = await fetch('/api/incomes', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addIncomeModal')).hide();
            this.reset();
            loadIncomes(new Date().getFullYear(), new Date().getMonth() + 1);
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gelir eklenemedi');
        }
    } catch(error) {
        console.error('Add income error:', error);
        alert('Gelir eklenirken bir hata oluştu');
    }
});

// Edit income
async function editIncome(id) {
    try {
        const response = await fetch(`/api/incomes?id=${id}`);
        const data = await response.json();
        
        if(data.success) {
            const income = data.data;
            document.getElementById('edit_id').value = income.id;
            document.getElementById('edit_name').value = income.name;
            document.getElementById('edit_amount').value = income.amount;
            document.getElementById('edit_currency').value = income.currency;
            document.getElementById('edit_first_income_date').value = income.first_income_date;
            document.getElementById('edit_frequency').value = income.frequency;
            
            new bootstrap.Modal(document.getElementById('editIncomeModal')).show();
        }
    } catch(error) {
        console.error('Edit income error:', error);
        alert('Gelir bilgileri yüklenirken bir hata oluştu');
    }
}

document.getElementById('editIncomeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const id = document.getElementById('edit_id').value;
        const response = await fetch(`/api/incomes?id=${id}`, {
            method: 'PUT',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editIncomeModal')).hide();
            loadIncomes(new Date().getFullYear(), new Date().getMonth() + 1);
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gelir güncellenemedi');
        }
    } catch(error) {
        console.error('Update income error:', error);
        alert('Gelir güncellenirken bir hata oluştu');
    }
});

// Delete income
async function deleteIncome(id) {
    if(!confirm('Bu geliri silmek istediğinizden emin misiniz?')) {
        return;
    }

    try {
        const response = await fetch(`/api/incomes?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            loadIncomes(new Date().getFullYear(), new Date().getMonth() + 1);
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gelir silinemedi');
        }
    } catch(error) {
        console.error('Delete income error:', error);
        alert('Gelir silinirken bir hata oluştu');
    }
}

// Helper functions
function getFrequencyText(frequency) {
    const frequencies = {
        0: 'Tek Seferlik',
        1: 'Aylık',
        2: '2 Aylık',
        3: '3 Aylık',
        6: '6 Aylık',
        12: 'Yıllık'
    };
    return frequencies[frequency] || 'Bilinmiyor';
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    loadIncomes(new Date().getFullYear(), new Date().getMonth() + 1);
});
</script>
