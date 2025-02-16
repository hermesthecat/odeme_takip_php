<?php
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Gelir</h5>
                <div class="display-6" id="totalIncome">...</div>
                <small class="text-white-50">Bu ay</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Gider</h5>
                <div class="display-6" id="totalExpense">...</div>
                <small class="text-white-50">Bu ay</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Net Durum</h5>
                <div class="display-6" id="netBalance">...</div>
                <small class="text-white-50">Bu ay</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Toplam Birikim</h5>
                <div class="display-6" id="totalSavings">...</div>
                <small class="text-white-50">Tüm zamanlar</small>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Aylık Gelir/Gider Grafiği</h5>
            </div>
            <div class="card-body">
                <canvas id="incomeExpenseChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Gider Kategorileri</h5>
            </div>
            <div class="card-body">
                <canvas id="expensePieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Budget Status -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Bütçe Durumu</h5>
                <small id="budgetMonthYear" class="text-muted"></small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Genel Bütçe</span>
                        <span id="budgetPercentage">0%</span>
                    </div>
                    <div class="progress">
                        <div id="budgetProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div id="categoryBudgets">
                    <!-- Category budgets will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Bills -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Yaklaşan Ödemeler</h5>
            </div>
            <div class="card-body">
                <div class="list-group" id="upcomingBills">
                    <!-- Upcoming bills will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Get current month and year
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;

        // Fetch monthly income and expenses
        const [incomeResponse, expenseResponse, budgetResponse, remindersResponse, savingsResponse] = await Promise.all([
            fetch(`/api/incomes?year=${year}&month=${month}`),
            fetch(`/api/expenses?year=${year}&month=${month}`),
            fetch('/api/budget?action=current'),
            fetch('/api/reminders?action=upcoming'),
            fetch('/api/savings')
        ]);

        const incomeData = await incomeResponse.json();
        const expenseData = await expenseResponse.json();
        const budgetData = await budgetResponse.json();
        const remindersData = await remindersResponse.json();
        const savingsData = await savingsResponse.json();

        // Update summary cards
        document.getElementById('totalIncome').textContent = incomeData.data.total_formatted;
        document.getElementById('totalExpense').textContent = expenseData.data.total_formatted;
        document.getElementById('netBalance').textContent = 
            new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' })
            .format(incomeData.data.total - expenseData.data.total);
        document.getElementById('totalSavings').textContent = savingsData.data.total_savings_formatted;

        // Create income/expense chart
        const monthNames = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 
                          'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        new Chart(document.getElementById('incomeExpenseChart'), {
            type: 'line',
            data: {
                labels: monthNames,
                datasets: [
                    {
                        label: 'Gelir',
                        borderColor: 'rgb(40, 167, 69)',
                        data: [/* monthly data will be here */]
                    },
                    {
                        label: 'Gider',
                        borderColor: 'rgb(220, 53, 69)',
                        data: [/* monthly data will be here */]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Create expense categories pie chart
        new Chart(document.getElementById('expensePieChart'), {
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

        // Update budget status
        if(budgetData.success) {
            document.getElementById('budgetMonthYear').textContent = 
                `${monthNames[now.getMonth()]} ${year}`;

            const percentage = (budgetData.data.total_expense / budgetData.data.monthly_limit) * 100;
            document.getElementById('budgetPercentage').textContent = `${percentage.toFixed(1)}%`;
            
            const progressBar = document.getElementById('budgetProgressBar');
            progressBar.style.width = `${percentage}%`;
            progressBar.className = `progress-bar ${percentage > 90 ? 'bg-danger' : 
                                                   percentage > 75 ? 'bg-warning' : 'bg-success'}`;

            // Update category budgets
            const categoryBudgets = document.getElementById('categoryBudgets');
            categoryBudgets.innerHTML = Object.entries(budgetData.data.categories)
                .map(([name, data]) => `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>${name}</span>
                            <span>${data.percentage.toFixed(1)}%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar ${data.percentage > 90 ? 'bg-danger' : 
                                                     data.percentage > 75 ? 'bg-warning' : 'bg-success'}"
                                 role="progressbar" style="width: ${data.percentage}%">
                            </div>
                        </div>
                    </div>
                `).join('');
        }

        // Update upcoming bills
        if(remindersData.success) {
            const upcomingBills = document.getElementById('upcomingBills');
            upcomingBills.innerHTML = remindersData.data
                .map(reminder => `
                    <div class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${reminder.name}</h6>
                            <small class="text-${reminder.status === 'overdue' ? 'danger' : 
                                               reminder.status === 'due_today' ? 'warning' : 'success'}">
                                ${reminder.days_until_due} gün kaldı
                            </small>
                        </div>
                        <small class="text-muted">Vade: ${reminder.due_date}</small>
                    </div>
                `).join('');
        }

    } catch(error) {
        console.error('Dashboard data loading error:', error);
        alert('Veriler yüklenirken bir hata oluştu');
    }
});
</script>
