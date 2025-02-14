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
            <h3>Toplam Gelir</h3>
            <div class="amount">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="card" id="expenseCard">
            <h3>Toplam Gider</h3>
            <div class="amount">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="card" id="balanceCard">
            <h3>Net Durum</h3>
            <div class="amount">Yükleniyor...</div>
            <div class="trend"></div>
        </div>
        <div class="card" id="savingsCard">
            <h3>Toplam Birikim</h3>
            <div class="amount">Yükleniyor...</div>
            <div class="progress-bar"></div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>Gelir/Gider Grafiği</h3>
            <canvas id="incomeExpenseChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Kategori Dağılımı</h3>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <!-- Son İşlemler ve Yaklaşan Ödemeler -->
    <div class="dashboard-bottom">
        <div class="recent-transactions">
            <h3>Son İşlemler</h3>
            <div class="transactions-list" id="recentTransactions">
                <div class="loading">Yükleniyor...</div>
            </div>
        </div>
        <div class="upcoming-payments">
            <h3>Yaklaşan Ödemeler</h3>
            <div class="payments-list" id="upcomingPayments">
                <div class="loading">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>

<!-- Döviz Kuru Widget'ı -->
<div class="currency-widget" id="currencyWidget">
    <h3>Döviz Kurları</h3>
    <div class="currency-rates">
        <div class="loading">Yükleniyor...</div>
    </div>
</div>

<script>
    // CSRF token'ı JavaScript'e aktar
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
</script>

<?php require_once 'includes/footer.php'; ?>