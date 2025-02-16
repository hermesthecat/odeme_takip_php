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
            <h2>Gider Yönetimi</h2>
            <div>
                <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="bi bi-tags"></i> Kategoriler
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-plus-circle"></i> Gider Ekle
                </button>
            </div>
        </div>
    </div>

    <!-- Budget Warning -->
    <div id="budgetWarning" class="col-12 mb-4" style="display: none;">
        <div class="alert alert-warning">
            <strong>Uyarı!</strong> <span id="budgetWarningText"></span>
        </div>
    </div>

    <!-- Expense List -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 me-3">Giderler</h5>
                    <select id="categoryFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Tüm Kategoriler</option>
                    </select>
                </div>
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
                                <th>Gider Adı</th>
                                <th>Kategori</th>
                                <th>Tutar</th>
                                <th>İlk Ödeme Tarihi</th>
                                <th>Tekrar</th>
                                <th>Sonraki Ödeme</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="expenseList">
                            <tr>
                                <td colspan="7" class="text-center">Yükleniyor...</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="2">Toplam</th>
                                <th id="totalExpense" colspan="5">0,00 ₺</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addExpenseForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Gider Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Gider Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Kategori</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Kategori Seçin</option>
                        </select>
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
                        <label for="first_payment_date" class="form-label">İlk Ödeme Tarihi</label>
                        <input type="date" class="form-control" id="first_payment_date" name="first_payment_date" required>
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

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editExpenseForm">
                <div class="modal-header">
                    <h5 class="modal-title">Gider Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Gider Adı</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Kategori</label>
                        <select class="form-select" id="edit_category" name="category" required>
                            <option value="">Kategori Seçin</option>
                        </select>
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
                        <label for="edit_first_payment_date" class="form-label">İlk Ödeme Tarihi</label>
                        <input type="date" class="form-control" id="edit_first_payment_date" name="first_payment_date" required>
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

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gider Kategorileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="newCategory" placeholder="Yeni kategori">
                        <button class="btn btn-primary" type="button" onclick="addCategory()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="list-group" id="categoryList">
                    <!-- Categories will be inserted here -->
                </div>
            </div>
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

// Load categories
async function loadCategories() {
    try {
        const response = await fetch('/api/budget?action=current');
        const data = await response.json();
        
        if(data.success) {
            const categories = data.data.categories;
            const categoryOptions = Object.keys(categories).map(name => 
                `<option value="${name}">${name}</option>`
            ).join('');

            document.getElementById('category').innerHTML = 
                '<option value="">Kategori Seçin</option>' + categoryOptions;
            document.getElementById('edit_category').innerHTML = 
                '<option value="">Kategori Seçin</option>' + categoryOptions;
            document.getElementById('categoryFilter').innerHTML = 
                '<option value="">Tüm Kategoriler</option>' + categoryOptions;

            document.getElementById('categoryList').innerHTML = Object.entries(categories)
                .map(([name, data]) => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${name}</span>
                            <small class="text-muted">Limit: ${data.limit_formatted}</small>
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar ${data.percentage > 90 ? 'bg-danger' : 
                                                    data.percentage > 75 ? 'bg-warning' : 'bg-success'}" 
                                 style="width: ${data.percentage}%">
                            </div>
                        </div>
                    </div>
                `).join('');
        }
    } catch(error) {
        console.error('Categories loading error:', error);
    }
}

// Load and display expenses
async function loadExpenses(year, month, category = '') {
    try {
        const url = category ? 
            `/api/expenses?category=${category}` : 
            `/api/expenses?year=${year}&month=${month}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if(data.success) {
            const expenseList = document.getElementById('expenseList');
            const totalExpense = document.getElementById('totalExpense');
            
            if(data.data.expenses.length === 0) {
                expenseList.innerHTML = '<tr><td colspan="7" class="text-center">Gider bulunamadı</td></tr>';
                totalExpense.textContent = '0,00 ₺';
                return;
            }

            expenseList.innerHTML = data.data.expenses.map(expense => `
                <tr>
                    <td>${expense.name}</td>
                    <td>${expense.category}</td>
                    <td>${expense.amount_formatted}</td>
                    <td>${expense.first_payment_date}</td>
                    <td>${getFrequencyText(expense.frequency)}</td>
                    <td>${expense.next_payment_date}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editExpense(${expense.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteExpense(${expense.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            totalExpense.textContent = data.data.total_formatted;
        }
    } catch(error) {
        console.error('Expense loading error:', error);
        alert('Giderler yüklenirken bir hata oluştu');
    }
}

// Check budget limit before adding expense
async function checkBudgetLimit(amount, currency, category) {
    try {
        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('currency', currency);
        formData.append('category', category);

        const response = await fetch('/api/budget?action=check', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const data = await response.json();
        
        if(data.success && data.data.exceeded) {
            const warning = data.data.type === 'overall' ? 
                'Bu gider aylık bütçe limitinizi aşacak.' : 
                `Bu gider "${category}" kategorisi için belirlenen limiti aşacak.`;
            
            document.getElementById('budgetWarningText').textContent = warning;
            document.getElementById('budgetWarning').style.display = 'block';
            
            return !confirm('Limiti aşmak istediğinizden emin misiniz?');
        }

        document.getElementById('budgetWarning').style.display = 'none';
        return false;
    } catch(error) {
        console.error('Budget check error:', error);
        return false;
    }
}

// Add new expense
document.getElementById('addExpenseForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const amount = formData.get('amount');
    const currency = formData.get('currency');
    const category = formData.get('category');

    // Check budget limit
    if(await checkBudgetLimit(amount, currency, category)) {
        return;
    }

    try {
        const response = await fetch('/api/expenses', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addExpenseModal')).hide();
            this.reset();
            loadExpenses(new Date().getFullYear(), new Date().getMonth() + 1);
            loadCategories();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gider eklenemedi');
        }
    } catch(error) {
        console.error('Add expense error:', error);
        alert('Gider eklenirken bir hata oluştu');
    }
});

// Edit expense
async function editExpense(id) {
    try {
        const response = await fetch(`/api/expenses?id=${id}`);
        const data = await response.json();
        
        if(data.success) {
            const expense = data.data;
            document.getElementById('edit_id').value = expense.id;
            document.getElementById('edit_name').value = expense.name;
            document.getElementById('edit_category').value = expense.category;
            document.getElementById('edit_amount').value = expense.amount;
            document.getElementById('edit_currency').value = expense.currency;
            document.getElementById('edit_first_payment_date').value = expense.first_payment_date;
            document.getElementById('edit_frequency').value = expense.frequency;
            
            new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
        }
    } catch(error) {
        console.error('Edit expense error:', error);
        alert('Gider bilgileri yüklenirken bir hata oluştu');
    }
}

document.getElementById('editExpenseForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const amount = formData.get('amount');
    const currency = formData.get('currency');
    const category = formData.get('category');

    // Check budget limit
    if(await checkBudgetLimit(amount, currency, category)) {
        return;
    }

    try {
        const id = document.getElementById('edit_id').value;
        const response = await fetch(`/api/expenses?id=${id}`, {
            method: 'PUT',
            body: formData,
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editExpenseModal')).hide();
            loadExpenses(new Date().getFullYear(), new Date().getMonth() + 1);
            loadCategories();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gider güncellenemedi');
        }
    } catch(error) {
        console.error('Update expense error:', error);
        alert('Gider güncellenirken bir hata oluştu');
    }
});

// Delete expense
async function deleteExpense(id) {
    if(!confirm('Bu gideri silmek istediğinizden emin misiniz?')) {
        return;
    }

    try {
        const response = await fetch(`/api/expenses?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            loadExpenses(new Date().getFullYear(), new Date().getMonth() + 1);
            loadCategories();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Gider silinemedi');
        }
    } catch(error) {
        console.error('Delete expense error:', error);
        alert('Gider silinirken bir hata oluştu');
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

// Category filter change handler
document.getElementById('categoryFilter').addEventListener('change', function() {
    const category = this.value;
    const now = new Date();
    loadExpenses(now.getFullYear(), now.getMonth() + 1, category);
});

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    loadCategories();
    loadExpenses(new Date().getFullYear(), new Date().getMonth() + 1);
});
</script>
