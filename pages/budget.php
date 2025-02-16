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
            <h2>Bütçe Yönetimi</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#setBudgetModal">
                <i class="bi bi-gear"></i> Bütçe Ayarla
            </button>
        </div>
    </div>

    <!-- Budget Overview -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Bütçe Durumu</h5>
                <small id="currentMonth" class="text-muted"></small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h6 class="mb-1">Toplam Bütçe</h6>
                            <div class="h3" id="monthlyLimit">0,00 ₺</div>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-1">Kalan Bütçe</h6>
                            <div class="h3" id="remainingBudget">0,00 ₺</div>
                        </div>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div id="budgetProgress" class="progress-bar" role="progressbar" style="width: 0%">
                            <span id="budgetPercentage">0%</span>
                        </div>
                    </div>
                </div>

                <div id="categoryBudgets">
                    <!-- Category budgets will be inserted here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Stats -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Aylık İstatistikler</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6>Gelir / Gider Dengesi</h6>
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
                <div>
                    <h6>Kategori Dağılımı</h6>
                    <canvas id="categoryDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Son İşlemler</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>İşlem</th>
                            <th>Kategori</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody id="recentTransactions">
                        <tr>
                            <td colspan="5" class="text-center">Yükleniyor...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Set Budget Modal -->
<div class="modal fade" id="setBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="setBudgetForm">
                <div class="modal-header">
                    <h5 class="modal-title">Bütçe Ayarları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-4">
                        <label for="monthly_limit" class="form-label">Aylık Bütçe Limiti</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="monthly_limit" name="monthly_limit" step="0.01" required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>

                    <h6 class="mb-3">Kategori Limitleri</h6>
                    <div id="categoryLimits">
                        <!-- Category limit inputs will be inserted here -->
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCategoryLimit()">
                        <i class="bi bi-plus-circle"></i> Yeni Kategori Ekle
                    </button>
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
// Load budget status
async function loadBudget() {
    try {
        const response = await fetch('/api/budget?action=current');
        const data = await response.json();
        
        if(data.success) {
            // Update monthly overview
            document.getElementById('monthlyLimit').textContent = data.data.monthly_limit_formatted;
            document.getElementById('remainingBudget').textContent = data.data.remaining_formatted;
            document.getElementById('currentMonth').textContent = new Date().toLocaleString('tr-TR', { month: 'long', year: 'numeric' });

            const percentage = (data.data.total_expense / data.data.monthly_limit) * 100;
            const progressBar = document.getElementById('budgetProgress');
            progressBar.style.width = `${percentage}%`;
            progressBar.className = `progress-bar ${percentage > 90 ? 'bg-danger' : 
                                                   percentage > 75 ? 'bg-warning' : 'bg-success'}`;
            document.getElementById('budgetPercentage').textContent = `${percentage.toFixed(1)}%`;

            // Update category budgets
            const categoryBudgets = document.getElementById('categoryBudgets');
            categoryBudgets.innerHTML = Object.entries(data.data.categories)
                .map(([name, info]) => `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <h6 class="mb-1">${name}</h6>
                                <small class="text-muted">${info.spent_formatted} / ${info.limit_formatted}</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Kalan: ${info.remaining_formatted}</small>
                            </div>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${info.percentage > 90 ? 'bg-danger' : 
                                                     info.percentage > 75 ? 'bg-warning' : 'bg-success'}" 
                                 role="progressbar" 
                                 style="width: ${info.percentage}%" 
                                 title="${info.percentage.toFixed(1)}%">
                            </div>
                        </div>
                    </div>
                `).join('');

            // Update form inputs
            document.getElementById('monthly_limit').value = data.data.monthly_limit;
            updateCategoryLimitInputs(data.data.categories);
        }
    } catch(error) {
        console.error('Budget loading error:', error);
        alert('Bütçe bilgileri yüklenirken bir hata oluştu');
    }
}

// Update category limit inputs in modal
function updateCategoryLimitInputs(categories) {
    const container = document.getElementById('categoryLimits');
    container.innerHTML = Object.entries(categories).map(([name, info], index) => `
        <div class="mb-3 category-limit-row">
            <div class="input-group">
                <input type="text" class="form-control" 
                       name="categories[${index}][name]" 
                       value="${name}" required 
                       placeholder="Kategori adı">
                <input type="number" class="form-control" 
                       name="categories[${index}][limit]" 
                       value="${info.limit}" required 
                       step="0.01" 
                       placeholder="Limit">
                <span class="input-group-text">₺</span>
                <button type="button" class="btn btn-outline-danger" onclick="removeCategoryLimit(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Add new category limit input
function addCategoryLimit() {
    const container = document.getElementById('categoryLimits');
    const index = container.querySelectorAll('.category-limit-row').length;
    
    const newRow = document.createElement('div');
    newRow.className = 'mb-3 category-limit-row';
    newRow.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" 
                   name="categories[${index}][name]" 
                   required 
                   placeholder="Kategori adı">
            <input type="number" class="form-control" 
                   name="categories[${index}][limit]" 
                   required 
                   step="0.01" 
                   placeholder="Limit">
            <span class="input-group-text">₺</span>
            <button type="button" class="btn btn-outline-danger" onclick="removeCategoryLimit(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
}

// Remove category limit input
function removeCategoryLimit(button) {
    button.closest('.category-limit-row').remove();
}

// Save budget settings
document.getElementById('setBudgetForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const response = await fetch('/api/budget', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('setBudgetModal')).hide();
            loadBudget();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Bütçe ayarları kaydedilemedi');
        }
    } catch(error) {
        console.error('Save budget error:', error);
        alert('Bütçe ayarları kaydedilirken bir hata oluştu');
    }
});

// Load monthly stats
async function loadMonthlyStats() {
    try {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;

        const [incomeResponse, expenseResponse] = await Promise.all([
            fetch(`/api/incomes?year=${year}&month=${month}`),
            fetch(`/api/expenses?year=${year}&month=${month}`)
        ]);

        const incomeData = await incomeResponse.json();
        const expenseData = await expenseResponse.json();

        // Create income/expense balance chart
        new Chart(document.getElementById('incomeExpenseChart'), {
            type: 'doughnut',
            data: {
                labels: ['Gelir', 'Gider'],
                datasets: [{
                    data: [incomeData.data.total, expenseData.data.total],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Create category distribution chart
        new Chart(document.getElementById('categoryDistributionChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(expenseData.data.categories),
                datasets: [{
                    data: Object.values(expenseData.data.categories).map(cat => cat.amount),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#8AC656', '#FF99CC', '#66B2FF', '#FFB366'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

    } catch(error) {
        console.error('Monthly stats loading error:', error);
    }
}

// Load recent transactions
async function loadRecentTransactions() {
    try {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;

        const [incomeResponse, expenseResponse] = await Promise.all([
            fetch(`/api/incomes?year=${year}&month=${month}`),
            fetch(`/api/expenses?year=${year}&month=${month}`)
        ]);

        const incomeData = await incomeResponse.json();
        const expenseData = await expenseResponse.json();

        // Combine and sort transactions
        const transactions = [
            ...incomeData.data.incomes.map(income => ({
                ...income,
                type: 'income',
                category: 'Gelir',
                date: income.first_income_date
            })),
            ...expenseData.data.expenses.map(expense => ({
                ...expense,
                type: 'expense',
                date: expense.first_payment_date
            }))
        ].sort((a, b) => new Date(b.date) - new Date(a.date));

        // Display transactions
        document.getElementById('recentTransactions').innerHTML = transactions
            .slice(0, 10) // Show only last 10 transactions
            .map(transaction => `
                <tr>
                    <td>${new Date(transaction.date).toLocaleDateString('tr-TR')}</td>
                    <td>${transaction.name}</td>
                    <td>${transaction.category}</td>
                    <td class="${transaction.type === 'income' ? 'text-success' : 'text-danger'}">
                        ${transaction.type === 'income' ? '+' : '-'} ${transaction.amount_formatted}
                    </td>
                    <td>
                        <span class="badge ${transaction.type === 'income' ? 'bg-success' : 'bg-danger'}">
                            ${transaction.type === 'income' ? 'Gelir' : 'Gider'}
                        </span>
                    </td>
                </tr>
            `).join('');

    } catch(error) {
        console.error('Recent transactions loading error:', error);
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadBudget();
    loadMonthlyStats();
    loadRecentTransactions();
});
</script>
