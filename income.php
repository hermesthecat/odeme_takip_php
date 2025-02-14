<?php

/**
 * @author A. Kerem Gök
 */

require_once '../includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="income-page">
    <div class="page-header">
        <h2>Gelir Yönetimi</h2>
        <button class="btn-primary" onclick="showAddIncomeModal()">
            <i class="fas fa-plus"></i> Yeni Gelir Ekle
        </button>
    </div>

    <!-- Filtreler -->
    <div class="filters">
        <div class="filter-group">
            <label for="dateRange">Tarih Aralığı:</label>
            <select id="dateRange" onchange="filterIncomes()">
                <option value="all">Tümü</option>
                <option value="this-month">Bu Ay</option>
                <option value="last-month">Geçen Ay</option>
                <option value="this-year">Bu Yıl</option>
                <option value="custom">Özel Aralık</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="category">Kategori:</label>
            <select id="category" onchange="filterIncomes()">
                <option value="all">Tümü</option>
                <option value="salary">Maaş</option>
                <option value="freelance">Serbest Çalışma</option>
                <option value="investment">Yatırım</option>
                <option value="other">Diğer</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="currency">Para Birimi:</label>
            <select id="currency" onchange="filterIncomes()">
                <option value="all">Tümü</option>
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
        </div>
    </div>

    <!-- Gelir Tablosu -->
    <div class="table-responsive">
        <table class="data-table" id="incomesTable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Açıklama</th>
                    <th>Kategori</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>İşlemler</th>
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

<!-- Yeni Gelir Modalı -->
<div class="modal" id="addIncomeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Gelir Ekle</h3>
            <button class="close-btn" onclick="closeAddIncomeModal()">&times;</button>
        </div>
        <form id="addIncomeForm" onsubmit="return handleAddIncome(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="amount">Tutar:</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="description">Açıklama:</label>
                <input type="text" id="description" name="description" required>
            </div>

            <div class="form-group">
                <label for="income_date">Tarih:</label>
                <input type="date" id="income_date" name="income_date" required>
            </div>

            <div class="form-group">
                <label for="category">Kategori:</label>
                <select id="category" name="category" required>
                    <option value="salary">Maaş</option>
                    <option value="freelance">Serbest Çalışma</option>
                    <option value="investment">Yatırım</option>
                    <option value="other">Diğer</option>
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
                <button type="button" class="btn-secondary" onclick="closeAddIncomeModal()">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Düzenleme Modalı -->
<div class="modal" id="editIncomeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gelir Düzenle</h3>
            <button class="close-btn" onclick="closeEditIncomeModal()">&times;</button>
        </div>
        <form id="editIncomeForm" onsubmit="return handleEditIncome(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group">
                <label for="edit_amount">Tutar:</label>
                <input type="number" id="edit_amount" name="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="edit_description">Açıklama:</label>
                <input type="text" id="edit_description" name="description" required>
            </div>

            <div class="form-group">
                <label for="edit_income_date">Tarih:</label>
                <input type="date" id="edit_income_date" name="income_date" required>
            </div>

            <div class="form-group">
                <label for="edit_category">Kategori:</label>
                <select id="edit_category" name="category" required>
                    <option value="salary">Maaş</option>
                    <option value="freelance">Serbest Çalışma</option>
                    <option value="investment">Yatırım</option>
                    <option value="other">Diğer</option>
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
                <button type="button" class="btn-secondary" onclick="closeEditIncomeModal()">İptal</button>
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