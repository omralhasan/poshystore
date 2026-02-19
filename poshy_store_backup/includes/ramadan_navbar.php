<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count if user is logged in
$cart_count = 0;
$is_logged_in = isset($_SESSION['user_id']);
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
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a href="<?= $base_path ?>pages/shop/shop.php" class="nav-link-ramadan">
                    <i class="fas fa-shopping-bag me-1"></i>Shop
                </a>
                <?php if ($is_logged_in): ?>
                    <a href="<?= $base_path ?>pages/shop/my_orders.php" class="nav-link-ramadan">
                        <i class="fas fa-box me-1"></i>My Orders
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="d-flex align-items-center">
                <?php if ($is_logged_in): ?>
                    <a href="<?= $base_path ?>pages/shop/cart.php" class="nav-icon-ramadan position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= $base_path ?>pages/auth/logout.php" class="nav-icon-ramadan">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= $base_path ?>pages/auth/signin.php" class="nav-icon-ramadan">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
