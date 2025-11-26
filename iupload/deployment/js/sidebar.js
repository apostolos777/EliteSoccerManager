// Elite Soccer Manager - Sidebar Navigation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebar && mainContent && sidebarToggle) {
        // Toggle sidebar
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage using unified key
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            // Ensure the CSS root variable is updated so widths/margins respond
            applyRootSidebarWidth(isCollapsed);
        });
        
        // Restore sidebar state from localStorage
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            // set root var for collapsed
            applyRootSidebarWidth(true);
        } else {
            applyRootSidebarWidth(false);
        }
        
        // Handle dropdown menus
        document.querySelectorAll('.nav-dropdown .dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Close other dropdowns
                document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
                    if (dropdown !== this.parentElement) {
                        dropdown.classList.remove('open');
                    }
                }.bind(this));
                
                // Toggle current dropdown
                this.parentElement.classList.toggle('open');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
                    dropdown.classList.remove('open');
                });
            }
        });
        
        // Handle responsive behavior
        function handleResize() {
            if (window.innerWidth <= 900) {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    applyRootSidebarWidth(true);
                }
            } else {
                // restore to stored preference (or expanded)
                const saved = localStorage.getItem('sidebarCollapsed');
                if (saved === 'true') {
                    sidebar.classList.add('collapsed');
                    applyRootSidebarWidth(true);
                } else {
                    sidebar.classList.remove('collapsed');
                    applyRootSidebarWidth(false);
                }
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize(); // Initial call
    }
    
    // Add smooth animations to cards and buttons
    const cards = document.querySelectorAll('.card, .stat-card, .team-card, .player-card');
    cards.forEach(function(card) {
        card.classList.add('fade-in');
    });
    
    // Enhanced form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        // Skip validation for attendance form
        if (form.id === 'attendanceForm') {
            return;
        }
        
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'danger');
            }
        });
    });
    
    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn[type="submit"]');
    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Skip loading state for attendance form
            if (this.form && this.form.id === 'attendanceForm') {
                return;
            }
            
            if (this.form && this.form.checkValidity()) {
                this.classList.add('loading');
                this.disabled = true;
                
                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 3000);
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Enhanced table interactions
    const tables = document.querySelectorAll('.table');
    tables.forEach(function(table) {
        // Add hover effects to rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(2px)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    });
});

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.textContent = message;
    
    // Insert at the top of the main content
    const mainContent = document.getElementById('mainContent');
    const container = mainContent?.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            alertDiv.style.opacity = '0';
            setTimeout(function() {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Enhanced smooth scrolling for internal links
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
    // If another sidebar script already initialized (inline include), don't run to avoid conflicts
    if (window.__vivoSidebarInitialized) return;
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add tooltips to sidebar items when minimized
function updateTooltips() {
    const sidebar = document.getElementById('sidebar');
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    
    if (sidebar && sidebar.classList.contains('collapsed')) {
        navLinks.forEach(function(link) {
            const label = link.querySelector('.sidebar-label') || link.querySelector('span');
            if (label) {
                link.setAttribute('title', label.textContent);
            }
        });
    } else {
        navLinks.forEach(function(link) {
            link.removeAttribute('title');
        });
    }
}

// Update tooltips when sidebar state changes
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            updateTooltips();
        }
    });
});

const sidebar = document.getElementById('sidebar');
if (sidebar) {
    observer.observe(sidebar, { attributes: true });
    updateTooltips(); // Initial call
}

// Helper to set the root CSS variable so width/margin respond even if global CSS defines vars
function applyRootSidebarWidth(collapsed) {
    try {
        const root = document.documentElement;
        const cs = getComputedStyle(root);
        let expanded = cs.getPropertyValue('--sidebar-expanded-width') || cs.getPropertyValue('--sidebar-width') || '280px';
        let collapsedW = cs.getPropertyValue('--sidebar-collapsed-width') || '60px';
        expanded = expanded.trim() || '280px';
        collapsedW = collapsedW.trim() || '60px';
        root.style.setProperty('--sidebar-width', collapsed ? collapsedW : expanded);
    } catch (e) {
        document.documentElement.style.setProperty('--sidebar-width', collapsed ? '60px' : '280px');
    }
}
