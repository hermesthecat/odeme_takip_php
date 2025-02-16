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
            <h2>Ödeme Hatırlatıcıları</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                <i class="bi bi-plus-circle"></i> Hatırlatıcı Ekle
            </button>
        </div>
    </div>

    <!-- Reminders Overview -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#upcomingTab">
                            Yaklaşan Ödemeler
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#allRemindersTab">
                            Tüm Hatırlatıcılar
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="upcomingTab">
                        <div class="list-group" id="upcomingReminders">
                            <!-- Upcoming reminders will be inserted here -->
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Yükleniyor...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="allRemindersTab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ödeme</th>
                                        <th>Gün</th>
                                        <th>Hatırlatma</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody id="allReminders">
                                    <tr>
                                        <td colspan="5" class="text-center">Yükleniyor...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Ödeme Takvimi</h5>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Due Today -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bugün Ödenmesi Gerekenler</h5>
            </div>
            <div class="card-body">
                <div id="dueToday">
                    <!-- Due today reminders will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Reminder Modal -->
<div class="modal fade" id="addReminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addReminderForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Hatırlatıcı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Ödeme Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="due_day" class="form-label">Ödeme Günü</label>
                        <input type="number" class="form-control" id="due_day" name="due_day" 
                               min="1" max="31" required>
                        <small class="form-text text-muted">Ayın hangi günü ödenmesi gerekiyor?</small>
                    </div>

                    <div class="mb-3">
                        <label for="reminder_days" class="form-label">Hatırlatma Günü</label>
                        <input type="number" class="form-control" id="reminder_days" name="reminder_days" 
                               min="1" max="15" value="3" required>
                        <small class="form-text text-muted">Kaç gün önce hatırlatılsın?</small>
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

<!-- Edit Reminder Modal -->
<div class="modal fade" id="editReminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editReminderForm">
                <div class="modal-header">
                    <h5 class="modal-title">Hatırlatıcı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Ödeme Adı</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_due_day" class="form-label">Ödeme Günü</label>
                        <input type="number" class="form-control" id="edit_due_day" name="due_day" 
                               min="1" max="31" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_reminder_days" class="form-label">Hatırlatma Günü</label>
                        <input type="number" class="form-control" id="edit_reminder_days" name="reminder_days" 
                               min="1" max="15" required>
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

<script>
// Load upcoming reminders
async function loadUpcomingReminders() {
    try {
        const response = await fetch('/api/reminders?action=upcoming');
        const data = await response.json();
        
        if(data.success) {
            const remindersList = document.getElementById('upcomingReminders');
            
            if(data.data.length === 0) {
                remindersList.innerHTML = `
                    <div class="text-center text-muted p-4">
                        Yaklaşan ödeme bulunmuyor
                    </div>
                `;
                return;
            }

            remindersList.innerHTML = data.data.map(reminder => `
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${reminder.name}</h6>
                        <small class="text-${reminder.status === 'overdue' ? 'danger' : 
                                          reminder.status === 'due_today' ? 'warning' : 'success'}">
                            ${reminder.days_until_due} gün kaldı
                        </small>
                    </div>
                    <p class="mb-1">Vade: ${reminder.due_date}</p>
                    <small class="text-muted">
                        ${reminder.reminder_days} gün önce hatırlatılacak
                    </small>
                </div>
            `).join('');
        }
    } catch(error) {
        console.error('Upcoming reminders loading error:', error);
    }
}

// Load all reminders
async function loadAllReminders() {
    try {
        const response = await fetch('/api/reminders');
        const data = await response.json();
        
        if(data.success) {
            const remindersList = document.getElementById('allReminders');
            
            if(data.data.length === 0) {
                remindersList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">
                            Hatırlatıcı bulunmuyor
                        </td>
                    </tr>
                `;
                return;
            }

            remindersList.innerHTML = data.data.map(reminder => `
                <tr>
                    <td>${reminder.name}</td>
                    <td>${reminder.due_day}</td>
                    <td>${reminder.reminder_days} gün önce</td>
                    <td>
                        <span class="badge ${reminder.status === 'overdue' ? 'bg-danger' : 
                                          reminder.status === 'due_today' ? 'bg-warning' : 'bg-success'}">
                            ${reminder.status === 'overdue' ? 'Gecikmiş' : 
                              reminder.status === 'due_today' ? 'Bugün' : 'Normal'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editReminder(${reminder.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteReminder(${reminder.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch(error) {
        console.error('All reminders loading error:', error);
    }
}

// Load due today reminders
async function loadDueToday() {
    try {
        const response = await fetch('/api/reminders?action=due-today');
        const data = await response.json();
        
        if(data.success) {
            const dueTodayList = document.getElementById('dueToday');
            
            if(data.data.length === 0) {
                dueTodayList.innerHTML = `
                    <div class="alert alert-info">
                        Bugün ödenmesi gereken fatura bulunmuyor
                    </div>
                `;
                return;
            }

            dueTodayList.innerHTML = `
                <div class="row">
                    ${data.data.map(reminder => `
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6 class="card-title">${reminder.name}</h6>
                                    <p class="card-text text-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Bugün ödenmesi gerekiyor
                                    </p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    } catch(error) {
        console.error('Due today reminders loading error:', error);
    }
}

// Add new reminder
document.getElementById('addReminderForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const response = await fetch('/api/reminders', {
            method: 'POST',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addReminderModal')).hide();
            this.reset();
            loadUpcomingReminders();
            loadAllReminders();
            loadDueToday();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Hatırlatıcı eklenemedi');
        }
    } catch(error) {
        console.error('Add reminder error:', error);
        alert('Hatırlatıcı eklenirken bir hata oluştu');
    }
});

// Edit reminder
async function editReminder(id) {
    try {
        const response = await fetch(`/api/reminders?id=${id}&action=status`);
        const data = await response.json();
        
        if(data.success) {
            const reminder = data.data;
            document.getElementById('edit_id').value = reminder.id;
            document.getElementById('edit_name').value = reminder.name;
            document.getElementById('edit_due_day').value = reminder.due_day;
            document.getElementById('edit_reminder_days').value = reminder.reminder_days;
            
            new bootstrap.Modal(document.getElementById('editReminderModal')).show();
        }
    } catch(error) {
        console.error('Edit reminder error:', error);
        alert('Hatırlatıcı bilgileri yüklenirken bir hata oluştu');
    }
}

document.getElementById('editReminderForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    try {
        const id = document.getElementById('edit_id').value;
        const response = await fetch(`/api/reminders?id=${id}`, {
            method: 'PUT',
            body: new FormData(this),
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editReminderModal')).hide();
            loadUpcomingReminders();
            loadAllReminders();
            loadDueToday();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Hatırlatıcı güncellenemedi');
        }
    } catch(error) {
        console.error('Update reminder error:', error);
        alert('Hatırlatıcı güncellenirken bir hata oluştu');
    }
});

// Delete reminder
async function deleteReminder(id) {
    if(!confirm('Bu hatırlatıcıyı silmek istediğinizden emin misiniz?')) {
        return;
    }

    try {
        const response = await fetch(`/api/reminders?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        const data = await response.json();

        if(data.success) {
            loadUpcomingReminders();
            loadAllReminders();
            loadDueToday();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Hatırlatıcı silinemedi');
        }
    } catch(error) {
        console.error('Delete reminder error:', error);
        alert('Hatırlatıcı silinirken bir hata oluştu');
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadUpcomingReminders();
    loadAllReminders();
    loadDueToday();
});
</script>
