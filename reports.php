<?php
/**
 * @author A. Kerem Gök
 */

require_once '../includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="reports-page">
    <div class="page-header">
        <h2>Finansal Raporlar</h2>
        <div class="report-actions">
            <button class="btn-secondary" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf"></i> PDF İndir
            </button>
            <button class="btn-secondary" onclick="exportReport('excel')">
                <i class="fas fa-file-excel"></i> Excel İndir
            </button>
        </div>
    </div>

    <!-- Rapor Filtreleri -->
    <div class="filters">
        <div class="filter-group">
            <label for="reportType">Rapor Türü:</label>
            <select id="reportType" onchange="changeReportType()">
                <option value="overview">Genel Bakış</option>
                <option value="income">Gelir Analizi</option>
                <option value="expense">Gider Analizi</option>
                <option value="savings">Birikim Analizi</option>
                <option value="bills">Fatura Analizi</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="dateRange">Tarih Aralığı:</label>
            <select id="dateRange" onchange="updateReport()">
                <option value="this-month">Bu Ay</option>
                <option value="last-month">Geçen Ay</option>
                <option value="last-3-months">Son 3 Ay</option>
                <option value="last-6-months">Son 6 Ay</option>
                <option value="this-year">Bu Yıl</option>
                <option value="last-year">Geçen Yıl</option>
                <option value="custom">Özel Aralık</option>
            </select>
        </div>
        <div class="filter-group" id="customDateRange" style="display: none;">
            <label for="startDate">Başlangıç:</label>
            <input type="date" id="startDate" onchange="updateReport()">
            <label for="endDate">Bitiş:</label>
            <input type="date" id="endDate" onchange="updateReport()">
        </div>
        <div class="filter-group">
            <label for="currency">Para Birimi:</label>
            <select id="currency" onchange="updateReport()">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
        </div>
    </div>

    <!-- Rapor Özeti -->
    <div class="report-summary">
        <div class="summary-card">
            <h3>Toplam Gelir</h3>
            <div class="amount" id="totalIncome">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="summary-card">
            <h3>Toplam Gider</h3>
            <div class="amount" id="totalExpense">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="summary-card">
            <h3>Net Durum</h3>
            <div class="amount" id="netBalance">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="summary-card">
            <h3>Birikim Oranı</h3>
            <div class="percentage" id="savingsRate">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
    </div>

    <!-- Ana Grafik -->
    <div class="main-chart-container">
        <canvas id="mainChart"></canvas>
    </div>

    <!-- Alt Grafikler -->
    <div class="sub-charts">
        <div class="chart-card">
            <h3>Kategori Dağılımı</h3>
            <canvas id="categoryChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Aylık Trend</h3>
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Detaylı Tablo -->
    <div class="table-responsive">
        <table class="data-table" id="detailsTable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="loading">Yükleniyor...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Özel Rapor Modalı -->
<div class="modal" id="customReportModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Özel Rapor Oluştur</h3>
            <button class="close-btn" onclick="closeCustomReportModal()">&times;</button>
        </div>
        <form id="customReportForm" onsubmit="return handleCustomReport(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Rapor Bileşenleri:</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="components[]" value="income" checked> Gelirler
                    </label>
                    <label>
                        <input type="checkbox" name="components[]" value="expense" checked> Giderler
                    </label>
                    <label>
                        <input type="checkbox" name="components[]" value="savings" checked> Birikimler
                    </label>
                    <label>
                        <input type="checkbox" name="components[]" value="bills" checked> Faturalar
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Grafikler:</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="charts[]" value="pie" checked> Pasta Grafik
                    </label>
                    <label>
                        <input type="checkbox" name="charts[]" value="line" checked> Çizgi Grafik
                    </label>
                    <label>
                        <input type="checkbox" name="charts[]" value="bar" checked> Çubuk Grafik
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="grouping">Gruplama:</label>
                <select id="grouping" name="grouping">
                    <option value="daily">Günlük</option>
                    <option value="weekly">Haftalık</option>
                    <option value="monthly" selected>Aylık</option>
                    <option value="quarterly">3 Aylık</option>
                    <option value="yearly">Yıllık</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeCustomReportModal()">İptal</button>
                <button type="submit" class="btn-primary">Rapor Oluştur</button>
            </div>
        </form>
    </div>
</div>

<script>
// CSRF token'ı JavaScript'e aktar
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

// Tarih aralığı seçimi kontrolü
document.getElementById('dateRange').addEventListener('change', function() {
    const customDateRange = document.getElementById('customDateRange');
    customDateRange.style.display = this.value === 'custom' ? 'block' : 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?> 