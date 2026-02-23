<?php
/**
 * Poshy Store - Modern Homepage
 * Clean, responsive design with category filtering
 */

// Central config (DB credentials, SITE_URL, error logging)
require_once __DIR__ . '/config.php';

// ── Slug Router ──────────────────────────────────────────────
// nginx sends all unknown URLs to index.php via try_files.
// Detect product slug URLs and hand off to product.php.
$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$request_path = rtrim($request_path, '/');

// A slug looks like /some-product-name (lowercase, digits, hyphens only)
// Exclude known paths: /index.php, /pages/..., /api/..., /images/..., etc.
if (
    $request_path !== '' &&
    $request_path !== '/' &&
    preg_match('#^/([a-z0-9]+(?:-[a-z0-9]+)*)$#', $request_path, $m) &&
    !preg_match('#^/(index|product|signin|signup|welcome|start|status|pages|api|images|includes|vendor|css|js|fonts)#i', $request_path)
) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/product.php';
    exit;
}
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/product_manager.php';
require_once __DIR__ . '/includes/cart_handler.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/product_image_helper.php';

// Points wallet - optional
if (file_exists(__DIR__ . '/includes/points_wallet_handler.php')) {
    require_once __DIR__ . '/includes/points_wallet_handler.php';
}

$lang = $current_lang; // alias for templates
$is_logged_in = isset($_SESSION['user_id']);
$cart_count = 0;
$user_points = 0;
$user_wallet_balance = 0;

if ($is_logged_in) {
    try {
        $cart_info = getCartCount($_SESSION['user_id']);
        $cart_count = $cart_info['count'] ?? 0;
        
        if (function_exists('getUserPointsAndWallet')) {
            $points_wallet_info = getUserPointsAndWallet($_SESSION['user_id']);
            if ($points_wallet_info && is_array($points_wallet_info)) {
                $user_points = $points_wallet_info['points'] ?? 0;
                $user_wallet_balance = $points_wallet_info['wallet_balance'] ?? 0;
            }
        }
    } catch (Exception $e) {
        error_log("Homepage user data error: " . $e->getMessage());
    }
}

// Get search & filter params (sanitized)
$search_query = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8')) : '';
$active_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$active_subcategory = isset($_GET['subcategory']) ? (int)$_GET['subcategory'] : 0;
$active_brand = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$active_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$show_all = isset($_GET['show_all']) ? true : false;

// Load categories
$all_categories = [];
try {
    $all_categories = getAllCategories();
} catch (Exception $e) {
    error_log("Failed to load categories: " . $e->getMessage());
}

// Get products
$products_array = [];
$is_search_mode = !empty($search_query);
$is_tag_mode = !empty($active_tag);
$is_brand_mode = ($active_brand > 0);
$products_limit = ($is_search_mode || $is_tag_mode || $is_brand_mode || $active_subcategory > 0 || $active_category > 0 || $show_all) ? 100 : 8;

try {
    if ($is_search_mode) {
        $search_filters = ['search' => $search_query, 'in_stock' => true];
        $products_result = getAllProducts($search_filters, $products_limit);
    } elseif ($is_tag_mode) {
        // Tag-based product search
        $tag_products = getProductsByTag($active_tag, $products_limit);
        $products_result = ['products' => $tag_products, 'success' => true];
    } elseif ($is_brand_mode) {
        $products_result = getAllProducts(['brand_id' => $active_brand, 'in_stock' => true], $products_limit);
    } elseif ($active_subcategory > 0) {
        $products_result = getAllProducts(['subcategory_id' => $active_subcategory, 'in_stock' => true], $products_limit);
    } elseif ($active_category > 0) {
        $products_result = getAllProducts(['category_id' => $active_category, 'in_stock' => true], $products_limit);
    } else {
        $products_result = getAllProducts(['in_stock' => true], $products_limit);
    }
    
    if (!empty($products_result['products'])) {
        $products_array = $products_result['products'];
    }
} catch (Exception $e) {
    error_log("Failed to load products: " . $e->getMessage());
}

// Count total products for "View All" link
$total_products_count = 0;
if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && $active_subcategory === 0 && $active_category === 0 && !$show_all) {
    try {
        $all_products_result = getAllProducts(['in_stock' => true], 200);
        $total_products_count = count($all_products_result['products'] ?? []);
    } catch (Exception $e) {
        error_log("Failed to count products: " . $e->getMessage());
    }
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Poshy Store - Premium beauty & skincare products">
    <title><?= t('welcome') ?> - Poshy Store</title>
    
    <?php require_once __DIR__ . '/includes/ramadan_theme_header.php'; ?>
    
    <style>
        :root {
            --primary: var(--deep-purple, #2d132c);
            --primary-light: var(--purple-dark, #1a0a18);
            --accent: var(--royal-gold, #c9a86a);
            --accent-light: var(--gold-light, #e4d4b4);
            --accent-dark: #a88a4e;
            --surface: #ffffff;
            --surface-alt: var(--creamy-white, #fcf8f2);
            --surface-hover: #f0ede6;
            --text-primary: var(--deep-purple, #2d132c);
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --border: #e5e7eb;
            --border-light: #f3f4f6;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
            --shadow-xl: 0 20px 50px rgba(0,0,0,0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [dir="rtl"] { text-align: right; }
        [dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }
        [dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }
        [dir="rtl"] .ms-3 { margin-left: 0 !important; margin-right: 1rem !important; }

        /* Navbar handled by ramadan_navbar.php */

        /* ============ HERO SECTION ============ */
        .hero {
            background: linear-gradient(135deg, var(--deep-purple, #2d132c) 0%, #3d1a3c 60%, var(--purple-dark, #1a0a18) 100%);
            padding: 4rem 1.5rem 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(201,168,106,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(201,168,106,0.07) 0%, transparent 40%);
        }

        .hero::after {
            content: '☪';
            position: absolute;
            top: 1rem; right: 2rem;
            font-size: 4rem;
            color: rgba(201,168,106,0.08);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes heroFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }

        .hero-ramadan-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(201,168,106,0.15);
            color: var(--accent-light);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(201,168,106,0.2);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin: 0 auto;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }

        .hero h1 span {
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: clamp(1rem, 2.5vw, 1.15rem);
            color: var(--accent-light);
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--royal-gold, #c9a86a) 0%, #b39358 100%);
            color: var(--deep-purple, #2d132c);
            padding: 0.85rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
        }

        .hero-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(201,168,106,0.5);
            color: var(--deep-purple, #2d132c);
        }

        /* ============ SEARCH & FILTER BAR ============ */
        .filter-bar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: relative;
            z-index: 100;
        }

        .filter-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .search-row {
            margin-bottom: 0.75rem;
        }

        .search-form {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid var(--border);
            border-radius: 50px;
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--surface-alt);
            color: var(--text-primary);
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(201,169,110,0.1);
        }

        .search-input::placeholder { color: var(--text-muted); }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
            pointer-events: none;
        }

        [dir="rtl"] .search-icon { left: auto; right: 1rem; }
        [dir="rtl"] .search-input { padding: 0.75rem 2.75rem 0.75rem 1rem; }

        /* Category Chips */
        .category-chips {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 0.25rem 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .category-chips::-webkit-scrollbar { display: none; }

        .cat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            border: 1.5px solid var(--border);
            color: var(--text-secondary);
            background: var(--surface);
            white-space: nowrap;
            transition: var(--transition);
            cursor: pointer;
        }

        .cat-chip:hover {
            border-color: var(--accent);
            color: var(--accent-dark);
            background: rgba(201,169,110,0.05);
        }

        .cat-chip.active {
            background: var(--primary);
            color: var(--accent-light);
            border-color: var(--primary);
        }

        .cat-chip .chip-count {
            font-size: 0.7rem;
            background: var(--border-light);
            color: var(--text-muted);
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            font-weight: 600;
        }

        .cat-chip.active .chip-count {
            background: rgba(201,169,110,0.2);
            color: var(--accent-light);
        }

        .cat-chip i { font-size: 0.8rem; }

        /* ============ REWARDS BANNER ============ */
        .rewards-strip {
            background: linear-gradient(135deg, var(--deep-purple, #2d132c) 0%, var(--purple-dark, #1a0a18) 100%);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid var(--royal-gold, #c9a86a);
        }

        .rewards-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .rewards-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
        }

        .rewards-icon { font-size: 1.5rem; color: var(--accent); }

        .rewards-text { font-size: 0.95rem; }
        .rewards-text strong { color: var(--accent); font-size: 1.1rem; }

        .rewards-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .rewards-btn:hover {
            background: var(--accent-light);
            color: var(--primary);
        }

        /* ============ PRODUCTS SECTION ============ */
        .products-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .section-title i { color: var(--royal-gold, #c9a86a); margin-right: 0.5rem; font-size: 1.2rem; }

        .view-all-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--accent-dark);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .view-all-link:hover { color: var(--accent); gap: 0.6rem; }

        .search-info-bar {
            background: rgba(201,169,110,0.08);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: var(--radius-md);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .search-info-bar .search-term { 
            font-weight: 600; 
            color: var(--accent-dark);
        }

        .clear-search {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-decoration: none;
            border: 1px solid var(--border);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            transition: var(--transition);
        }

        .clear-search:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Product Grid */
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
            border: 1px solid var(--border-light);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .p-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 30px rgba(201, 168, 106, 0.3);
            border-color: var(--royal-gold, #c9a86a);
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
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .p-card:hover .p-card-img img {
            transform: scale(1.08);
        }

        .p-card-img .discount-tag {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
        }

        [dir="rtl"] .p-card-img .discount-tag { right: auto; left: 0.75rem; }

        .p-card-img .cat-tag {
            position: absolute;
            bottom: 0.75rem;
            left: 0.75rem;
            background: rgba(45,19,44,0.85);
            color: var(--accent-light);
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            font-size: 0.7rem;
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

        .price-now {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-dark);
        }

        .price-was {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .p-card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-cart {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.65rem 0.75rem;
            background: linear-gradient(135deg, var(--deep-purple, #2d132c) 0%, var(--purple-dark, #1a0a18) 100%);
            color: var(--accent-light);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(45, 19, 44, 0.2);
        }

        .btn-cart:hover {
            background: linear-gradient(135deg, var(--royal-gold, #c9a86a) 0%, #b39358 100%);
            color: var(--deep-purple, #2d132c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.4);
        }

        .btn-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

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

        .btn-view:hover {
            border-color: var(--accent);
            color: var(--accent-dark);
            background: rgba(201,169,110,0.05);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--accent-light);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, var(--royal-gold, #c9a86a) 0%, #b39358 100%);
            color: var(--deep-purple, #2d132c);
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
        }

        .empty-state a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.5);
        }

        /* ============ FOOTER ============ */
        .site-footer {
            background: linear-gradient(135deg, var(--deep-purple, #2d132c) 0%, var(--purple-dark, #1a0a18) 100%);
            color: var(--creamy-white, #fcf8f2);
            margin-top: 2rem;
        }

        .footer-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 1.5rem 1.5rem;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 2rem;
        }

        .footer-brand {
            font-family: 'Dancing Script', 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            line-height: 1.1;
            filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
        }

        .footer-brand small {
            display: block;
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-size: 0.7rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            font-weight: 300;
            background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-desc {
            font-size: 0.9rem;
            line-height: 1.7;
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            gap: 0.75rem;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(201,168,106,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-light, #e4d4b4);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .social-links a:hover {
            background: var(--royal-gold, #c9a86a);
            color: var(--deep-purple, #2d132c);
            border-color: var(--royal-gold, #c9a86a);
        }

        .footer-heading {
            font-weight: 600;
            color: var(--royal-gold, #c9a86a);
            margin-bottom: 1rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li { margin-bottom: 0.5rem; }

        .footer-links a {
            color: var(--gold-light, #e4d4b4);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--royal-gold, #c9a86a);
            padding-left: 4px;
        }

        [dir="rtl"] .footer-links a:hover {
            padding-left: 0;
            padding-right: 4px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            padding: 1rem 1.5rem;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* ============ RESPONSIVE ============ */
        
        /* Tablets */
        @media (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .hero {
                padding: 2.5rem 1rem 2rem;
            }

            .p-card-body {
                padding: 0.75rem;
            }

            .p-card-name {
                font-size: 0.85rem;
                min-height: 2.4em;
            }

            .p-card-name-ar {
                font-size: 0.75rem;
            }

            .price-now {
                font-size: 1.05rem;
            }

            .btn-cart {
                font-size: 0.75rem;
                padding: 0.55rem 0.5rem;
            }

            .btn-view {
                width: 36px;
            }

            .footer-main {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .navbar-inner {
                padding: 0 1rem;
            }

            .brand {
                font-size: 1.4rem;
            }

            .nav-btn {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .rewards-inner {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Small phones */
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .p-card-body {
                padding: 0.6rem;
            }

            .p-card-name {
                font-size: 0.8rem;
            }

            .price-now {
                font-size: 0.95rem;
            }

            .p-card-actions {
                flex-direction: column;
            }

            .btn-view {
                width: 100%;
                height: 36px;
            }

            .hero h1 {
                font-size: 1.6rem;
            }

            .hero p {
                font-size: 0.9rem;
            }

            .hero-cta {
                padding: 0.7rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        /* ============ UTILITIES ============ */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alert Toasts */
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

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        [dir="rtl"] .toast-alert { right: auto; left: 1rem; }

        [dir="rtl"] @keyframes slideInRight {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/ramadan_navbar.php'; ?>

    <!-- ======== HERO SECTION ======== -->
    <section class="hero">
        <div class="hero-content fade-in">
            <div class="hero-ramadan-badge">
                <i class="fas fa-moon"></i> <?= $lang === 'ar' ? 'عروض رمضان الخاصة' : 'Ramadan Special Offers' ?> <i class="fas fa-star-and-crescent"></i>
            </div>
            <h1><?= t('welcome') ?> <span>Poshy Store</span></h1>
            <p><?= t('tagline') ?></p>
            <a href="#products" class="hero-cta">
                <i class="fas fa-shopping-bag"></i> <?= t('shop_now') ?>
            </a>
        </div>
    </section>

    <!-- ======== REWARDS BANNER ======== -->
    <?php if ($is_logged_in && $user_points > 0): ?>
    <div class="rewards-strip">
        <div class="rewards-inner">
            <div class="rewards-info">
                <i class="fas fa-award rewards-icon"></i>
                <span class="rewards-text">
                    <?= t('you_have_points') ?> <strong><?= number_format($user_points) ?></strong> <?= t('points') ?>
                    <?php if ($user_wallet_balance > 0): ?>
                        &nbsp;|&nbsp; <?= t('wallet') ?>: <strong><?= number_format($user_wallet_balance, 3) ?> <?= t('currency') ?></strong>
                    <?php endif; ?>
                </span>
            </div>
            <a href="pages/shop/points_wallet.php" class="rewards-btn">
                <i class="fas fa-gift me-1"></i> <?= t('rewards') ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======== SEARCH & CATEGORIES ======== -->
    <div class="filter-bar" id="filterBar">
        <div class="filter-inner">
            <!-- Search -->
            <div class="search-row">
                <form method="GET" action="index.php" class="search-form">
                    <i class="fas fa-search search-icon"></i>
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="<?= t('search_products') ?>"
                        value="<?= htmlspecialchars($search_query) ?>"
                        autocomplete="off"
                    >
                </form>
            </div>
            
            <!-- Categories -->
            <?php if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && !empty($all_categories)): ?>
            <div class="category-chips">
                <a href="javascript:void(0)" onclick="filterByCategory(0, this)" class="cat-chip <?= ($active_subcategory === 0 && $active_category === 0 && !$show_all) ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <?= $lang === 'ar' ? 'الكل' : 'All' ?>
                </a>
                <?php foreach ($all_categories as $cat): ?>
                    <?php foreach ($cat['subcategories'] as $sub): ?>
                        <a href="javascript:void(0)" onclick="filterByCategory(<?= $sub['id'] ?>, this)"
                           class="cat-chip <?= $active_subcategory === (int)$sub['id'] ? 'active' : '' ?>">
                            <?php if (!empty($sub['icon'])): ?>
                                <i class="<?= htmlspecialchars($sub['icon']) ?>"></i>
                            <?php endif; ?>
                            <?= $lang === 'ar' ? htmlspecialchars($sub['name_ar']) : htmlspecialchars($sub['name_en']) ?>
                            <?php if ($sub['product_count'] > 0): ?>
                                <span class="chip-count"><?= $sub['product_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======== PRODUCTS SECTION ======== -->
    <section class="products-section" id="products">

        <?php if ($is_search_mode): ?>
            <div class="search-info-bar fade-in">
                <span>
                    <i class="fas fa-search me-1"></i>
                    <?= t('search_results_for') ?> <span class="search-term">"<?= htmlspecialchars($search_query) ?>"</span>
                    <small style="color: var(--text-muted); margin-left: 0.5rem;">(<?= count($products_array) ?> <?= t('products') ?>)</small>
                </span>
                <a href="index.php" class="clear-search">
                    <i class="fas fa-times me-1"></i><?= t('clear_search') ?>
                </a>
            </div>
        <?php elseif ($is_tag_mode): ?>
            <div class="search-info-bar fade-in">
                <span>
                    <i class="fas fa-tag me-1"></i>
                    <?= $current_lang === 'ar' ? 'نتائج الوسم' : 'Tag results for' ?> <span class="search-term">"<?= htmlspecialchars($active_tag) ?>"</span>
                    <small style="color: var(--text-muted); margin-left: 0.5rem;">(<?= count($products_array) ?> <?= t('products') ?>)</small>
                </span>
                <a href="index.php" class="clear-search">
                    <i class="fas fa-times me-1"></i><?= $current_lang === 'ar' ? 'مسح' : 'Clear' ?>
                </a>
            </div>
        <?php elseif ($is_brand_mode): ?>
            <div class="search-info-bar fade-in">
                <span>
                    <i class="fas fa-building me-1"></i>
                    <?= $current_lang === 'ar' ? 'منتجات العلامة التجارية' : 'Brand products' ?>
                    <small style="color: var(--text-muted); margin-left: 0.5rem;">(<?= count($products_array) ?> <?= t('products') ?>)</small>
                </span>
                <a href="index.php" class="clear-search">
                    <i class="fas fa-times me-1"></i><?= $current_lang === 'ar' ? 'مسح' : 'Clear' ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-sparkles"></i>
                <?php if ($active_subcategory > 0): ?>
                    <?php 
                    // Find the active subcategory name
                    $active_sub_name = '';
                    foreach ($all_categories as $cat) {
                        foreach ($cat['subcategories'] as $sub) {
                            if ((int)$sub['id'] === $active_subcategory) {
                                $active_sub_name = $lang === 'ar' ? $sub['name_ar'] : $sub['name_en'];
                                break 2;
                            }
                        }
                    }
                    echo htmlspecialchars($active_sub_name ?: t('products'));
                    ?>
                <?php elseif ($is_search_mode): ?>
                    <?= t('search_results_for') ?>
                <?php else: ?>
                    <?= $show_all ? t('view_all_products') : t('featured_products') ?>
                <?php endif; ?>
            </h2>

            <?php if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && $active_subcategory === 0 && $active_category === 0 && !$show_all && $total_products_count > count($products_array)): ?>
                <a href="javascript:void(0)" onclick="showAllProducts(this)" class="view-all-link">
                    <?= t('view_all') ?> (<?= $total_products_count ?>)
                    <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($products_array)): ?>
            <!-- Empty State -->
            <div class="empty-state fade-in">
                <i class="fas fa-box-open"></i>
                <h3><?= t('no_products_found') ?></h3>
                <p><?= t('try_searching_else') ?></p>
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> <?= t('view_all_products') ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Product Grid -->
            <div class="product-grid">
                <?php foreach ($products_array as $idx => $product): ?>
                <div class="p-card fade-in" style="animation-delay: <?= $idx * 0.05 ?>s;">
                    <div class="p-card-img">
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
                                __DIR__
                            );
                        ?>
                        <a href="<?= htmlspecialchars($product['slug'] ?? '#') ?>">
                            <img 
                                src="<?= htmlspecialchars($image_src) ?>" 
                                alt="<?= htmlspecialchars($product['name_en']) ?>"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='images/placeholder-cosmetics.svg';"
                            >
                        </a>
                    </div>

                    <div class="p-card-body">
                        <a href="<?= htmlspecialchars($product['slug'] ?? '#') ?>" style="text-decoration:none; color:inherit;">
                            <div class="p-card-name"><?= htmlspecialchars($product['name_en']) ?></div>
                            <div class="p-card-name-ar"><?= htmlspecialchars($product['name_ar'] ?? '') ?></div>
                        </a>
                        
                        <div class="p-card-price">
                            <span class="price-now"><?= number_format($product['price_jod'], 3) ?> <?= t('currency') ?></span>
                            <?php if (!empty($product['has_discount']) && $product['original_price'] > 0): ?>
                                <span class="price-was"><?= number_format($product['original_price'], 3) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="p-card-actions">
                            <?php if ($is_logged_in): ?>
                                <button class="btn-cart" onclick="addToCart(<?= (int)$product['id'] ?>, this)">
                                    <i class="fas fa-cart-plus"></i>
                                    <span><?= t('add_to_cart') ?></span>
                                </button>
                            <?php else: ?>
                                <a href="pages/auth/signin.php" class="btn-cart">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span><?= t('login') ?></span>
                                </a>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($product['slug'] ?? '#') ?>" class="btn-view" title="<?= t('details') ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && $active_subcategory === 0 && $active_category === 0 && !$show_all && $total_products_count > count($products_array)): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="javascript:void(0)" onclick="showAllProducts(this)" class="hero-cta" style="font-size: 0.9rem; padding: 0.7rem 1.75rem;">
                    <i class="fas fa-th-large"></i> <?= t('view_all_products') ?> (<?= $total_products_count ?>)
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- ======== FOOTER ======== -->
    <?php require_once __DIR__ . '/includes/ramadan_footer.php'; ?>

    <script>
    // Current language for translations
    const CURRENT_LANG = '<?= $lang ?>';
    const IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;
    const CURRENCY_TEXT = '<?= addslashes(t("currency")) ?>';
    const ADD_TO_CART_TEXT = '<?= addslashes(t("add_to_cart")) ?>';
    const LOGIN_TEXT = '<?= addslashes(t("login")) ?>';
    const DETAILS_TEXT = '<?= addslashes(t("details")) ?>';
    const FEATURED_TEXT = '<?= addslashes(t("featured_products")) ?>';
    const VIEW_ALL_TEXT = '<?= addslashes(t("view_all_products")) ?>';
    const VIEW_ALL_LINK_TEXT = '<?= addslashes(t("view_all")) ?>';
    const NO_PRODUCTS_TEXT = '<?= addslashes(t("no_products_found")) ?>';
    const TRY_SEARCHING_TEXT = '<?= addslashes(t("try_searching_else")) ?>';
    
    // ==========================================
    // AJAX Category Filter (no page refresh)
    // ==========================================
    let currentFilter = { subcategory: <?= $active_subcategory ?>, show_all: <?= $show_all ? 'true' : 'false' ?> };
    
    function filterByCategory(subcategoryId, chipEl) {
        // Update active chip
        document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
        if (chipEl) chipEl.classList.add('active');
        
        currentFilter.subcategory = subcategoryId;
        currentFilter.show_all = false;
        
        // Build URL
        let apiUrl = 'api/get_products.php?';
        if (subcategoryId > 0) {
            apiUrl += 'subcategory=' + subcategoryId;
        }
        
        // Show loading
        const grid = document.querySelector('.product-grid');
        if (grid) {
            grid.style.opacity = '0.5';
            grid.style.pointerEvents = 'none';
        }
        
        fetch(apiUrl)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderProducts(data.products, data.filter_name, data.total_count, data.show_all, subcategoryId);
                    // Update URL without reload
                    let newUrl = subcategoryId > 0 ? 'index.php?subcategory=' + subcategoryId : 'index.php';
                    history.pushState({ subcategory: subcategoryId }, '', newUrl);
                }
            })
            .catch(err => {
                console.error('Filter error:', err);
                if (grid) { grid.style.opacity = '1'; grid.style.pointerEvents = ''; }
            });
    }
    
    function showAllProducts(el) {
        // Update active chip - remove active from all category chips
        document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
        
        currentFilter.subcategory = 0;
        currentFilter.show_all = true;
        
        // Show loading
        const grid = document.querySelector('.product-grid');
        if (grid) {
            grid.style.opacity = '0.5';
            grid.style.pointerEvents = 'none';
        }
        
        fetch('api/get_products.php?show_all=1')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderProducts(data.products, '', 0, true, 0);
                    history.pushState({ show_all: true }, '', 'index.php?show_all=1');
                }
            })
            .catch(err => {
                console.error('Show all error:', err);
                if (grid) { grid.style.opacity = '1'; grid.style.pointerEvents = ''; }
            });
    }
    
    function renderProducts(products, filterName, totalCount, showAll, subcategoryId) {
        const section = document.querySelector('.products-section');
        if (!section) return;
        
        // Build title
        let titleText = '';
        if (filterName) {
            titleText = filterName;
        } else if (showAll) {
            titleText = VIEW_ALL_TEXT;
        } else {
            titleText = FEATURED_TEXT;
        }
        
        // Build view-all link
        let viewAllHtml = '';
        if (!showAll && subcategoryId === 0 && totalCount > products.length) {
            viewAllHtml = '<a href="javascript:void(0)" onclick="showAllProducts(this)" class="view-all-link">' +
                VIEW_ALL_LINK_TEXT + ' (' + totalCount + ') <i class="fas fa-arrow-right"></i></a>';
        }
        
        // Build product cards
        let cardsHtml = '';
        if (products.length === 0) {
            cardsHtml = '<div class="empty-state fade-in">' +
                '<i class="fas fa-box-open"></i>' +
                '<h3>' + NO_PRODUCTS_TEXT + '</h3>' +
                '<p>' + TRY_SEARCHING_TEXT + '</p></div>';
        } else {
            cardsHtml = '<div class="product-grid">';
            products.forEach(function(product, idx) {
                // Discount tag
                let discountTag = '';
                if (product.has_discount && product.discount_percentage > 0) {
                    discountTag = '<span class="discount-tag">-' + product.discount_percentage + '%</span>';
                }
                
                // Category tag
                let catTag = '';
                let subName = CURRENT_LANG === 'ar' ? product.subcategory_ar : product.subcategory_en;
                if (subName) {
                    catTag = '<span class="cat-tag">' + subName + '</span>';
                }
                
                // Price
                let priceHtml = '<span class="price-now">' + product.price_jod + ' ' + CURRENCY_TEXT + '</span>';
                if (product.has_discount && product.original_price) {
                    priceHtml += '<span class="price-was">' + product.original_price + '</span>';
                }
                
                // Actions
                let actionsHtml = '';
                if (IS_LOGGED_IN) {
                    actionsHtml = '<button class="btn-cart" onclick="addToCart(' + product.id + ', this)">' +
                        '<i class="fas fa-cart-plus"></i><span>' + ADD_TO_CART_TEXT + '</span></button>';
                } else {
                    actionsHtml = '<a href="pages/auth/signin.php" class="btn-cart">' +
                        '<i class="fas fa-sign-in-alt"></i><span>' + LOGIN_TEXT + '</span></a>';
                }
                actionsHtml += '<a href="' + product.slug + '" class="btn-view" title="' + DETAILS_TEXT + '">' +
                    '<i class="fas fa-eye"></i></a>';
                
                cardsHtml += '<div class="p-card fade-in" style="animation-delay: ' + (idx * 0.05) + 's;">' +
                    '<div class="p-card-img">' + discountTag + catTag +
                    '<a href="' + product.slug + '"><img src="' + product.image_src + '" alt="' + product.name_en + '" loading="lazy" ' +
                    'onerror="this.onerror=null; this.src=\'images/placeholder-cosmetics.svg\';"></a></div>' +
                    '<div class="p-card-body">' +
                    '<a href="' + product.slug + '" style="text-decoration:none; color:inherit;">' +
                    '<div class="p-card-name">' + product.name_en + '</div>' +
                    '<div class="p-card-name-ar">' + product.name_ar + '</div></a>' +
                    '<div class="p-card-price">' + priceHtml + '</div>' +
                    '<div class="p-card-actions">' + actionsHtml + '</div>' +
                    '</div></div>';
            });
            cardsHtml += '</div>';
            
            // Bottom view-all button
            if (!showAll && subcategoryId === 0 && totalCount > products.length) {
                cardsHtml += '<div style="text-align: center; margin-top: 2rem;">' +
                    '<a href="javascript:void(0)" onclick="showAllProducts(this)" class="hero-cta" style="font-size: 0.9rem; padding: 0.7rem 1.75rem;">' +
                    '<i class="fas fa-th-large"></i> ' + VIEW_ALL_TEXT + ' (' + totalCount + ')</a></div>';
            }
        }
        
        // Update the section
        section.innerHTML = '<div class="section-header">' +
            '<h2 class="section-title"><i class="fas fa-sparkles"></i> ' + titleText + '</h2>' +
            viewAllHtml + '</div>' + cardsHtml;
        
        // Smooth scroll to products
        const offset = 20;
        const y = section.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
    
    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        if (e.state) {
            if (e.state.subcategory !== undefined) {
                filterByCategory(e.state.subcategory, null);
            } else if (e.state.show_all) {
                showAllProducts(null);
            }
        }
    });

    // ==========================================
    // Add to Cart (AJAX)
    // ==========================================
    function addToCart(productId, btn) {
        if (!btn) return;
        const originalHTML = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('api/add_to_cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
        })
        .then(response => {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update cart badge
                let badge = document.querySelector('.nav-badge');
                if (badge) {
                    badge.textContent = data.cart_count || (parseInt(badge.textContent || '0') + 1);
                } else {
                    const cartBtn = document.querySelector('a[href="pages/shop/cart.php"]');
                    if (cartBtn) {
                        const b = document.createElement('span');
                        b.className = 'nav-badge';
                        b.textContent = data.cart_count || '1';
                        cartBtn.appendChild(b);
                    }
                }
                
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Added!</span>';
                btn.style.background = '#059669';
                
                showToast('success', '<?= $lang === "ar" ? "تمت الإضافة للسلة" : "Added to cart!" ?>');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                showToast('error', data.error || 'Failed to add to cart');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            showToast('error', 'Network error. Please try again.');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }

    // ==========================================
    // Toast Notifications
    // ==========================================
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

    // ==========================================
    // Smooth scroll
    // ==========================================
    document.querySelectorAll('a[href*="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const hash = href.split('#')[1];
            if (hash) {
                const target = document.getElementById(hash);
                if (target) {
                    e.preventDefault();
                    const offset = 20;
                    const y = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                    
                    // Update URL without page jump
                    if (history.pushState) {
                        history.pushState(null, null, href);
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
