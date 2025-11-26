// Mobile Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;

    // Toggle mobile menu
    function toggleMobileMenu() {
        sidebar.classList.toggle('mobile-menu-open');
        mobileMenuOverlay.classList.toggle('active');
        body.classList.toggle('mobile-menu-active');

        // Update aria-expanded for accessibility
        const isOpen = sidebar.classList.contains('mobile-menu-open');
        mobileMenuToggle.setAttribute('aria-expanded', isOpen);
    }

    // Event listeners
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }

    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', toggleMobileMenu);
    }

    // Close menu on window resize (if screen becomes larger)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-menu-open');
            mobileMenuOverlay.classList.remove('active');
            body.classList.remove('mobile-menu-active');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Close menu when clicking on nav links (mobile)
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMobileMenu();
            }
        });
    });
});