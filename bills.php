<?php

/**
 * @author A. Kerem Gök
 */

require_once '../includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="bills-page">
    <div class="page-header">
        <h2>Fatura Hatırlatıcıları</h2>
        <button class="btn-primary" onclick="showAddBillModal()">
            <i class="fas fa-plus"></i> Yeni Fatura Ekle
        </button>
    </div>

    <!-- Yaklaşan Faturalar -->
    <div class="upcoming-bills">
        <h3>Yaklaşan Faturalar</h3>
        <div class="bills-timeline" id="upcomingBills">
            <div class="loading">Yükleniyor...</div>
        </div>
    </div>

    <!-- Fatura İstatistikleri -->
    <div class="bills-stats">
        <div class="stats-card">
            <h3>Bu Ay Ödenecek</h3>
            <div class="amount" id="monthlyTotal">Yükleniyor...</div>
        </div>
        <div class="stats-card">
            <h3>Geciken Ödemeler</h3>
            <div class="amount warning" id="overdueTotal">Yükleniyor...</div>
        </div>
        <div class="stats-card">
            <h3>Aktif Hatırlatıcılar</h3>
            <div class="count" id="activeReminders">0</div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="filters">
        <div class="filter-group">
            <label for="repeatInterval">Tekrar:</label>
            <select id="repeatInterval" onchange="filterBills()">
                <option value="all">Tümü</option>
                <option value="monthly">Aylık</option>
                <option value="quarterly">3 Aylık</option>
                <option value="yearly">Yıllık</option>
                <option value="once">Tek Seferlik</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="status">Durum:</label>
            <select id="status" onchange="filterBills()">
                <option value="all">Tümü</option>
                <option value="pending">Bekleyen</option>
                <option value="paid">Ödendi</option>
                <option value="overdue">Gecikmiş</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="currency">Para Birimi:</label>
            <select id="currency" onchange="filterBills()">
                <option value="all">Tümü</option>
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
        </div>
    </div>

    <!-- Faturalar Tablosu -->
    <div class="table-responsive">
        <table class="data-table" id="billsTable">
            <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Son Ödeme</th>
                    <th>Tekrar</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="loading">Yükleniyor...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Yeni Fatura Modalı -->
<div class="modal" id="addBillModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Fatura Hatırlatıcısı</h3>
            <button class="close-btn" onclick="closeAddBillModal()">&times;</button>
        </div>
        <form id="addBillForm" onsubmit="return handleAddBill(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="title">Başlık:</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="amount">Tutar:</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="due_date">Son Ödeme Tarihi:</label>
                <input type="date" id="due_date" name="due_date" required>
            </div>

            <div class="form-group">
                <label for="repeat_interval">Tekrar:</label>
                <select id="repeat_interval" name="repeat_interval" required>
                    <option value="monthly">Aylık</option>
                    <option value="quarterly">3 Aylık</option>
                    <option value="yearly">Yıllık</option>
                    <option value="once">Tek Seferlik</option>
                </select>
            </div>

            <div class="form-group">
                <label for="currency">Para Birimi:</label>
                <select id="currency" name="currency" required>
                    <option value="TRY">TRY</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeAddBillModal()">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Düzenleme Modalı -->
<div class="modal" id="editBillModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Fatura Düzenle</h3>
            <button class="close-btn" onclick="closeEditBillModal()">&times;</button>
        </div>
        <form id="editBillForm" onsubmit="return handleEditBill(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group">
                <label for="edit_title">Başlık:</label>
                <input type="text" id="edit_title" name="title" required>
            </div>

            <div class="form-group">
                <label for="edit_amount">Tutar:</label>
                <input type="number" id="edit_amount" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="edit_due_date">Son Ödeme Tarihi:</label>
                <input type="date" id="edit_due_date" name="due_date" required>
            </div>

            <div class="form-group">
                <label for="edit_repeat_interval">Tekrar:</label>
                <select id="edit_repeat_interval" name="repeat_interval" required>
                    <option value="monthly">Aylık</option>
                    <option value="quarterly">3 Aylık</option>
                    <option value="yearly">Yıllık</option>
                    <option value="once">Tek Seferlik</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_currency">Para Birimi:</label>
                <select id="edit_currency" name="currency" required>
                    <option value="TRY">TRY</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeEditBillModal()">İptal</button>
                <button type="submit" class="btn-primary">Güncelle</button>
            </div>
        </form>
    </div>
</div>

<script>
    // CSRF token'ı JavaScript'e aktar
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
</script>

<?php require_once '../includes/footer.php'; ?>