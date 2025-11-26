/* VIVO United Football Manager - Layout Toggle Feature */
/* This script allows users to toggle between normal and compact layouts */

document.addEventListener('DOMContentLoaded', function() {
    // Create layout toggle button
    const toggleButton = document.createElement('div');
    toggleButton.id = 'layout-toggle';
    toggleButton.className = 'layout-toggle-button';
    toggleButton.innerHTML = `
        <i class="fas fa-compress-alt"></i>
        <span class="toggle-text">Compact</span>
    `;
    
    // Add styles for the toggle button
    const toggleStyles = document.createElement('style');
    toggleStyles.textContent = `
        .layout-toggle-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            cursor: pointer;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            transition: all 0.3s ease;
            user-select: none;
        }
        
        .layout-toggle-button:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }
        
        .layout-toggle-button.compact-active {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .layout-toggle-button.compact-active:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        
        .layout-toggle-button i {
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .layout-toggle-button {
                top: 10px;
                right: 10px;
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .layout-toggle-button i {
                font-size: 14px;
            }
        }
    `;
    
    document.head.appendChild(toggleStyles);
    document.body.appendChild(toggleButton);
    
    // Check if compact layout is currently active
    const isCompact = document.body.classList.contains('compact-layout');
    updateToggleButton(isCompact);
    
    // Handle toggle click
    toggleButton.addEventListener('click', function() {
        const body = document.body;
        const isCurrentlyCompact = body.classList.contains('compact-layout');
        
        if (isCurrentlyCompact) {
            // Switch to normal layout
            body.classList.remove('compact-layout');
            localStorage.setItem('vivo-layout-preference', 'normal');
            updateToggleButton(false);
            showLayoutNotification('Switched to Normal Layout');
        } else {
            // Switch to compact layout
            body.classList.add('compact-layout');
            localStorage.setItem('vivo-layout-preference', 'compact');
            updateToggleButton(true);
            showLayoutNotification('Switched to Compact Layout');
        }
    });
    
    // Load saved layout preference
    const savedPreference = localStorage.getItem('vivo-layout-preference');
    if (savedPreference === 'normal' && isCompact) {
        document.body.classList.remove('compact-layout');
        updateToggleButton(false);
    } else if (savedPreference === 'compact' && !isCompact) {
        document.body.classList.add('compact-layout');
        updateToggleButton(true);
    }
    
    function updateToggleButton(isCompact) {
        const button = document.getElementById('layout-toggle');
        const icon = button.querySelector('i');
        const text = button.querySelector('.toggle-text');
        
        if (isCompact) {
            button.classList.add('compact-active');
            icon.className = 'fas fa-expand-alt';
            text.textContent = 'Normal';
        } else {
            button.classList.remove('compact-active');
            icon.className = 'fas fa-compress-alt';
            text.textContent = 'Compact';
        }
    }
    
    function showLayoutNotification(message) {
        // Remove existing notification
        const existingNotification = document.querySelector('.layout-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'layout-notification';
        notification.textContent = message;
        
        // Add notification styles
        const notificationStyles = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: #1f2937;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInNotification 0.3s ease, fadeOutNotification 0.3s ease 2.7s forwards;
        `;
        
        // Add animation styles if not already present
        if (!document.querySelector('#notification-styles')) {
            const animationStyles = document.createElement('style');
            animationStyles.id = 'notification-styles';
            animationStyles.textContent = `
                @keyframes slideInNotification {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes fadeOutNotification {
                    from {
                        opacity: 1;
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                }
                
                .layout-notification {
                    ${notificationStyles}
                }
            `;
            document.head.appendChild(animationStyles);
        }
        
        notification.style.cssText = notificationStyles;
        document.body.appendChild(notification);
        
        // Remove notification after animation
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
});

// Initialize layout preference on page load
(function() {
    const savedPreference = localStorage.getItem('vivo-layout-preference');
    if (savedPreference === 'compact' && !document.body.classList.contains('compact-layout')) {
        document.body.classList.add('compact-layout');
    } else if (savedPreference === 'normal' && document.body.classList.contains('compact-layout')) {
        document.body.classList.remove('compact-layout');
    }
})();
