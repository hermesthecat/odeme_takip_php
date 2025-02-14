/**
 * @author A. Kerem Gök
 * Ana sayfa işlemleri
 */

// Özet kartlarını güncelle
async function updateSummaryCards() {
    try {
        // Gelir toplamı
        const incomeResponse = await fetchAPI('/api/income.php');
        if (incomeResponse.success) {
            const totalIncome = incomeResponse.data.reduce((sum, item) => sum + parseFloat(item.amount), 0);
            document.querySelector('#incomeCard .amount').textContent = formatMoney(totalIncome);
        }

        // Gider toplamı
        const expenseResponse = await fetchAPI('/api/expense.php');
        if (expenseResponse.success) {
            const totalExpense = expenseResponse.data.reduce((sum, item) => sum + parseFloat(item.amount), 0);
            document.querySelector('#expenseCard .amount').textContent = formatMoney(totalExpense);
        }

        // Net durum
        const netBalance = totalIncome - totalExpense;
        document.querySelector('#balanceCard .amount').textContent = formatMoney(netBalance);
        document.querySelector('#balanceCard .amount').classList.toggle('negative', netBalance < 0);

        // Birikim toplamı
        const savingsResponse = await fetchAPI('/api/savings.php');
        if (savingsResponse.success) {
            const totalSavings = savingsResponse.data.reduce((sum, item) => sum + parseFloat(item.current_amount), 0);
            document.querySelector('#savingsCard .amount').textContent = formatMoney(totalSavings);
        }
    } catch (error) {
        console.error('Özet kartları güncelleme hatası:', error);
    }
}

// Gelir/Gider grafiğini güncelle
async function updateIncomeExpenseChart() {
    try {
        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');

        // Son 6 ayın verilerini al
        const months = Array.from({ length: 6 }, (_, i) => {
            const d = new Date();
            d.setMonth(d.getMonth() - i);
            return d.toLocaleString('tr-TR', { month: 'long' });
        }).reverse();

        const incomeData = await Promise.all(months.map(async month => {
            const response = await fetchAPI(`/api/income.php?month=${month}`);
            return response.success ? response.data.reduce((sum, item) => sum + parseFloat(item.amount), 0) : 0;
        }));

        const expenseData = await Promise.all(months.map(async month => {
            const response = await fetchAPI(`/api/expense.php?month=${month}`);
            return response.success ? response.data.reduce((sum, item) => sum + parseFloat(item.amount), 0) : 0;
        }));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Gelir',
                    data: incomeData,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Gider',
                    data: expenseData,
                    borderColor: '#F44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Aylık Gelir/Gider Grafiği'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatMoney(value)
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Gelir/Gider grafiği güncelleme hatası:', error);
    }
}

// Kategori dağılımı grafiğini güncelle
async function updateCategoryChart() {
    try {
        const ctx = document.getElementById('categoryChart').getContext('2d');

        // Gider kategorilerini al
        const expenseResponse = await fetchAPI('/api/expense.php');
        const categoryData = {};

        if (expenseResponse.success) {
            expenseResponse.data.forEach(expense => {
                categoryData[expense.category] = (categoryData[expense.category] || 0) + parseFloat(expense.amount);
            });
        }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    data: Object.values(categoryData),
                    backgroundColor: [
                        '#4CAF50', '#2196F3', '#F44336', '#FFC107',
                        '#9C27B0', '#FF5722', '#795548', '#607D8B'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Gider Kategorileri Dağılımı'
                    }
                }
            }
        });
    } catch (error) {
        console.error('Kategori grafiği güncelleme hatası:', error);
    }
}

// Son işlemleri güncelle
async function updateRecentTransactions() {
    try {
        const container = document.getElementById('recentTransactions');
        container.innerHTML = '<div class="loading">Yükleniyor...</div>';

        // Son 5 gelir ve gideri al
        const [incomeResponse, expenseResponse] = await Promise.all([
            fetchAPI('/api/income.php?limit=5'),
            fetchAPI('/api/expense.php?limit=5')
        ]);

        const transactions = [];

        if (incomeResponse.success) {
            transactions.push(...incomeResponse.data.map(income => ({
                ...income,
                type: 'income',
                date: income.income_date
            })));
        }

        if (expenseResponse.success) {
            transactions.push(...expenseResponse.data.map(expense => ({
                ...expense,
                type: 'expense',
                date: expense.payment_date || expense.due_date
            })));
        }

        // Tarihe göre sırala
        transactions.sort((a, b) => new Date(b.date) - new Date(a.date));

        // İlk 5 işlemi göster
        const html = transactions.slice(0, 5).map(transaction => `
            <div class="transaction-item ${transaction.type}">
                <div class="transaction-date">${formatDate(transaction.date)}</div>
                <div class="transaction-desc">${transaction.description}</div>
                <div class="transaction-amount ${transaction.type}">
                    ${transaction.type === 'income' ? '+' : '-'} ${formatMoney(transaction.amount, transaction.currency)}
                </div>
            </div>
        `).join('');

        container.innerHTML = html || '<div class="no-data">İşlem bulunamadı</div>';
    } catch (error) {
        console.error('Son işlemler güncelleme hatası:', error);
        container.innerHTML = '<div class="error">Veriler yüklenirken bir hata oluştu</div>';
    }
}

// Yaklaşan ödemeleri güncelle
async function updateUpcomingPayments() {
    try {
        const container = document.getElementById('upcomingPayments');
        container.innerHTML = '<div class="loading">Yükleniyor...</div>';

        // Yaklaşan faturaları ve giderleri al
        const [billsResponse, expenseResponse] = await Promise.all([
            fetchAPI('/api/bills.php?status=pending'),
            fetchAPI('/api/expense.php?status=pending')
        ]);

        const payments = [];

        if (billsResponse.success) {
            payments.push(...billsResponse.data.map(bill => ({
                ...bill,
                type: 'bill',
                date: bill.due_date
            })));
        }

        if (expenseResponse.success) {
            payments.push(...expenseResponse.data.map(expense => ({
                ...expense,
                type: 'expense',
                date: expense.due_date
            })));
        }

        // Tarihe göre sırala
        payments.sort((a, b) => new Date(a.date) - new Date(b.date));

        // İlk 5 ödemeyi göster
        const html = payments.slice(0, 5).map(payment => `
            <div class="payment-item">
                <div class="payment-date">${formatDate(payment.date)}</div>
                <div class="payment-desc">${payment.title || payment.description}</div>
                <div class="payment-amount">
                    ${formatMoney(payment.amount, payment.currency)}
                </div>
            </div>
        `).join('');

        container.innerHTML = html || '<div class="no-data">Yaklaşan ödeme bulunamadı</div>';
    } catch (error) {
        console.error('Yaklaşan ödemeler güncelleme hatası:', error);
        container.innerHTML = '<div class="error">Veriler yüklenirken bir hata oluştu</div>';
    }
}

// Döviz kurlarını güncelle
async function updateCurrencyRates() {
    try {
        const container = document.querySelector('#currencyWidget .currency-rates');
        container.innerHTML = '<div class="loading">Yükleniyor...</div>';

        const response = await fetchAPI('/api/currency.php');

        if (response.success) {
            const { rates, currencies, last_update } = response.data;

            const html = Object.entries(rates).map(([code, rate]) => `
                <div class="currency-item">
                    <div class="currency-code">${code}</div>
                    <div class="currency-name">${currencies[code]}</div>
                    <div class="currency-rate">${rate.toFixed(4)}</div>
                </div>
            `).join('');

            container.innerHTML = `
                ${html}
                <div class="last-update">Son güncelleme: ${formatDate(last_update)}</div>
            `;
        } else {
            throw new Error('Döviz kurları alınamadı');
        }
    } catch (error) {
        console.error('Döviz kurları güncelleme hatası:', error);
        container.innerHTML = '<div class="error">Döviz kurları yüklenirken bir hata oluştu</div>';
    }
}

// Tüm dashboard verilerini güncelle
async function updateDashboard() {
    await Promise.all([
        updateSummaryCards(),
        updateIncomeExpenseChart(),
        updateCategoryChart(),
        updateRecentTransactions(),
        updateUpcomingPayments(),
        updateCurrencyRates()
    ]);
}

// Sayfa yüklendiğinde ve her 5 dakikada bir güncelle
document.addEventListener('DOMContentLoaded', () => {
    updateDashboard();
    setInterval(updateDashboard, 300000); // 5 dakika
}); 