<?php

/**
 * @author A. Kerem Gök
 */

require_once '../includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="expenses-page">
    <div class="page-header">
        <h2>Gider Yönetimi</h2>
        <button class="btn-primary" onclick="showAddExpenseModal()">
            <i class="fas fa-plus"></i> Yeni Gider Ekle
        </button>
    </div>

    <!-- Filtreler -->
    <div class="filters">
        <div class="filter-group">
            <label for="dateRange">Tarih Aralığı:</label>
            <select id="dateRange" onchange="filterExpenses()">
                <option value="all">Tümü</option>
                <option value="this-month">Bu Ay</option>
                <option value="last-month">Geçen Ay</option>
                <option value="this-year">Bu Yıl</option>
                <option value="custom">Özel Aralık</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="category">Kategori:</label>
            <select id="category" onchange="filterExpenses()">
                <option value="all">Tümü</option>
                <option value="bills">Faturalar</option>
                <option value="rent">Kira</option>
                <option value="food">Gıda</option>
                <option value="transportation">Ulaşım</option>
                <option value="shopping">Alışveriş</option>
                <option value="health">Sağlık</option>
                <option value="education">Eğitim</option>
                <option value="entertainment">Eğlence</option>
                <option value="other">Diğer</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="status">Durum:</label>
            <select id="status" onchange="filterExpenses()">
                <option value="all">Tümü</option>
                <option value="pending">Bekleyen</option>
                <option value="paid">Ödendi</option>
                <option value="overdue">Gecikmiş</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="currency">Para Birimi:</label>
            <select id="currency" onchange="filterExpenses()">
                <option value="all">Tümü</option>
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
        </div>
    </div>

    <!-- Gider Tablosu -->
    <div class="table-responsive">
        <table class="data-table" id="expensesTable">
            <thead>
                <tr>
                    <th>Son Ödeme</th>
                    <th>Ödeme Tarihi</th>
                    <th>Açıklama</th>
                    <th>Kategori</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="loading">Yükleniyor...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Yeni Gider Modalı -->
<div class="modal" id="addExpenseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Gider Ekle</h3>
            <button class="close-btn" onclick="closeAddExpenseModal()">&times;</button>
        </div>
        <form id="addExpenseForm" onsubmit="return handleAddExpense(event)">
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
                <label for="due_date">Son Ödeme Tarihi:</label>
                <input type="date" id="due_date" name="due_date" required>
            </div>

            <div class="form-group">
                <label for="payment_date">Ödeme Tarihi:</label>
                <input type="date" id="payment_date" name="payment_date">
            </div>

            <div class="form-group">
                <label for="category">Kategori:</label>
                <select id="category" name="category" required>
                    <option value="bills">Faturalar</option>
                    <option value="rent">Kira</option>
                    <option value="food">Gıda</option>
                    <option value="transportation">Ulaşım</option>
                    <option value="shopping">Alışveriş</option>
                    <option value="health">Sağlık</option>
                    <option value="education">Eğitim</option>
                    <option value="entertainment">Eğlence</option>
                    <option value="other">Diğer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Durum:</label>
                <select id="status" name="status" required>
                    <option value="pending">Bekleyen</option>
                    <option value="paid">Ödendi</option>
                    <option value="overdue">Gecikmiş</option>
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
                <button type="button" class="btn-secondary" onclick="closeAddExpenseModal()">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Düzenleme Modalı -->
<div class="modal" id="editExpenseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gider Düzenle</h3>
            <button class="close-btn" onclick="closeEditExpenseModal()">&times;</button>
        </div>
        <form id="editExpenseForm" onsubmit="return handleEditExpense(event)">
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
                <label for="edit_due_date">Son Ödeme Tarihi:</label>
                <input type="date" id="edit_due_date" name="due_date" required>
            </div>

            <div class="form-group">
                <label for="edit_payment_date">Ödeme Tarihi:</label>
                <input type="date" id="edit_payment_date" name="payment_date">
            </div>

            <div class="form-group">
                <label for="edit_category">Kategori:</label>
                <select id="edit_category" name="category" required>
                    <option value="bills">Faturalar</option>
                    <option value="rent">Kira</option>
                    <option value="food">Gıda</option>
                    <option value="transportation">Ulaşım</option>
                    <option value="shopping">Alışveriş</option>
                    <option value="health">Sağlık</option>
                    <option value="education">Eğitim</option>
                    <option value="entertainment">Eğlence</option>
                    <option value="other">Diğer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_status">Durum:</label>
                <select id="edit_status" name="status" required>
                    <option value="pending">Bekleyen</option>
                    <option value="paid">Ödendi</option>
                    <option value="overdue">Gecikmiş</option>
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
                <button type="button" class="btn-secondary" onclick="closeEditExpenseModal()">İptal</button>
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