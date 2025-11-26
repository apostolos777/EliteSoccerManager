// VIVO United Football Manager - Enhanced JavaScript
// Updated: August 29, 2025

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Could send to error tracking service
});

// Global AJAX error handler
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    console.error('AJAX Error:', {
        url: settings.url,
        status: xhr.status,
        error: thrownError
    });
});

// Enhanced mobile navigation
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && mobileMenuOverlay && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            mobileMenuOverlay.classList.toggle('active');
        });

        mobileMenuOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            mobileMenuOverlay.classList.remove('active');
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });

    // Enhanced delete confirmations
    const deleteButtons = document.querySelectorAll('a[href*="delete"], button[data-action="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const itemType = this.dataset.itemType || 'item';
            const itemName = this.dataset.itemName || 'this item';

            if (!confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Enhanced search functionality
    const searchInputs = document.querySelectorAll('input[type="search"], input[name="q"]');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    });

    // Table sorting
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = this.dataset.sort;
            const direction = this.dataset.direction || 'asc';

            rows.sort((a, b) => {
                const aVal = a.querySelector(`[data-${column}]`)?.textContent || '';
                const bVal = b.querySelector(`[data-${column}]`)?.textContent || '';

                if (direction === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });

            // Update direction
            this.dataset.direction = direction === 'asc' ? 'desc' : 'asc';
            this.querySelector('.sort-icon')?.classList.toggle('fa-sort-up');
            this.querySelector('.sort-icon')?.classList.toggle('fa-sort-down');

            // Reorder rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // Quick attendance functionality
    initQuickAttendance();

    // Initialize tooltips
    initTooltips();

    // Initialize modals
    initModals();
});

// Quick attendance system
function initQuickAttendance() {
    const attendanceButtons = document.querySelectorAll('.quick-attendance-btn');
    attendanceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const eventId = this.dataset.eventId;
            const playerId = this.dataset.playerId;
            const status = this.dataset.status;

            updateAttendance(eventId, playerId, status, this);
        });
    });
}

function updateAttendance(eventId, playerId, status, button) {
    if (!button) return;

    const originalText = button.textContent;
    button.textContent = 'Updating...';
    button.disabled = true;

    fetch('save_event_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `event_id=${eventId}&player_id=${playerId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const row = button.closest('tr');
            const statusCell = row.querySelector('.attendance-status');

            if (statusCell) {
                statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusCell.className = `attendance-status status-${status}`;
            }

            // Update button states
            const buttons = row.querySelectorAll('.quick-attendance-btn');
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.status === status) {
                    btn.classList.add('active');
                }
            });

            showAlert('Attendance updated successfully', 'success');
        } else {
            throw new Error(data.error || 'Failed to update attendance');
        }
    })
    .catch(error => {
        console.error('Attendance update error:', error);
        showAlert('Failed to update attendance: ' + error.message, 'error');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
    `;

    const container = document.querySelector('.content-main') || document.body;
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) + 'px';
            tooltip.style.top = rect.top - 30 + 'px';
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
}

function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);

            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') || e.target.classList.contains('modal-close')) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => modal.style.display = 'none');
            document.body.style.overflow = '';
        }
    });
}

// Form enhancement
function enhanceForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Auto-save drafts for long forms
        const inputs = form.querySelectorAll('input, textarea, select');
        if (inputs.length > 5) {
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    saveFormDraft(form.id || 'form', this.name, this.value);
                });
            });
        }
    });
}

function saveFormDraft(formId, fieldName, value) {
    const drafts = JSON.parse(localStorage.getItem('form_drafts') || '{}');
    if (!drafts[formId]) drafts[formId] = {};
    drafts[formId][fieldName] = value;
    drafts[formId].timestamp = Date.now();
    localStorage.setItem('form_drafts', JSON.stringify(drafts));
}

function loadFormDrafts() {
    const drafts = JSON.parse(localStorage.getItem('form_drafts') || '{}');
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        const formId = form.id || 'form';
        if (drafts[formId]) {
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (drafts[formId][input.name]) {
                    input.value = drafts[formId][input.name];
                }
            });
        }
    });
}

// Initialize form drafts on page load
document.addEventListener('DOMContentLoaded', loadFormDrafts);

// Export functions for global use
window.VivoApp = {
    showAlert,
    updateAttendance,
    saveFormDraft,
    loadFormDrafts
};

console.log('VIVO United Football Manager loaded successfully');
