/**
 * @author A. Kerem Gök
 * Raporlar işlemleri
 */

// Rapor verilerini yükle
async function loadReport() {
    try {
        // Filtre değerlerini al
        const reportType = document.getElementById('reportType').value;
        const dateRange = document.getElementById('dateRange').value;
        const currency = document.getElementById('currency').value;

        // Özel tarih aralığı için kontrol
        let startDate, endDate;
        if (dateRange === 'custom') {
            startDate = document.getElementById('startDate').value;
            endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                throw new Error('Lütfen tarih aralığı seçin');
            }
        }

        // Yükleniyor durumunu göster
        showLoadingState();

        // API parametrelerini oluştur
        const params = new URLSearchParams({
            type: reportType,
            date_range: dateRange,
            currency: currency,
            start_date: startDate || '',
            end_date: endDate || '',
            csrf_token: CSRF_TOKEN
        });

        const response = await fetchAPI(`/api/reports.php?${params.toString()}`);
        
        if (response.success) {
            // Rapor özetini güncelle
            updateReportSummary(response.data.summary);

            // Grafikleri güncelle
            updateMainChart(response.data.main_chart);
            updateCategoryChart(response.data.category_chart);
            updateTrendChart(response.data.trend_chart);

            // Detay tablosunu güncelle
            updateDetailsTable(response.data.details);
        } else {
            throw new Error(response.error || 'Rapor yüklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Rapor yükleme hatası:', error);
        showError(error.message);
    }
}

// Yükleniyor durumunu göster
function showLoadingState() {
    // Özet kartları
    document.querySelectorAll('.summary-card .amount').forEach(el => {
        el.textContent = 'Yükleniyor...';
    });

    // Grafik konteynerleri
    document.querySelectorAll('canvas').forEach(canvas => {
        canvas.style.opacity = '0.5';
    });

    // Detay tablosu
    const table = document.getElementById('detailsTable').querySelector('tbody');
    table.innerHTML = '<tr><td colspan="6" class="loading">Yükleniyor...</td></tr>';
}

// Hata mesajını göster
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Hata',
        text: message,
        confirmButtonText: 'Tamam'
    });
}

// Rapor özetini güncelle
function updateReportSummary(summary) {
    document.getElementById('totalIncome').textContent = formatMoney(summary.total_income, summary.currency);
    document.getElementById('totalExpense').textContent = formatMoney(summary.total_expense, summary.currency);
    document.getElementById('netBalance').textContent = formatMoney(summary.net_balance, summary.currency);
    document.getElementById('savingsRate').textContent = `%${summary.savings_rate.toFixed(1)}`;

    // Trend oklarını güncelle
    updateTrendIndicator('totalIncome', summary.income_trend);
    updateTrendIndicator('totalExpense', summary.expense_trend);
    updateTrendIndicator('netBalance', summary.balance_trend);
    updateTrendIndicator('savingsRate', summary.savings_trend);
}

// Trend göstergesini güncelle
function updateTrendIndicator(elementId, trend) {
    const element = document.querySelector(`#${elementId} .trend`);
    element.innerHTML = trend > 0 ? '↑' : trend < 0 ? '↓' : '→';
    element.className = `trend ${trend > 0 ? 'positive' : trend < 0 ? 'negative' : 'neutral'}`;
}

// Ana grafiği güncelle
function updateMainChart(data) {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    // Mevcut grafiği temizle
    if (window.mainChart) {
        window.mainChart.destroy();
    }

    // Rapor tipine göre grafik oluştur
    window.mainChart = createMainChart(ctx, data);
}

// Ana grafik oluştur
function createMainChart(ctx, data) {
    const reportType = document.getElementById('reportType').value;
    
    switch (reportType) {
        case 'overview':
            return createMixedChart(ctx, {
                labels: data.labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Gelir',
                        data: data.income,
                        color: 'success'
                    },
                    {
                        type: 'line',
                        label: 'Gider',
                        data: data.expense,
                        color: 'danger'
                    },
                    {
                        type: 'bar',
                        label: 'Net',
                        data: data.net,
                        color: 'primary'
                    }
                ]
            }, {
                title: 'Genel Bakış',
                yAxisCallback: value => formatMoney(value)
            });

        case 'income':
        case 'expense':
            return createBarChart(ctx, {
                labels: data.labels,
                datasets: [{
                    label: reportType === 'income' ? 'Gelir' : 'Gider',
                    data: data.values,
                    color: reportType === 'income' ? 'success' : 'danger'
                }]
            }, {
                title: reportType === 'income' ? 'Gelir Analizi' : 'Gider Analizi',
                yAxisCallback: value => formatMoney(value)
            });

        case 'savings':
            return createLineChart(ctx, {
                labels: data.labels,
                datasets: [{
                    label: 'Birikim',
                    data: data.values,
                    color: 'info'
                }]
            }, {
                title: 'Birikim Analizi',
                yAxisCallback: value => formatMoney(value)
            });

        case 'bills':
            return createBarChart(ctx, {
                labels: data.labels,
                datasets: [{
                    label: 'Faturalar',
                    data: data.values,
                    color: 'warning'
                }]
            }, {
                title: 'Fatura Analizi',
                yAxisCallback: value => formatMoney(value)
            });
    }
}

// Kategori grafiğini güncelle
function updateCategoryChart(data) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    // Mevcut grafiği temizle
    if (window.categoryChart) {
        window.categoryChart.destroy();
    }

    // Pasta grafik oluştur
    window.categoryChart = createPieChart(ctx, {
        labels: data.labels,
        values: data.values
    }, {
        title: 'Kategori Dağılımı'
    });
}

// Trend grafiğini güncelle
function updateTrendChart(data) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Mevcut grafiği temizle
    if (window.trendChart) {
        window.trendChart.destroy();
    }

    // Çizgi grafik oluştur
    window.trendChart = createLineChart(ctx, {
        labels: data.labels,
        datasets: [{
            label: 'Trend',
            data: data.values,
            color: 'secondary'
        }]
    }, {
        title: 'Aylık Trend',
        yAxisCallback: value => formatMoney(value)
    });
}

// Detay tablosunu güncelle
function updateDetailsTable(data) {
    const table = document.getElementById('detailsTable').querySelector('tbody');
    
    if (data.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="no-data">Kayıt bulunamadı</td></tr>';
        return;
    }

    table.innerHTML = data.map(item => `
        <tr>
            <td>${formatDate(item.date)}</td>
            <td>${item.type === 'income' ? 'Gelir' : 'Gider'}</td>
            <td>${item.category}</td>
            <td>${item.description}</td>
            <td class="amount ${item.type}">
                ${item.type === 'income' ? '+' : '-'} ${formatMoney(item.amount, item.currency)}
            </td>
            <td>${item.currency}</td>
        </tr>
    `).join('');
}

// Rapor tipini değiştir
function changeReportType() {
    // Grafikleri sıfırla
    if (window.mainChart) window.mainChart.destroy();
    if (window.categoryChart) window.categoryChart.destroy();
    if (window.trendChart) window.trendChart.destroy();

    // Yeni raporu yükle
    loadReport();
}

// Raporu dışa aktar
async function exportReport(format) {
    try {
        const params = new URLSearchParams({
            type: document.getElementById('reportType').value,
            date_range: document.getElementById('dateRange').value,
            currency: document.getElementById('currency').value,
            format: format,
            csrf_token: CSRF_TOKEN
        });

        if (params.get('date_range') === 'custom') {
            params.append('start_date', document.getElementById('startDate').value);
            params.append('end_date', document.getElementById('endDate').value);
        }

        const response = await fetchAPI(`/api/reports.php/export?${params.toString()}`);
        
        if (response.success) {
            // Dosyayı indir
            const link = document.createElement('a');
            link.href = response.data.url;
            link.download = response.data.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            throw new Error(response.error || 'Rapor dışa aktarılırken bir hata oluştu');
        }
    } catch (error) {
        console.error('Rapor dışa aktarma hatası:', error);
        showError(error.message);
    }
}

// Özel rapor oluştur
async function handleCustomReport(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const data = formDataToJSON(form);
        data.csrf_token = CSRF_TOKEN;

        const response = await fetchAPI('/api/reports.php/custom', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            // Raporu göster
            updateMainChart(response.data.main_chart);
            updateCategoryChart(response.data.category_chart);
            updateTrendChart(response.data.trend_chart);
            updateDetailsTable(response.data.details);

            closeCustomReportModal();
        } else {
            throw new Error(response.error || 'Özel rapor oluşturulurken bir hata oluştu');
        }
    } catch (error) {
        console.error('Özel rapor oluşturma hatası:', error);
        showError(error.message);
    }
}

// Modal işlemleri
function showCustomReportModal() {
    document.getElementById('customReportModal').classList.add('show');
}

function closeCustomReportModal() {
    document.getElementById('customReportModal').classList.remove('show');
    document.getElementById('customReportForm').reset();
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    loadReport();
}); 