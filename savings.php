<?php
/**
 * @author A. Kerem Gök
 */

require_once '../includes/header.php';
checkAuth();

// CSRF token oluştur
$csrf_token = generateToken();
?>

<div class="savings-page">
    <div class="page-header">
        <h2>Birikim Hedefleri</h2>
        <button class="btn-primary" onclick="showAddSavingModal()">
            <i class="fas fa-plus"></i> Yeni Hedef Ekle
        </button>
    </div>

    <!-- Birikim Hedefleri Kartları -->
    <div class="savings-grid" id="savingsGrid">
        <div class="loading">Yükleniyor...</div>
    </div>

    <!-- Birikim İstatistikleri -->
    <div class="savings-stats">
        <div class="stats-card">
            <h3>Toplam Birikim</h3>
            <div class="amount" id="totalSavings">Yükleniyor...</div>
        </div>
        <div class="stats-card">
            <h3>Hedeflere Ulaşma Oranı</h3>
            <div class="progress-circle" id="achievementRate">
                <div class="progress-text">0%</div>
            </div>
        </div>
        <div class="stats-card">
            <h3>Aktif Hedefler</h3>
            <div class="count" id="activeGoals">0</div>
        </div>
    </div>

    <!-- Birikim Grafiği -->
    <div class="chart-container">
        <h3>Birikim Trendi</h3>
        <canvas id="savingsChart"></canvas>
    </div>
</div>

<!-- Yeni Birikim Hedefi Modalı -->
<div class="modal" id="addSavingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Yeni Birikim Hedefi</h3>
            <button class="close-btn" onclick="closeAddSavingModal()">&times;</button>
        </div>
        <form id="addSavingForm" onsubmit="return handleAddSaving(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="description">Hedef Adı:</label>
                <input type="text" id="description" name="description" required>
            </div>

            <div class="form-group">
                <label for="target_amount">Hedef Miktar:</label>
                <input type="number" id="target_amount" name="target_amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="current_amount">Mevcut Birikim:</label>
                <input type="number" id="current_amount" name="current_amount" step="0.01" value="0" required>
            </div>

            <div class="form-group">
                <label for="target_date">Hedef Tarih:</label>
                <input type="date" id="target_date" name="target_date" required>
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
                <button type="button" class="btn-secondary" onclick="closeAddSavingModal()">İptal</button>
                <button type="submit" class="btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Düzenleme Modalı -->
<div class="modal" id="editSavingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Birikim Hedefi Düzenle</h3>
            <button class="close-btn" onclick="closeEditSavingModal()">&times;</button>
        </div>
        <form id="editSavingForm" onsubmit="return handleEditSaving(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_description">Hedef Adı:</label>
                <input type="text" id="edit_description" name="description" required>
            </div>

            <div class="form-group">
                <label for="edit_target_amount">Hedef Miktar:</label>
                <input type="number" id="edit_target_amount" name="target_amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="edit_current_amount">Mevcut Birikim:</label>
                <input type="number" id="edit_current_amount" name="current_amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="edit_target_date">Hedef Tarih:</label>
                <input type="date" id="edit_target_date" name="target_date" required>
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
                <button type="button" class="btn-secondary" onclick="closeEditSavingModal()">İptal</button>
                <button type="submit" class="btn-primary">Güncelle</button>
            </div>
        </form>
    </div>
</div>

<!-- Hızlı Güncelleme Modalı -->
<div class="modal" id="quickUpdateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Birikim Güncelle</h3>
            <button class="close-btn" onclick="closeQuickUpdateModal()">&times;</button>
        </div>
        <form id="quickUpdateForm" onsubmit="return handleQuickUpdate(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="quick_update_id">
            
            <div class="form-group">
                <label for="quick_amount">Yeni Birikim Miktarı:</label>
                <input type="number" id="quick_amount" name="amount" step="0.01" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeQuickUpdateModal()">İptal</button>
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