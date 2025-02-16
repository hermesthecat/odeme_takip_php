<?php
// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Birikim Hedefleri</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSavingModal">
                <i class="bi bi-plus-circle"></i> Hedef Ekle
            </button>
        </div>
    </div>

    <!-- Total Savings -->
    <div class="col-12 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-0">Toplam Birikim</h4>
                        <small>Tüm hedefler</small>
                    </div>
                    <div class="col-auto">
                        <div class="display-6" id="totalSavings">0,00 ₺</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Savings Goals -->
    <div class="col-12">
        <div class="row" id="savingsGrid">
            <!-- Saving cards will be inserted here -->
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Saving Modal -->
<div class="modal fade" id="addSavingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addSavingForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Birikim Hedefi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Hedef Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-8">
                            <label for="target_amount" class="form-label">Hedef Tutar</label>
                            <input type="number" class="form-control" id="target_amount" name="target_amount" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <label for="currency" class="form-label">Para Birimi</label>
                            <select class="form-select" id="currency" name="currency" required></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="current_amount" class="form-label">Mevcut Birikim</label>
                        <input type="number" class="form-control" id="current_amount" name="current_amount" step="0.01" value="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="target_date" class="form-label">Hedef Tarihi</label>
                        <input type="date" class="form-control" id="target_date" name="target_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateProgressForm">
                <div class="modal-header">
                    <h5 class="modal-title">Birikim Güncelle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="progress_id" name="id">
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Yeni Birikim</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        <small class="form-text text-muted">
                            Eklemek istediğiniz tutarı girin
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize date inputs with future date (1 year from now)
document.getElementById('target_date').value = new Date(
    new Date().setFullYear(new Date().getFullYear() + 1)
).toISOString().split('T')[0];

// Load currencies
async function loadCurrencies() {
    try {
        const response = await fetch('/api/exchange?action=currencies');
        const data = await response.json();
        
        if(data.success) {
            const currencies = Object.entries(data.data.currencies).map(([code, info]) => 
                `<option value="${code}">${info.symbol} ${code}</option>`
            ).join('');

            document.getElementById('currency').innerHTML = currencies;
        }
    } catch(error) {
        console.error('Currency loading error:', error);
    }
}

// Load and display savings
async function loadSavings() {
    try {
        const response = await fetch('/api/savings');
        const data = await response.json();
        
        if(data.success) {
            const savingsGrid = document.getElementById('savingsGrid');
            document.getElementById('totalSavings').textContent = data.data.total_savings_formatted;
            
            if(data.data.savings.length === 0) {
                savingsGrid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            Henüz birikim hedefiniz bulunmuyor. Yeni bir hedef ekleyebilirsiniz.
                        </div>
                    </div>
                `;
                return;
            }

            savingsGrid.innerHTML = data.data.savings.map(saving => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">${saving.name}</h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" onclick="updateProgress(${saving.id})">
                                            <i class="bi bi-plus-circle me-2"></i> Birikim Ekle
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteSaving(${saving.id})">
                                            <i class="bi bi-trash me-2"></i> Sil
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>İlerleme (${saving.progress.percentage}%)</small>
                                    <small>${saving.current_amount_formatted} / ${saving.target_amount_formatted}</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar ${saving.progress.percentage >= 100 ? 'bg-success' : ''}" 
                                         role="progressbar" 
                                         style="width: ${Math.min(saving.progress.percentage, 100)}%">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="d-block text-muted">Kalan</small>
                                    <strong>${saving.remaining_amount_formatted}</strong>
                                </div>
                                <div class="text-end">
                                    <small class="d-block text-muted">Hedef Tarihi</small>
                                    <strong>${saving.target_date}</strong>
                                </div>
                            </div>
                        </div>
                        ${saving.progress.percentage >= 100 ? `
                            <div class="card-footer bg-success text-white">
                                <i class="bi bi-check-circle me-2"></i> Hedefe ulaşıldı!
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch(error) {
        console.error('Savings loading error:', error);
        alert('Birikimler yüklenirken bir hata oluştu');
    }
}

// Add new saving
document.getElementById('addSavingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const response = await fetch('/api/savings', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addSavingModal')).hide();
            this.reset();
            loadSavings();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Birikim hedefi eklenemedi');
        }
    } catch(error) {
        console.error('Add saving error:', error);
        alert('Birikim hedefi eklenirken bir hata oluştu');
    }
});

// Update progress
function updateProgress(id) {
    document.getElementById('progress_id').value = id;
    new bootstrap.Modal(document.getElementById('updateProgressModal')).show();
}

document.getElementById('updateProgressForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const id = document.getElementById('progress_id').value;
        const response = await fetch(`/api/savings?id=${id}&action=progress`, {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('updateProgressModal')).hide();
            this.reset();
            loadSavings();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Birikim güncellenemedi');
        }
    } catch(error) {
        console.error('Update progress error:', error);
        alert('Birikim güncellenirken bir hata oluştu');
    }
});

// Delete saving
async function deleteSaving(id) {
    if(!confirm('Bu birikim hedefini silmek istediğinizden emin misiniz?')) {
        return;
    }

    try {
        const response = await fetch(`/api/savings?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            loadSavings();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Birikim hedefi silinemedi');
        }
    } catch(error) {
        console.error('Delete saving error:', error);
        alert('Birikim hedefi silinirken bir hata oluştu');
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    loadSavings();
});
</script>
