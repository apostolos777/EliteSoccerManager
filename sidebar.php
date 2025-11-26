<?php
// Enhanced sidebar navigation with mobile support + dynamic club logo
$currentPage = $currentPage ?? '';

// Load club settings to display logo/name if available
$clubSettings = ['club_logo' => '', 'club_name' => 'VIVO UNITED'];

// Modern database approach - same as club_settings.php
if (!function_exists('get_db_connection')) {
    $wpCfg = __DIR__ . '/../wp-config.php';
    if (file_exists($wpCfg)) {
        require_once $wpCfg;
    }
}

// Load settings from club_settings table (modern approach)
try {
    if (function_exists('get_db_connection')) {
        $db = get_db_connection();
        // Load all club settings from database
        $stmt = $db->query("SELECT setting_key, setting_value FROM club_settings");
        $fetched = [];
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                if (!empty($row['setting_key'])) {
                    $fetched[$row['setting_key']] = $row['setting_value'] ?? '';
                }
            }
        }
        if (!empty($fetched)) {
            $clubSettings = array_merge($clubSettings, $fetched);
        }
    }
} catch (Throwable $e) {
    // Silently ignore; table may not exist on first run
}
$logoRel = trim($clubSettings['club_logo'] ?? '');

// Fallback auto-fix: if stored value is a bare filename that exists under uploads/, prepend uploads/
if ($logoRel && !preg_match('#^https?://#i', $logoRel) && !preg_match('#^uploads/#', $logoRel)) {
    $uploadsCandidate = __DIR__ . '/../uploads/' . $logoRel;
    if (is_file($uploadsCandidate)) {
        $logoRel = 'uploads/' . $logoRel; // update for display (not writing back; transient fix)
    }
}

$logoFs = $logoRel ? realpath(__DIR__.'/../'.$logoRel) : '';
$logoIsUrl = is_string($logoRel) && preg_match('#^https?://#i', $logoRel);
$logoExists = $logoIsUrl || ($logoFs && is_file($logoFs));

// Last-resort auto-detect: pick latest file that matches club_logo_* in uploads if current doesn't exist
if (!$logoExists) {
    $uploadDir = __DIR__ . '/../uploads';
    if (is_dir($uploadDir)) {
        $candidates = glob($uploadDir . '/club_logo_*.*');
        if ($candidates) {
            // sort newest first
            usort($candidates, function($a,$b){ return filemtime($b) <=> filemtime($a); });
            $auto = $candidates[0];
            if (is_file($auto)) {
                $rel = 'uploads/' . basename($auto);
                $logoFs = realpath($auto);
                $logoRel = $rel;
                $logoExists = true;
            }
        }
    }
}
?>
<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open mobile menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<div class="sidebar" id="sidebar">
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-image" aria-label="Club Logo">
                <?php if ($logoExists): ?>
                    <img src="<?php echo htmlspecialchars($logoRel); ?>" alt="Club Logo" class="club-logo-img" />
                <?php else: ?>
                    <div class="logo-fallback" title="No logo set"><i class="fas fa-futbol"></i></div>
                    <!-- Logo Debug (auto comment)
                        Stored: <?php echo htmlspecialchars($clubSettings['club_logo'] ?? ''); ?>
                        Effective: <?php echo htmlspecialchars($logoRel); ?>
                        FS Path: <?php echo htmlspecialchars($logoFs ?: 'N/A'); ?>
                        Exists: NO
                        Hint: Ensure value saved is like uploads/filename.png and file is inside /uploads.
                    -->
                <?php endif; ?>
            </div>
            <div class="logo-text">
                <div class="logo-title"><?php echo htmlspecialchars($clubSettings['club_name'] ?? 'VIVO UNITED'); ?></div>
                <div class="logo-subtitle">Elite Football Manager</div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="players.php" class="nav-link <?php echo $currentPage === 'players' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Players</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="coaches.php" class="nav-link <?php echo $currentPage === 'coaches' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Coaches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="events.php" class="nav-link <?php echo $currentPage === 'events' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Events</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="attendance.php" class="nav-link <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
.club-logo-img,.club-logo-footer{display:block;max-width:60px;max-height:60px;object-fit:contain;background:white;padding:4px;border-radius:6px;}
.club-logo-footer{max-width:40px;max-height:40px;margin:0 auto 4px;padding:2px;}
.logo-image{display:flex;align-items:center;justify-content:center;width:70px;height:70px;background:rgba(255,255,255,0.05);border-radius:12px;overflow:hidden;margin:0 auto;}
.logo-fallback{font-size:2rem;color:#fff;opacity:.9;display:flex;align-items:center;justify-content:center;width:100%;height:100%}
.logo-title{font-size:.9rem;font-weight:700;letter-spacing:.5px;text-align:center;}
.logo-subtitle{font-size:.55rem;text-transform:uppercase;letter-spacing:.8px;opacity:.8;text-align:center;}
.sidebar-header{text-align:center;padding:20px;}
.logo-container{display:flex;flex-direction:column;align-items:center;gap:10px;}
.logo-text{text-align:center;}
.sidebar-footer .footer-content{text-align:center;font-size:.6rem;line-height:1.1;font-weight:500;color:#ddd}

/* Collapsible Sidebar */
.sidebar-toggle {
    position: absolute;
    top: 20px;
    right: -15px;
    width: 30px;
    height: 30px;
    background: var(--primary, #0e1012);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.sidebar-toggle:hover {
    background: var(--primary-dark, #000000);
    transform: scale(1.1);
}

.sidebar.collapsed {
    width: 0 !important;
    min-width: 0 !important;
    max-width: 0 !important;
    overflow: hidden !important;
}

.sidebar.collapsed > *:not(.sidebar-toggle) {
    display: none !important;
}

.sidebar.collapsed .sidebar-header {
    padding: 20px 10px;
}

.sidebar.collapsed .logo-container {
    justify-content: center;
}

.sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 12px;
    width: 44px;
    margin: 4px auto;
}

.sidebar.collapsed .nav-link i {
    margin-right: 0;
}

.sidebar.collapsed .sidebar-toggle i:before {
    content: "\f0c9"; /* fa-bars */
}

.sidebar:not(.collapsed) .sidebar-toggle i:before {
    content: "\f104"; /* fa-angle-left */
}

/* Fix content area adjustment */
body.sidebar-collapsed .content-main,
body.sidebar-collapsed .main-content {
    margin-left: 0 !important;
    transition: margin-left 0.3s ease !important;
}

body:not(.sidebar-collapsed) .content-main,
body:not(.sidebar-collapsed) .main-content {
    margin-left: 280px !important;
    transition: margin-left 0.3s ease !important;
}

/* Mobile Menu Toggle Button */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: var(--primary-red);
    border: none;
    border-radius: 6px;
    padding: 0.75rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.burger-line {
    display: block;
    width: 20px;
    height: 2px;
    background: white;
    margin: 3px 0;
    border-radius: 1px;
    transition: all 0.3s ease;
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mobile-menu-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1000;
    }
    
    .sidebar.mobile-menu-open {
        transform: translateX(0);
    }
    
    body.mobile-menu-active {
        overflow: hidden;
    }
    
    .content-main {
        margin-left: 0;
        padding-left: var(--space-4);
        padding-right: var(--space-4);
    }
}

/* Burger Animation */
.mobile-menu-toggle[aria-expanded="true"] .burger-line:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle[aria-expanded="true"] .burger-line:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle[aria-expanded="true"] .burger-line:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Smooth transitions */
.sidebar,
.sidebar .logo-text,
.sidebar .nav-link span,
.sidebar .sidebar-footer .footer-content div {
    transition: all 0.3s ease;
}

/* Adjust content when sidebar is collapsed using CSS variables */
body.sidebar-collapsed .content-main {
    margin-left: var(--sidebar-collapsed-width, 60px);
}
body.sidebar-collapsed .main-content {
    margin-left: var(--sidebar-collapsed-width, 60px);
}

/* Smooth content shift regardless of container class */
.content-main,
.main-content {
    transition: margin-left 0.3s ease;
}

@media (max-width:768px){
    .club-logo-img{max-width:50px;max-height:50px}
    .sidebar-toggle {display: none;} /* Hide toggle on mobile, use hamburger menu instead */
}
</style>

<?php
$debugTrigger = !empty($_GET['logo_debug']) || is_file(__DIR__.'/../LOGO_DEBUG_ON');
?>
<div id="logoDebugPanel" style="display:<?php echo $debugTrigger ? 'block':'none'; ?>;background:#222;color:#eee;padding:10px;font-size:11px;line-height:1.3;border-top:1px solid #444;">
 <strong>Logo Debug</strong> <button id="logoDebugClose" style="float:right;background:#444;color:#fff;border:none;padding:2px 6px;cursor:pointer;font-size:11px;">×</button><br>
 Stored value: <code><?php echo htmlspecialchars($clubSettings['club_logo'] ?? ''); ?></code><br>
 Effective path used: <code><?php echo htmlspecialchars($logoRel); ?></code><br>
 Resolved FS path: <code><?php echo htmlspecialchars($logoFs ?: 'N/A'); ?></code><br>
 Exists? <?php echo $logoExists ? 'YES' : 'NO'; ?> | URL? <?php echo $logoIsUrl ? 'YES' : 'NO'; ?><br>
 uploads/ listing (first 5):<br>
 <?php
 $upDir = __DIR__.'/../uploads';
 if (is_dir($upDir)) {
     $files = array_slice(array_filter(scandir($upDir), function($f){ return $f!=='.' && $f!=='..'; }),0,5);
     foreach ($files as $f) { echo htmlspecialchars($f).'<br>'; }
 } else {
     echo 'uploads/ directory missing';
 }
 ?>
 <em>Toggle: press Alt+L or add ?logo_debug=1. Create empty file LOGO_DEBUG_ON to persist.</em>
</div>
<button id="logoDebugToggle" style="position:fixed;bottom:6px;left:6px;z-index:9999;background:var(--primary);color:#fff;border:none;padding:4px 8px;font-size:11px;border-radius:4px;cursor:pointer;opacity:0.6;">LogoDbg</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    const root = document.documentElement;
    
    // Get stored preference (only for desktop)
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Apply initial state only on desktop
    if (!isMobile() && isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
        root.classList.add('sidebar-collapsed');
    }
    
    // Desktop sidebar toggle
    toggleBtn && toggleBtn.addEventListener('click', function() {
        if (!sidebar || isMobile()) return;
        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        root.classList.toggle('sidebar-collapsed');
        
        // Store preference
        const collapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', collapsed);
    });

    // Mobile menu functionality
    function openMobileMenu() {
        if (sidebar) {
            sidebar.classList.add('mobile-open');
            mobileMenuOverlay.classList.add('active');
            body.style.overflow = 'hidden';
        }
    }
    
    function closeMobileMenu() {
        if (sidebar) {
            sidebar.classList.remove('mobile-open');
            mobileMenuOverlay.classList.remove('active');
            body.style.overflow = '';
        }
    }
    
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
    
    // Logo debug toggle
    const dbgToggle = document.getElementById('logoDebugToggle');
    const dbgPanel = document.getElementById('logoDebugPanel');
    const dbgClose = document.getElementById('logoDebugClose');
    function showDbg(){ if(dbgPanel) dbgPanel.style.display='block'; }
    function hideDbg(){ if(dbgPanel) dbgPanel.style.display='none'; }
    dbgToggle && dbgToggle.addEventListener('click', function(){ if(!dbgPanel) return; dbgPanel.style.display = (dbgPanel.style.display==='none'?'block':'none'); });
    dbgClose && dbgClose.addEventListener('click', hideDbg);
    document.addEventListener('keydown', function(e){ if(e.altKey && (e.key==='l' || e.key==='L')) { showDbg(); } });
});
</script>
