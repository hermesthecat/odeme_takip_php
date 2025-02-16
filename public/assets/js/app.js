// CSRF Token Management
function getCSRFToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : null;
}

// Currency Formatting
function formatCurrency(amount, currency = 'TRY') {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Date Formatting
function formatDate(date, format = 'short') {
    const d = new Date(date);
    const options = format === 'short' ? 
        { year: 'numeric', month: '2-digit', day: '2-digit' } : 
        { year: 'numeric', month: 'long', day: 'numeric' };
    
    return d.toLocaleDateString('tr-TR', options);
}

// Number Formatting
function formatNumber(number, decimals = 2) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// API Request Helper
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    try {
        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json();

        if(!response.ok) {
            throw new Error(data.errors ? data.errors.join('\n') : 'API request failed');
        }

        return data;
    } catch(error) {
        console.error('API request error:', error);
        throw error;
    }
}

// Input Validation
const validators = {
    required: value => !!value || 'Bu alan zorunludur',
    email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Geçerli bir e-posta adresi girin',
    minLength: (value, min) => value.length >= min || `En az ${min} karakter girin`,
    maxLength: (value, max) => value.length <= max || `En fazla ${max} karakter girin`,
    number: value => !isNaN(value) || 'Geçerli bir sayı girin',
    positive: value => value > 0 || 'Sıfırdan büyük bir değer girin',
    integer: value => Number.isInteger(Number(value)) || 'Tam sayı girin',
    date: value => !isNaN(Date.parse(value)) || 'Geçerli bir tarih girin'
};

function validateForm(formData, rules) {
    const errors = {};

    for(const [field, fieldRules] of Object.entries(rules)) {
        const value = formData.get(field);

        for(const rule of fieldRules) {
            let validatorName, validatorParam;

            if(typeof rule === 'string') {
                validatorName = rule;
            } else {
                [[validatorName, validatorParam]] = Object.entries(rule);
            }

            const validator = validators[validatorName];
            if(!validator) continue;

            const result = validator(value, validatorParam);
            if(result !== true) {
                errors[field] = result;
                break;
            }
        }
    }

    return {
        isValid: Object.keys(errors).length === 0,
        errors
    };
}

// Modal Helper
function createModal(options) {
    const template = `
        <div class="modal fade" id="${options.id}" tabindex="-1">
            <div class="modal-dialog ${options.size || ''}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${options.title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">${options.body}</div>
                    ${options.footer ? `
                        <div class="modal-footer">${options.footer}</div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;

    const modalElement = document.createElement('div');
    modalElement.innerHTML = template.trim();
    document.body.appendChild(modalElement.firstChild);

    const modal = new bootstrap.Modal(document.getElementById(options.id));
    
    if(options.onShow) {
        document.getElementById(options.id).addEventListener('shown.bs.modal', options.onShow);
    }
    
    if(options.onHide) {
        document.getElementById(options.id).addEventListener('hidden.bs.modal', options.onHide);
    }

    return modal;
}

// Toast Notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    const container = document.getElementById('toast-container') || (() => {
        const div = document.createElement('div');
        div.id = 'toast-container';
        div.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(div);
        return div;
    })();

    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Loading Indicator
const loadingIndicator = {
    show(container = document.body) {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = `
            <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>
        `;
        container.appendChild(spinner);
    },
    hide() {
        const spinner = document.querySelector('.loading-spinner');
        if(spinner) spinner.remove();
    }
};

// Confirmation Dialog
async function confirmDialog(options) {
    return new Promise(resolve => {
        const modal = createModal({
            id: 'confirmDialog',
            title: options.title || 'Onay',
            body: `<p>${options.message}</p>`,
            footer: `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-${options.type || 'primary'}" id="confirmOk">Tamam</button>
            `,
            onShow: () => {
                document.getElementById('confirmOk').onclick = () => {
                    modal.hide();
                    resolve(true);
                };
            },
            onHide: () => resolve(false)
        });
        
        modal.show();
    });
}

// Month Selector Helper
function createMonthSelector(container, onChange) {
    const now = new Date();
    const months = [];
    
    // Generate last 12 months
    for(let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        months.push({
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            label: date.toLocaleString('tr-TR', { month: 'long', year: 'numeric' })
        });
    }

    const select = document.createElement('select');
    select.className = 'form-select form-select-sm';
    select.innerHTML = months.map(m => 
        `<option value="${m.year}-${m.month}">${m.label}</option>`
    ).join('');

    select.addEventListener('change', e => {
        const [year, month] = e.target.value.split('-').map(Number);
        onChange(year, month);
    });

    container.appendChild(select);
    return select;
}

// Export functions
window.app = {
    getCSRFToken,
    formatCurrency,
    formatDate,
    formatNumber,
    apiRequest,
    validateForm,
    createModal,
    showToast,
    loadingIndicator,
    confirmDialog,
    createMonthSelector
};
