<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include language system if not already included
if (!function_exists('t')) {
    require_once __DIR__ . '/language.php';
}

// Get cart count if user is logged in
$cart_count = 0;
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = ($is_logged_in && ($_SESSION['role'] ?? '') === 'admin');
if ($is_logged_in) {
    // Try to get cart count if cart_handler is available
    if (file_exists(__DIR__ . '/cart_handler.php')) {
        require_once __DIR__ . '/cart_handler.php';
        if (function_exists('getCartCount')) {
            $cart_info = getCartCount($_SESSION['user_id']);
            $cart_count = $cart_info['count'] ?? 0;
        }
    }
}

// Calculate proper base path
$current_path = $_SERVER['PHP_SELF'];
$base_path = '';
if (strpos($current_path, '/pages/') !== false) {
    $base_path = '../../';
} else if (strpos($current_path, '/api/') !== false) {
    $base_path = '../';
}
?>

<!-- Floating Ramadan Decorations -->
<div class="floating-decorations">
    <i class="fas fa-moon floating-icon"></i>
    <i class="fas fa-star floating-icon"></i>
    <i class="fas fa-mosque floating-icon"></i>
    <i class="fas fa-star-and-crescent floating-icon"></i>
    <i class="fas fa-star floating-icon"></i>
    <i class="fas fa-moon floating-icon"></i>
</div>

<!-- Navbar -->
<nav class="ramadan-navbar">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a href="<?= $base_path ?>index.php" class="navbar-brand-ramadan">
                Poshy
                <span class="logo-subtitle">STORE</span>
            </a>
            
            <div class="d-none d-md-flex gap-3">
                <a href="<?= $base_path ?>index.php" class="nav-link-ramadan">
                    <i class="fas fa-home me-1"></i><?= t('home') ?>
                </a>
                <a href="<?= $base_path ?>pages/shop/shop.php" class="nav-link-ramadan">
                    <i class="fas fa-shopping-bag me-1"></i><?= t('shop') ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <a href="<?= $base_path ?>pages/shop/points_wallet.php" class="nav-link-ramadan">
                        <i class="fas fa-award me-1"></i><?= t('rewards') ?>
                    </a>
                    <a href="<?= $base_path ?>pages/shop/my_orders.php" class="nav-link-ramadan">
                        <i class="fas fa-box me-1"></i><?= $current_lang === 'ar' ? 'طلباتي' : 'My Orders' ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Language Switcher -->
                <a href="?lang=<?= getOtherLang() ?>" class="nav-icon-ramadan" title="<?= getLangName(getOtherLang()) ?>" style="font-size: 1.2rem;">
                    <?= $current_lang === 'ar' ? '🇬🇧' : '🇯🇴' ?>
                </a>
                
                <?php if ($is_admin): ?>
                    <a href="<?= $base_path ?>pages/admin/admin_panel.php" class="nav-icon-ramadan d-none d-md-inline" title="Admin Panel" style="color: #ffd700;">
                        <i class="fas fa-cog"></i>
                    </a>
                <?php endif; ?>
                <?php if ($is_logged_in): ?>
                    <a href="<?= $base_path ?>pages/shop/points_wallet.php" class="nav-icon-ramadan d-none d-md-inline" title="<?= t('points_wallet') ?>">
                        <i class="fas fa-award"></i>
                    </a>
                    <a href="<?= $base_path ?>pages/shop/cart.php" class="nav-icon-ramadan position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= $base_path ?>pages/auth/logout.php" class="nav-icon-ramadan d-none d-md-inline">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= $base_path ?>pages/auth/signin.php" class="nav-icon-ramadan">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Hamburger Menu -->
                <button class="mobile-menu-toggle d-md-none" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Slide-out Menu -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span class="mobile-menu-brand">Poshy <small>STORE</small></span>
        <button class="mobile-menu-close" onclick="toggleMobileMenu()" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="mobile-menu-links">
        <a href="<?= $base_path ?>index.php" class="mobile-menu-link">
            <i class="fas fa-home"></i>
            <span><?= t('home') ?></span>
        </a>
        <a href="<?= $base_path ?>pages/shop/shop.php" class="mobile-menu-link">
            <i class="fas fa-shopping-bag"></i>
            <span><?= t('shop') ?></span>
        </a>
        <?php if ($is_logged_in): ?>
            <a href="<?= $base_path ?>pages/shop/cart.php" class="mobile-menu-link">
                <i class="fas fa-shopping-cart"></i>
                <span><?= $current_lang === 'ar' ? 'السلة' : 'Cart' ?> <?php if ($cart_count > 0): ?><span class="mobile-cart-count"><?= $cart_count ?></span><?php endif; ?></span>
            </a>
            <a href="<?= $base_path ?>pages/shop/points_wallet.php" class="mobile-menu-link">
                <i class="fas fa-award"></i>
                <span><?= t('rewards') ?></span>
            </a>
            <a href="<?= $base_path ?>pages/shop/my_orders.php" class="mobile-menu-link">
                <i class="fas fa-box"></i>
                <span><?= $current_lang === 'ar' ? 'طلباتي' : 'My Orders' ?></span>
            </a>
            <?php if ($is_admin): ?>
                <a href="<?= $base_path ?>pages/admin/admin_panel.php" class="mobile-menu-link" style="color: #ffd700;">
                    <i class="fas fa-cog"></i>
                    <span><?= $current_lang === 'ar' ? 'لوحة الإدارة' : 'Admin Panel' ?></span>
                </a>
            <?php endif; ?>
            <div class="mobile-menu-divider"></div>
            <a href="<?= $base_path ?>pages/auth/logout.php" class="mobile-menu-link mobile-menu-link-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span><?= $current_lang === 'ar' ? 'تسجيل خروج' : 'Logout' ?></span>
            </a>
        <?php else: ?>
            <a href="<?= $base_path ?>pages/auth/signin.php" class="mobile-menu-link">
                <i class="fas fa-user"></i>
                <span><?= $current_lang === 'ar' ? 'تسجيل دخول' : 'Sign In' ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const btn = document.querySelector('.mobile-menu-toggle');
    const isOpen = menu.classList.contains('open');
    
    menu.classList.toggle('open');
    overlay.classList.toggle('open');
    if (btn) btn.classList.toggle('active');
    document.body.style.overflow = isOpen ? '' : 'hidden';
}
</script>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Mobile navigation">
    <a href="<?= $base_path ?>index.php" class="bottom-nav-item <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_GET['search'])) ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span><?= $current_lang === 'ar' ? 'الرئيسية' : 'Home' ?></span>
    </a>
    
    <a href="<?= $base_path ?>index.php?show_all=1" class="bottom-nav-item <?= isset($_GET['search']) || isset($_GET['brand']) || isset($_GET['subcategory']) ? 'active' : '' ?>" id="bottomNavSearch">
        <i class="fas fa-search"></i>
        <span><?= $current_lang === 'ar' ? 'بحث' : 'Search' ?></span>
    </a>
    
    <?php if ($is_logged_in): ?>
    <a href="<?= $base_path ?>pages/shop/cart.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-cart"></i>
        <?php if ($cart_count > 0): ?>
            <span class="bottom-nav-badge"><?= $cart_count > 9 ? '9+' : $cart_count ?></span>
        <?php endif; ?>
        <span><?= $current_lang === 'ar' ? 'السلة' : 'Cart' ?></span>
    </a>
    
    <a href="<?= $base_path ?>pages/shop/my_orders.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'my_orders.php' ? 'active' : '' ?>">
        <i class="fas fa-box"></i>
        <span><?= $current_lang === 'ar' ? 'طلباتي' : 'Orders' ?></span>
    </a>
    
    <a href="<?= $base_path ?>pages/shop/points_wallet.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'points_wallet.php' ? 'active' : '' ?>">
        <i class="fas fa-award"></i>
        <span><?= $current_lang === 'ar' ? 'مكافآت' : 'Rewards' ?></span>
    </a>
    <?php else: ?>
    <a href="<?= $base_path ?>pages/shop/shop.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-bag"></i>
        <span><?= $current_lang === 'ar' ? 'المتجر' : 'Shop' ?></span>
    </a>
    
    <a href="<?= $base_path ?>pages/auth/signin.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'signin.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i>
        <span><?= $current_lang === 'ar' ? 'دخول' : 'Sign In' ?></span>
    </a>
    <?php endif; ?>
</nav>

<script>
// Bottom nav: highlight search icon and open search on mobile
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('bottomNavSearch');
    if (!searchBtn) return;
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) {
        // Not on homepage — use href as-is
        return;
    }
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        searchInput.focus();
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});
</script>
