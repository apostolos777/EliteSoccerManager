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
<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
    <span class="burger-line"></span>
    <span class="burger-line"></span>
    <span class="burger-line"></span>
</button>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<div class="sidebar" id="sidebar">
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="true">
            <i class="fas fa-angle-left" aria-hidden="true"></i>
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
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
                    <i class="fas fa-home"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="teams.php" class="nav-link <?php echo $currentPage === 'teams' ? 'active' : ''; ?>" title="Team Management">
                    <i class="fas fa-users"></i> 
                    <span>Teams</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="coaches.php" class="nav-link <?php echo $currentPage === 'coaches' ? 'active' : ''; ?>" title="Coach Management">
                    <i class="fas fa-users-cog"></i> 
                    <span>Coaches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="players.php" class="nav-link <?php echo $currentPage === 'players' ? 'active' : ''; ?>" title="Player Management">
                    <i class="fas fa-user-circle"></i> 
                    <span>Players</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="events.php" class="nav-link <?php echo $currentPage === 'events' ? 'active' : ''; ?>" title="Event Management">
                    <i class="fas fa-calendar-alt"></i> 
                    <span>Events</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="attendance.php" class="nav-link <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>" title="Attendance Tracking">
                    <i class="fas fa-clipboard-check"></i> 
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="club_settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" title="Club Settings">
                    <i class="fas fa-cog"></i> 
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <!-- Login/Logout Button -->
        <div class="auth-section">
            <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                <a href="logout.php" class="auth-btn logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
                <div class="user-info">
                    <small>Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></small>
                </div>
            <?php else: ?>
                <a href="login.php" class="auth-btn login-btn" title="Login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="footer-content">
            <?php if ($logoExists): ?>
                <img src="<?php echo htmlspecialchars($logoRel); ?>" alt="Club Logo" class="club-logo-footer" />
            <?php else: ?>
                <i class="fas fa-futbol"></i>
            <?php endif; ?>
            <div><?php echo htmlspecialchars($clubSettings['club_name'] ?? 'Club'); ?></div>
            <div>Management System</div>
        </div>
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



.sidebar {
    width: var(--sidebar-width);
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    height: 100vh;
    overflow-y: auto;
    transition: width 220ms cubic-bezier(.2,.8,.2,1);
}

.sidebar.collapsed {
    /* enforce a small collapsed width and make it resilient if CSS variables are missing */
    width: var(--sidebar-collapsed-width, 60px) !important;
    min-width: var(--sidebar-collapsed-width, 60px) !important;
    max-width: var(--sidebar-collapsed-width, 60px) !important;
    overflow: hidden !important;
}

.sidebar .logo-text,
.sidebar .nav-link span,
.sidebar .sidebar-footer .footer-content div {
    transition: opacity 160ms ease, transform 180ms ease;
    transform-origin: left;
}

.sidebar.collapsed .logo-text,
.sidebar.collapsed .nav-link span,
.sidebar.collapsed .sidebar-footer .footer-content div {
    opacity: 0;
    transform: translateX(-6px);
    pointer-events: none;
}

.sidebar.collapsed .sidebar-header { padding: 20px 10px; }
.sidebar.collapsed .logo-container { justify-content: center; }
.sidebar.collapsed .nav-link { justify-content: center; padding: 12px; width: 44px; margin: 4px auto; }
.sidebar.collapsed .nav-link i { margin-right: 0; }

/* Fix content area adjustment */
/* Use CSS variable for content shift so transition is smooth */
.content-main,
.main-content {
    margin-left: var(--sidebar-width);
    transition: margin-left 220ms cubic-bezier(.2,.8,.2,1);
}

/* When the body has sidebar-collapsed class, keep the global --sidebar-width variable in sync
    so the content margin-left always matches the visible sidebar width */
body.sidebar-collapsed { --sidebar-width: var(--sidebar-collapsed-width, 60px); }
body:not(.sidebar-collapsed) { --sidebar-width: var(--sidebar-expanded-width); }

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    display: none;
}

.mobile-menu-overlay.active {
    display: block;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 999;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .sidebar.collapsed {
        width: 280px !important; /* Full width on mobile when opened */
    }
    
    .content-main,
    .main-content {
        margin-left: 0 !important;
        transition: none !important;
    }
    
    body.sidebar-collapsed .content-main,
    body.sidebar-collapsed .main-content {
        margin-left: 0 !important;
    }
    
    /* Mobile menu button */
    .mobile-menu-btn {
        display: block;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1000;
        background: var(--primary);
        color: white;
        border: none;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
}

@media (min-width: 769px) {
    .mobile-menu-btn {
        display: none;
    }
}

/* Smooth transitions */
.sidebar,
.sidebar .logo-text,
.sidebar .nav-link span,
.sidebar .sidebar-footer .footer-content div {
    transition: all 0.3s ease;
}

/* Adjust content when sidebar is collapsed */
body:not(.mobile-view) .content-main,
body:not(.mobile-view) .main-content {
    margin-left: var(--sidebar-width, 280px);
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ensure content margin-left matches the collapsed sidebar width to prevent overlap */
body.sidebar-collapsed:not(.mobile-view) .content-main,
body.sidebar-collapsed:not(.mobile-view) .main-content {
    margin-left: var(--sidebar-collapsed-width, 60px) !important;
}

@media (max-width:768px){
    .club-logo-img{max-width:50px;max-height:50px}
    .sidebar-toggle {display: none;} /* Hide toggle on mobile, use hamburger menu instead */
    
    body .content-main,
    body .main-content {
        margin-left: 0 !important;
    }
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    const root = document.documentElement;
    
    console.log('Sidebar elements found:', { sidebar, toggleBtn, mobileMenuToggle, mobileMenuOverlay });
    
    // Get stored preference (only for desktop)
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    function isMobile() {
        const mobile = window.innerWidth <= 768;
        console.log('isMobile:', mobile, 'window.innerWidth:', window.innerWidth);
        return mobile;
    }
    
    // Ensure the CSS root variable is updated so inline styles override global stylesheet
    function applyRootSidebarWidth(collapsed) {
        try {
            // Read configured values from computed styles (fallback if not defined)
            const cs = getComputedStyle(root);
            let expanded = cs.getPropertyValue('--sidebar-expanded-width') || cs.getPropertyValue('--sidebar-width') || '280px';
            let collapsedW = cs.getPropertyValue('--sidebar-collapsed-width') || '60px';
            expanded = expanded.trim() || '280px';
            collapsedW = collapsedW.trim() || '60px';
            root.style.setProperty('--sidebar-width', collapsed ? collapsedW : expanded);
        } catch (e) {
            // fallback: set explicit px values
            root.style.setProperty('--sidebar-width', collapsed ? '60px' : '280px');
        }
    }
    
    // Apply initial state only on desktop
    const toggleIcon = toggleBtn ? toggleBtn.querySelector('i') : null;
    function setToggleIcon(collapsed) {
        if (!toggleIcon) return;
        toggleIcon.classList.remove('fa-angle-left','fa-angle-right');
        toggleIcon.classList.add(collapsed ? 'fa-angle-right' : 'fa-angle-left');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', (!collapsed).toString());
    }

    if (!isMobile() && isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
        root.classList.add('sidebar-collapsed');
        setToggleIcon(true);
    // apply inline root var so global CSS doesn't override our width
    applyRootSidebarWidth(true);
    } else {
        setToggleIcon(false);
    applyRootSidebarWidth(false);
    }
    
    // Desktop sidebar toggle
    toggleBtn && toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!sidebar || isMobile()) return;
        
        const isCollapsed = sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed', isCollapsed);
        root.classList.toggle('sidebar-collapsed', isCollapsed);
        
        // Store preference
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        setToggleIcon(isCollapsed);
        
        // Update the CSS variables
        applyRootSidebarWidth(isCollapsed);
        
        console.log('Sidebar toggled. Collapsed:', isCollapsed);
    });

    // Mobile menu functionality
    
    function openMobileMenu() {
        console.log('openMobileMenu called');
        if (sidebar) {
            sidebar.classList.add('mobile-open', 'mobile-menu-open');
            mobileMenuOverlay && mobileMenuOverlay.classList.add('active');
            body.classList.add('mobile-menu-active');
            body.style.overflow = 'hidden';
            if (mobileMenuToggle) {
                mobileMenuToggle.setAttribute('aria-expanded', 'true');
            }
        }
    }
    
    function closeMobileMenu() {
        console.log('closeMobileMenu called');
        if (sidebar) {
            sidebar.classList.remove('mobile-open', 'mobile-menu-open');
            mobileMenuOverlay && mobileMenuOverlay.classList.remove('active');
            body.classList.remove('mobile-menu-active');
            body.style.overflow = '';
            if (mobileMenuToggle) {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        }
    }
    
    // Mobile menu button click
    mobileMenuToggle && mobileMenuToggle.addEventListener('click', function() {
        console.log('Mobile menu toggle clicked');
        console.log('Sidebar classes:', sidebar.classList);
        if (sidebar.classList.contains('mobile-menu-open')) {
            console.log('Closing mobile menu');
            closeMobileMenu();
        } else {
            console.log('Opening mobile menu');
            openMobileMenu();
        }
    });
    
    // Overlay click to close
    mobileMenuOverlay && mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    
    // Close mobile menu when clicking nav links on mobile
    if (isMobile()) {
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            closeMobileMenu();
        }
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
    // Indicate that the inline sidebar script has initialized so other sidebar scripts can skip initialization
    window.__vivoSidebarInitialized = true;
});
</script>
