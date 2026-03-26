<?php
/**
 * Category Store Page - Shows all products in a specific category
 * URL: pages/shop/category.php?id=1 (category ID)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_manager.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/../../includes/guest_cart_handler.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/product_image_helper.php';

$lang = $current_lang;
$is_logged_in = isset($_SESSION['user_id']);
$cart_count = 0;

if ($is_logged_in) {
    try {
        $cart_info = getCartCount($_SESSION['user_id']);
        $cart_count = is_array($cart_info) ? ($cart_info['count'] ?? 0) : (int)$cart_info;
    } catch (Exception $e) {}
} else {
    try {
        $guest_cart_info = guestGetCartCount();
        $cart_count = is_array($guest_cart_info) ? ($guest_cart_info['count'] ?? 0) : (int)$guest_cart_info;
    } catch (Exception $e) {}
}

// Helper function to convert string to slug
function stringToSlug($str) {
    return strtolower(trim(preg_replace('/[^a-z0-9-]/i', '-', preg_replace('/\s+/', '-', trim($str)))));
}

// Helper function to find category by slug
function findCategoryBySlug($categories, $slug) {
    foreach ($categories as $cat) {
        $cat_slug = stringToSlug($cat['name_en']);
        if ($cat_slug === $slug) {
            return $cat;
        }
    }
    return null;
}

// Get category ID or slug
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$active_subcategory = isset($_GET['subcategory']) ? (int)$_GET['subcategory'] : 0;

if ($category_id <= 0 && empty($category_slug)) {
    header('Location: ../../index.php');
    exit;
}

// Load all categories
$all_categories = [];
try {
    $all_categories = getAllCategories();
} catch (Exception $e) {
    error_log("Failed to load categories: " . $e->getMessage());
}

// Find the current category by ID or slug
$current_category = null;

if (!empty($category_slug)) {
    // Find by slug
    $current_category = findCategoryBySlug($all_categories, $category_slug);
    if ($current_category) {
        $category_id = $current_category['id'];
    }
} else if ($category_id > 0) {
    // Find by ID
    foreach ($all_categories as $cat) {
        if ((int)$cat['id'] === $category_id) {
            $current_category = $cat;
            break;
        }
    }
}

if (!$current_category) {
    header('Location: ../../index.php');
    exit;
}

$category_name = $lang === 'ar' && !empty($current_category['name_ar'])
    ? $current_category['name_ar']
    : $current_category['name_en'];

// Get products
$products_array = [];
try {
    if ($active_subcategory > 0) {
        $products_result = getAllProducts(['subcategory_id' => $active_subcategory], 200);
    } else {
        $products_result = getAllProducts(['category_id' => $category_id], 200);
    }
    if (!empty($products_result['products'])) {
        $products_array = $products_result['products'];
    }
} catch (Exception $e) {
    error_log("Failed to load products: " . $e->getMessage());
}

// Active subcategory name
$active_sub_name = '';
if ($active_subcategory > 0 && !empty($current_category['subcategories'])) {
    foreach ($current_category['subcategories'] as $sub) {
        if ((int)$sub['id'] === $active_subcategory) {
            $active_sub_name = $lang === 'ar' ? ($sub['name_ar'] ?: $sub['name_en']) : $sub['name_en'];
            break;
        }
    }
}

// Determine category icon
$cat_name_lower = strtolower(trim($current_category['name_en'] ?? ''));
$cat_icon = 'fas fa-star';
if (str_contains($cat_name_lower, 'skin')) $cat_icon = 'fas fa-spa';
elseif (str_contains($cat_name_lower, 'hair')) $cat_icon = 'fas fa-wind';
elseif (str_contains($cat_name_lower, 'makeup') || str_contains($cat_name_lower, 'cosmetic')) $cat_icon = 'fas fa-palette';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($category_name) ?> - Poshy Store">
    <title><?= htmlspecialchars($category_name) ?> - Poshy Store</title>
    
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    
    <style>
        :root {
            --primary: #000000;
            --primary-light: #222222;
            --accent: #C5A059;
            --accent-light: #D8BE85;
            --accent-dark: #a88a4e;
            --surface: #ffffff;
            --surface-alt: #F9F9F9;
            --surface-hover: #f5f5f5;
            --text-primary: #111111;
            --text-secondary: #666666;
            --text-muted: #999999;
            --border: rgba(0,0,0,0.08);
            --border-light: rgba(0,0,0,0.04);
            --shadow-sm: 0 1px 4px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.06);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --rose: #E8C4B8;
        }

        [dir="rtl"] { text-align: right; }

        /* Category Hero */
        .category-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 3rem 1.5rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .category-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(197,160,89,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .category-hero-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #fff;
            box-shadow: 0 8px 25px rgba(197,160,89,0.3);
        }

        .category-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .category-hero p {
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        .category-hero .product-count {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            color: var(--accent-light);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 1rem;
            backdrop-filter: blur(10px);
        }

        /* Breadcrumb */
        .breadcrumb-bar {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .breadcrumb a:hover { color: var(--accent-dark); }
        .breadcrumb .active { color: var(--text-primary); font-weight: 600; }

        /* Subcategory Chips */
        .subcategory-bar {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem 1rem;
        }

        .subcategory-chips {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0.5rem;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            align-items: center;
            scrollbar-width: thin;
        }
        .subcategory-chips::-webkit-scrollbar {
            height: 4px;
        }
        .subcategory-chips::-webkit-scrollbar-track {
            background: transparent;
        }
        .subcategory-chips::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 2px;
        }
        .subcategory-chips::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .sub-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
            text-decoration: none;
            flex-shrink: 0;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .sub-chip .chip-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f8f4f0, #fff);
            border: 2px solid var(--border);
            color: var(--accent);
            font-size: 2.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .sub-chip:hover .chip-icon {
            background: linear-gradient(135deg, var(--accent), #d4a574);
            border-color: var(--accent);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 16px rgba(212, 165, 116, 0.3);
        }
        
        .sub-chip.active .chip-icon {
            background: linear-gradient(135deg, #000, #333);
            border-color: #000;
            color: #fff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
        }
        
        .sub-chip .chip-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-align: center;
            max-width: 110px;
            word-wrap: break-word;
            transition: color 0.3s ease;
            line-height: 1.2;
        }

        .sub-chip:hover .chip-label,
        .sub-chip.active .chip-label {
            color: var(--accent);
        }
        
        .sub-chip.active .chip-label {
            color: #000;
        }
        
        .sub-chip .chip-count {
            font-size: 0.65rem;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }
        
        .sub-chip:hover .chip-count,
        .sub-chip.active .chip-count {
            opacity: 1;
        }

        /* Products */
        .products-section {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .results-count {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        /* Product Card */
        .p-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        .p-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
        }
        .p-card-img {
            position: relative;
            aspect-ratio: 1;
            background: var(--surface-alt);
            overflow: hidden;
        }
        .p-card-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--surface-alt);
            padding: 4px;
            transition: transform 0.5s ease;
        }
        .p-card:hover .p-card-img img { transform: scale(1.08); }
        .p-card-img .discount-tag {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: var(--rose);
            color: #111;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            z-index: 2;
        }
        [dir="rtl"] .p-card-img .discount-tag { right: auto; left: 0.75rem; }
        .p-card-img .cat-tag {
            position: absolute;
            bottom: 0.75rem;
            left: 0.75rem;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 600;
            z-index: 2;
            backdrop-filter: blur(4px);
        }
        [dir="rtl"] .p-card-img .cat-tag { left: auto; right: 0.75rem; }
        .p-card-body {
            padding: 1rem 1.15rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .p-card-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 0.2rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.6em;
        }
        .p-card-name-ar {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .p-card-price {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            margin-top: auto;
        }
        .price-now { font-size: 1.2rem; font-weight: 700; color: var(--accent-dark); }
        .price-was { font-size: 0.85rem; color: var(--text-muted); text-decoration: line-through; }
        .p-card-actions { display: flex; gap: 0.5rem; }
        .btn-cart {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.65rem 0.75rem;
            background: #000;
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            text-decoration: none;
        }
        .btn-cart:hover { background: #222; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .btn-view {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            background: var(--surface);
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .btn-view:hover { border-color: #000; color: #000; }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        .empty-state i { font-size: 3.5rem; color: var(--accent-light); margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .empty-state a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1rem;
            background: #000;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .out-of-stock-card .p-card-img img { opacity: 0.5; filter: grayscale(40%); }
        .out-of-stock-card .p-card-body { opacity: 0.75; }

        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .toast-alert {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            box-shadow: var(--shadow-lg);
            animation: slideInRight 0.3s ease-out;
        }
        .toast-alert.success { background: #059669; }
        .toast-alert.error { background: #ef4444; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        @media (max-width: 1024px) {
            .product-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
            .p-card-body { padding: 0.75rem; }
            .p-card-name { font-size: 0.85rem; }
            .price-now { font-size: 1.05rem; }
            .btn-cart { font-size: 0.75rem; padding: 0.55rem 0.5rem; }
            .category-hero { padding: 2rem 1rem; }
            .category-hero h1 { font-size: 1.6rem; }
            .subcategory-chips { gap: 0.75rem; }
            .sub-chip .chip-icon { width: 85px; height: 85px; font-size: 2rem; }
            .sub-chip .chip-label { font-size: 0.7rem; max-width: 95px; }
        }
        @media (max-width: 480px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
            .subcategory-chips { gap: 0.5rem; }
            .sub-chip .chip-icon { width: 70px; height: 70px; font-size: 1.6rem; }
            .sub-chip .chip-label { font-size: 0.65rem; max-width: 80px; }
            .p-card-body { padding: 0.6rem; }
            .p-card-name { font-size: 0.8rem; }
            .price-now { font-size: 0.95rem; }
            .subcategory-chips { gap: 0.5rem; padding-bottom: 0.25rem; }
            .sub-chip .chip-icon { width: 55px; height: 55px; font-size: 1.2rem; }
            .sub-chip .chip-label { font-size: 0.65rem; max-width: 60px; }
            .sub-chip .chip-count { font-size: 0.6rem; }
            .p-card-actions { flex-direction: column; }
            .btn-view { width: 100%; height: 36px; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>

    <!-- Category Hero -->
    <section class="category-hero">
        <div class="category-hero-icon">
            <i class="<?= $cat_icon ?>"></i>
        </div>
        <h1><?= htmlspecialchars($category_name) ?></h1>
        <p><?= $lang === 'ar' 
            ? 'تصفحي جميع منتجات ' . htmlspecialchars($category_name)
            : 'Browse all ' . htmlspecialchars($current_category['name_en']) . ' products'
        ?></p>
        <span class="product-count">
            <i class="fas fa-box-open"></i>
            <?= count($products_array) ?> <?= $lang === 'ar' ? 'منتج' : 'products' ?>
        </span>
    </section>

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
        <ul class="breadcrumb">
            <li><a href="/index.php"><i class="fas fa-home"></i> <?= $lang === 'ar' ? 'الرئيسية' : 'Home' ?></a></li>
            <li>/</li>
            <?php if ($active_subcategory > 0): ?>
                <li><a href="category.php?id=<?= $category_id ?>"><?= htmlspecialchars($category_name) ?></a></li>
                <li>/</li>
                <li class="active"><?= htmlspecialchars($active_sub_name) ?></li>
            <?php else: ?>
                <li class="active"><?= htmlspecialchars($category_name) ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Subcategory filter chips -->
    <?php if (!empty($current_category['subcategories'])): ?>
    <div class="subcategory-bar">
        <div class="subcategory-chips">
            <a href="category.php?id=<?= $category_id ?>" class="sub-chip <?= $active_subcategory === 0 ? 'active' : '' ?>" title="<?= $lang === 'ar' ? 'الكل' : 'All' ?>">
                <div class="chip-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="chip-label"><?= $lang === 'ar' ? 'الكل' : 'All' ?></div>
                <span class="chip-count">(0)</span>
            </a>
            <?php foreach ($current_category['subcategories'] as $sub): ?>
                <a href="category.php?id=<?= $category_id ?>&subcategory=<?= $sub['id'] ?>"
                   class="sub-chip <?= $active_subcategory === (int)$sub['id'] ? 'active' : '' ?>"
                   title="<?= htmlspecialchars($lang === 'ar' && !empty($sub['name_ar']) ? $sub['name_ar'] : $sub['name_en']) ?>">
                    <div class="chip-icon">
                        <?php if (!empty($sub['image_url'])): ?>
                            <img src="<?= htmlspecialchars($sub['image_url']) ?>" alt="<?= htmlspecialchars($sub['name_en']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php elseif (!empty($sub['icon'])): ?>
                            <i class="<?= htmlspecialchars($sub['icon']) ?>"></i>
                        <?php else: ?>
                            <i class="fas fa-tag"></i>
                        <?php endif; ?>
                    </div>
                    <div class="chip-label"><?= htmlspecialchars($lang === 'ar' && !empty($sub['name_ar']) ? $sub['name_ar'] : $sub['name_en']) ?></div>
                    <span class="chip-count">(<?= $sub['product_count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-sparkles" style="color: var(--accent);"></i>
                <?php if ($active_subcategory > 0): ?>
                    <?= htmlspecialchars($active_sub_name) ?>
                <?php else: ?>
                    <?= htmlspecialchars($category_name) ?>
                <?php endif; ?>
            </h2>
            <span class="results-count"><?= count($products_array) ?> <?= $lang === 'ar' ? 'منتج' : 'products' ?></span>
        </div>

        <?php if (empty($products_array)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-box-open"></i>
                <h3><?= $lang === 'ar' ? 'لا توجد منتجات' : 'No products found' ?></h3>
                <p><?= $lang === 'ar' ? 'لا توجد منتجات في هذا القسم بعد' : 'No products are available in this category yet' ?></p>
                <a href="/index.php">
                    <i class="fas fa-arrow-left"></i> <?= $lang === 'ar' ? 'العودة للرئيسية' : 'Back to Home' ?>
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products_array as $idx => $product): ?>
                <div class="p-card fade-in<?= ($product['stock_quantity'] <= 0) ? ' out-of-stock-card' : '' ?>" style="animation-delay: <?= $idx * 0.03 ?>s;">
                    <div class="p-card-img">
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            <span style="position:absolute;top:10px;left:10px;z-index:5;background:rgba(239,68,68,0.92);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;"><?= $lang === 'ar' ? 'نفذت الكمية' : 'Out of Stock' ?></span>
                        <?php endif; ?>

                        <?php if (!empty($product['is_best_seller'])): ?>
                            <span style="position:absolute;top:<?= ($product['stock_quantity'] <= 0) ? '38px' : '10px' ?>;left:10px;z-index:5;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="fas fa-fire"></i> <?= $lang === 'ar' ? 'الأكثر مبيعاً' : 'Best Seller' ?></span>
                        <?php endif; ?>

                        <?php if (!empty($product['is_recommended'])): ?>
                            <span style="position:absolute;bottom:10px;left:10px;z-index:5;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:4px 10px;border-radius:6px;font-size:0.72rem;font-weight:700;"><i class="fas fa-star"></i> <?= $lang === 'ar' ? 'موصى به' : 'Recommended' ?></span>
                        <?php endif; ?>

                        <?php if (!empty($product['has_discount']) && $product['discount_percentage'] > 0): ?>
                            <span class="discount-tag">-<?= intval($product['discount_percentage']) ?>%</span>
                        <?php endif; ?>

                        <?php if (!empty($product['subcategory_en'])): ?>
                            <span class="cat-tag">
                                <?= $lang === 'ar' ? htmlspecialchars($product['subcategory_ar'] ?? '') : htmlspecialchars($product['subcategory_en']) ?>
                            </span>
                        <?php endif; ?>

                        <?php
                            $image_src = get_product_thumbnail(
                                trim($product['name_en']),
                                $product['image_link'] ?? '',
                                __DIR__ . '/../../'
                            );
                            // Prefix with ../../ so the relative path resolves from
                            // /pages/shop/ back to the site root where /images/ lives
                            $image_src_url = '../../' . $image_src;
                        ?>
                        <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>">
                            <img src="<?= htmlspecialchars($image_src_url) ?>" 
                                 alt="<?= htmlspecialchars($product['name_en']) ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='../../images/placeholder-cosmetics.svg';">
                        </a>
                    </div>

                    <div class="p-card-body">
                        <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>" style="text-decoration:none; color:inherit;">
                            <div class="p-card-name"><?= htmlspecialchars($lang === 'ar' ? ($product['name_ar'] ?: $product['name_en']) : $product['name_en']) ?></div>
                            <div class="p-card-name-ar"><?= htmlspecialchars($lang === 'ar' ? $product['name_en'] : ($product['name_ar'] ?? '')) ?></div>
                        </a>
                        
                        <div class="p-card-price">
                            <?php 
                                $display_price = $product['price_jod'];
                                if (isSupplier() && !empty($product['supplier_cost']) && $product['supplier_cost'] > 0) {
                                    $display_price = $product['supplier_cost'];
                                }
                            ?>
                            <span class="price-now"><?= number_format($display_price, 3) ?> <?= t('currency') ?></span>
                            <?php if (!empty($product['has_discount']) && $product['original_price'] > 0): ?>
                                <span class="price-was"><?= number_format($product['original_price'], 3) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="p-card-actions">
                            <?php if ($product['stock_quantity'] <= 0): ?>
                                <button class="btn-cart" disabled style="opacity:0.6;cursor:not-allowed;background:#999;">
                                    <i class="fas fa-ban"></i>
                                    <span><?= $lang === 'ar' ? 'نفذت الكمية' : 'Out of Stock' ?></span>
                                </button>
                            <?php else: ?>
                                <button class="btn-cart" onclick="addToCart(<?= (int)$product['id'] ?>, this)">
                                    <i class="fas fa-cart-plus"></i>
                                    <span><?= t('add_to_cart') ?></span>
                                </button>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars(getProductUrl($product['slug'] ?? '')) ?>" class="btn-view" title="<?= t('details') ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>

    <script>
    const IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;
    
    function addToCart(productId, btn) {
        if (!btn) return;
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const apiUrl = IS_LOGGED_IN ? '../../api/add_to_cart_api.php' : '../../api/guest_cart_api.php';
        const bodyParams = IS_LOGGED_IN 
            ? 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
            : 'action=add&product_id=' + encodeURIComponent(productId) + '&quantity=1';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParams
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let badge = document.querySelector('.cart-badge');
                if (badge) {
                    badge.textContent = data.cart_count || (parseInt(badge.textContent || '0') + 1);
                }
                btn.innerHTML = '<i class="fas fa-check"></i> <span><?= $lang === "ar" ? "تمت الإضافة" : "Added!" ?></span>';
                btn.style.background = '#059669';
                showToast('success', '<?= $lang === "ar" ? "تمت الإضافة للسلة" : "Added to cart!" ?>');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                showToast('error', data.error || 'Failed');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(() => {
            showToast('error', 'Network error');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    function showToast(type, message) {
        const toast = document.createElement('div');
        toast.className = 'toast-alert ' + type;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>
