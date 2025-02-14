<?php
/**
 * @author A. Kerem Gök
 */

require_once 'includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="dashboard">
    <!-- Özet Kartları -->
    <div class="summary-cards">
        <div class="card" id="incomeCard">
            <div class="card-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="card-content">
                <h3>Toplam Gelir</h3>
                <div class="amount">Yükleniyor...</div>
                <div class="trend"></div>
            </div>
        </div>
        
        <div class="card" id="expenseCard">
            <div class="card-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="card-content">
                <h3>Toplam Gider</h3>
                <div class="amount">Yükleniyor...</div>
                <div class="trend"></div>
            </div>
        </div>
        
        <div class="card" id="balanceCard">
            <div class="card-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="card-content">
                <h3>Net Durum</h3>
                <div class="amount">Yükleniyor...</div>
                <div class="trend"></div>
            </div>
        </div>
        
        <div class="card" id="savingsCard">
            <div class="card-icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <div class="card-content">
                <h3>Toplam Birikim</h3>
                <div class="amount">Yükleniyor...</div>
                <div class="progress-bar"></div>
            </div>
        </div>
    </div>
    
    <!-- Grafikler -->
    <div class="charts-container">
        <div class="chart-card">
            <div class="chart-header">
                <h3>Gelir/Gider Grafiği</h3>
                <div class="chart-actions">
                    <select id="chartPeriod" onchange="updateIncomeExpenseChart()">
                        <option value="week">Bu Hafta</option>
                        <option value="month" selected>Bu Ay</option>
                        <option value="quarter">Bu Çeyrek</option>
                        <option value="year">Bu Yıl</option>
                    </select>
                </div>
            </div>
            <canvas id="incomeExpenseChart"></canvas>
        </div>
        
        <div class="chart-card">
            <div class="chart-header">
                <h3>Kategori Dağılımı</h3>
                <div class="chart-actions">
                    <select id="categoryType" onchange="updateCategoryChart()">
                        <option value="expense" selected>Giderler</option>
                        <option value="income">Gelirler</option>
                    </select>
                </div>
            </div>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <!-- Son İşlemler ve Yaklaşan Ödemeler -->
    <div class="dashboard-bottom">
        <div class="recent-transactions">
            <div class="section-header">
                <h3>Son İşlemler</h3>
                <a href="/pages/reports.php" class="btn-link">
                    Tümünü Gör <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="transactions-list" id="recentTransactions">
                <div class="loading">Yükleniyor...</div>
            </div>
        </div>
        
        <div class="upcoming-payments">
            <div class="section-header">
                <h3>Yaklaşan Ödemeler</h3>
                <a href="/pages/bills.php" class="btn-link">
                    Tümünü Gör <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="payments-list" id="upcomingPayments">
                <div class="loading">Yükleniyor...</div>
            </div>
        </div>
    </div>
    
    <!-- Döviz Kuru Widget'ı -->
    <div class="currency-widget" id="currencyWidget">
        <div class="widget-header">
            <h3>Döviz Kurları</h3>
            <span class="last-update">Son güncelleme: <span id="lastUpdateTime">Yükleniyor...</span></span>
        </div>
        <div class="currency-rates">
            <div class="loading">Yükleniyor...</div>
        </div>
    </div>
    
    <!-- Hızlı İşlem Butonları -->
    <div class="quick-actions">
        <button onclick="window.location.href='/pages/income.php?action=add'" class="btn-floating" title="Gelir Ekle">
            <i class="fas fa-plus"></i>
        </button>
        <button onclick="window.location.href='/pages/expenses.php?action=add'" class="btn-floating" title="Gider Ekle">
            <i class="fas fa-minus"></i>
        </button>
        <button onclick="window.location.href='/pages/bills.php?action=add'" class="btn-floating" title="Fatura Ekle">
            <i class="fas fa-file-invoice"></i>
        </button>
        <button onclick="window.location.href='/pages/savings.php?action=add'" class="btn-floating" title="Birikim Hedefi Ekle">
            <i class="fas fa-piggy-bank"></i>
        </button>
    </div>
</div>

<script>
// CSRF token'ı JavaScript'e aktar
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    // Dashboard verilerini yükle
    updateDashboard();
    
    // Her 5 dakikada bir güncelle
    setInterval(updateDashboard, 300000);
});
</script>

<?php require_once 'includes/footer.php'; ?> 