// VIVO United - Mobile Navigation System
// Updated: August 29, 2025

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && mobileMenuOverlay && sidebar) {
        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            sidebar.classList.toggle('mobile-open');
            mobileMenuOverlay.classList.toggle('active');

            // Update button icon
            const icon = this.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('mobile-open')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            }
        });

        // Close menu when clicking overlay
        mobileMenuOverlay.addEventListener('click', function() {
            closeMobileMenu();
        });

        // Close menu on window resize (if screen becomes larger)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Close menu when clicking on a menu item
        const menuLinks = sidebar.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });
    }

    // Swipe gestures for mobile
    initSwipeGestures();

    // Keyboard navigation
    initKeyboardNavigation();

    function closeMobileMenu() {
        sidebar.classList.remove('mobile-open');
        mobileMenuOverlay.classList.remove('active');

        const icon = mobileMenuBtn.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-bars';
        }
    }

    function initSwipeGestures() {
        let startX = 0;
        let startY = 0;
        let isTracking = false;

        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isTracking = true;
        });

        document.addEventListener('touchmove', function(e) {
            if (!isTracking) return;

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const diffX = startX - currentX;
            const diffY = startY - currentY;

            // Only handle horizontal swipes
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    // Swipe left - close menu
                    closeMobileMenu();
                } else {
                    // Swipe right - open menu (only if at left edge)
                    if (startX < 50) {
                        sidebar.classList.add('mobile-open');
                        mobileMenuOverlay.classList.add('active');
                    }
                }
                isTracking = false;
            }
        });

        document.addEventListener('touchend', function() {
            isTracking = false;
        });
    }

    function initKeyboardNavigation() {
        document.addEventListener('keydown', function(e) {
            // ESC key closes mobile menu
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeMobileMenu();
            }

            // Alt + M toggles mobile menu
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                mobileMenuBtn.click();
            }
        });
    }

    // Prevent body scroll when mobile menu is open
    function toggleBodyScroll() {
        if (sidebar.classList.contains('mobile-open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Observe mobile menu state changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                toggleBodyScroll();
            }
        });
    });

    if (sidebar) {
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        // Small delay to ensure the orientation change is complete
        setTimeout(function() {
            closeMobileMenu();
        }, 100);
    });

    // Accessibility improvements
    function enhanceAccessibility() {
        // Add ARIA labels
        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-label', 'Toggle mobile menu');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }

        if (mobileMenuOverlay) {
            mobileMenuOverlay.setAttribute('aria-hidden', 'true');
        }

        // Update ARIA attributes when menu state changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mobileMenuBtn) {
                    const isOpen = sidebar.classList.contains('mobile-open');
                    mobileMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }
                if (mobileMenuOverlay) {
                    const isOpen = mobileMenuOverlay.classList.contains('active');
                    mobileMenuOverlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                }
            });
        });

        if (sidebar) {
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
        }
        if (mobileMenuOverlay) {
            observer.observe(mobileMenuOverlay, { attributes: true, attributeFilter: ['class'] });
        }
    }

    enhanceAccessibility();

    // Performance optimization - throttle resize events
    let resizeTimeout;
    function throttledResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        }, 100);
    }

    window.addEventListener('resize', throttledResize);

    console.log('Mobile navigation system initialized');
});