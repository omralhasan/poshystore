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

// The redesigned index.php now serves as the main landing page
// No separate landing page needed - the Beauty Box design is built into index.php
// if ($request_path === '' || $request_path === '/') {
//     require __DIR__ . '/poshy-luxury-home.html';
//     exit;
// }

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
require_once __DIR__ . '/includes/guest_cart_handler.php';
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
} else {
    // Guest cart count
    try {
        $guest_cart_info = guestGetCartCount();
        $cart_count = $guest_cart_info['count'] ?? 0;
    } catch (Exception $e) {
        // guest_cart table might not exist yet
    }
}

// Get search & filter params (sanitized)
$search_query = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8')) : '';
$category_param = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$active_category = ctype_digit($category_param) ? (int)$category_param : 0;
$active_category_keyword = ($active_category === 0 && $category_param !== '')
    ? strtolower(preg_replace('/[^a-z0-9]+/', '', $category_param))
    : '';
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

// Homepage category set: Skin Care, Hair Care, Makeup
$homepage_categories = [];
$home_category_slots = [
    'skin' => null,
    'hair' => null,
    'makeup' => null,
];

foreach ($all_categories as $cat) {
    $name_en = strtolower(trim((string)($cat['name_en'] ?? '')));
    $normalized = preg_replace('/[^a-z0-9]+/', '', $name_en);

    if ($home_category_slots['skin'] === null && (str_contains($normalized, 'skin') || str_contains($normalized, 'skincare'))) {
        $home_category_slots['skin'] = $cat;
        continue;
    }
    if ($home_category_slots['hair'] === null && (str_contains($normalized, 'hair') || str_contains($normalized, 'haircare'))) {
        $home_category_slots['hair'] = $cat;
        continue;
    }
    if ($home_category_slots['makeup'] === null && (str_contains($normalized, 'makeup') || str_contains($normalized, 'cosmetic'))) {
        $home_category_slots['makeup'] = $cat;
        continue;
    }
}

foreach (['skin', 'hair', 'makeup'] as $slot) {
    if (!empty($home_category_slots[$slot])) {
        $homepage_categories[] = $home_category_slots[$slot];
    }
}

// Allow category filter by keyword slug (e.g. category=skin-care)
if ($active_category === 0 && $active_category_keyword !== '') {
    if (str_contains($active_category_keyword, 'skin') && !empty($home_category_slots['skin'])) {
        $active_category = (int)$home_category_slots['skin']['id'];
    } elseif (str_contains($active_category_keyword, 'hair') && !empty($home_category_slots['hair'])) {
        $active_category = (int)$home_category_slots['hair']['id'];
    } elseif ((str_contains($active_category_keyword, 'makeup') || str_contains($active_category_keyword, 'cosmetic')) && !empty($home_category_slots['makeup'])) {
        $active_category = (int)$home_category_slots['makeup']['id'];
    }
}

$active_category_name = '';
if ($active_category > 0) {
    foreach ($all_categories as $cat) {
        if ((int)$cat['id'] === $active_category) {
            $active_category_name = $lang === 'ar'
                ? (!empty($cat['name_ar']) ? $cat['name_ar'] : $cat['name_en'])
                : $cat['name_en'];
            break;
        }
    }
}

// Load brands for filter dropdown
$all_brands = [];
try {
    $brands_result = $conn->query("SELECT id, name_en, name_ar FROM brands ORDER BY name_en ASC");
    if ($brands_result) {
        while ($br = $brands_result->fetch_assoc()) $all_brands[] = $br;
    }
} catch (Exception $e) {
    error_log("Failed to load brands: " . $e->getMessage());
}

// Get products
$products_array = [];
$is_search_mode = !empty($search_query);
$is_tag_mode = !empty($active_tag);
$is_brand_mode = ($active_brand > 0);
$is_filtered_mode = ($is_search_mode || $is_tag_mode || $is_brand_mode || $active_subcategory > 0 || $active_category > 0 || $show_all);
$products_limit = $is_filtered_mode ? 100 : 8;

try {
    if ($is_search_mode) {
        $search_filters = ['search' => $search_query];
        $products_result = getAllProducts($search_filters, $products_limit);
    } elseif ($is_tag_mode) {
        $tag_products = getProductsByTag($active_tag, $products_limit);
        $products_result = ['products' => $tag_products, 'success' => true];
    } elseif ($is_brand_mode) {
        $products_result = getAllProducts(['brand_id' => $active_brand], $products_limit);
    } elseif ($active_subcategory > 0) {
        $products_result = getAllProducts(['subcategory_id' => $active_subcategory], $products_limit);
    } elseif ($active_category > 0) {
        $products_result = getAllProducts(['category_id' => $active_category], $products_limit);
    } else {
        $products_result = getAllProducts([], $products_limit);
    }
    
    if (!empty($products_result['products'])) {
        $products_array = $products_result['products'];
    }
} catch (Exception $e) {
    error_log("Failed to load products: " . $e->getMessage());
}

// Count total products for "View All" link
$total_products_count = 0;
if (!$is_filtered_mode) {
    try {
        $all_products_result = getAllProducts([], 200);
        $total_products_count = count($all_products_result['products'] ?? []);
    } catch (Exception $e) {
        error_log("Failed to count products: " . $e->getMessage());
    }
}

// ── Homepage recommended sections per category ──
$category_recommended = [];
if (!$is_filtered_mode) {
    foreach ($homepage_categories as $cat) {
        $cat_id = (int)$cat['id'];
        try {
            // Get recommended products first (is_recommended=1) for this category
            $rec_sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar,
                               p.price_jod, p.supplier_cost, p.stock_quantity, p.image_link, p.subcategory_id, p.brand_id,
                               p.original_price, p.discount_percentage, p.has_discount,
                               p.is_recommended, p.is_best_seller,
                               s.name_en AS subcategory_en, s.name_ar AS subcategory_ar,
                               c.name_en AS category_en, c.name_ar AS category_ar,
                               b.name_en AS brand_en, b.name_ar AS brand_ar
                        FROM products p
                        LEFT JOIN subcategories s ON p.subcategory_id = s.id
                        LEFT JOIN categories c ON s.category_id = c.id
                        LEFT JOIN brands b ON p.brand_id = b.id
                        WHERE s.category_id = ? AND p.is_recommended = 1
                        ORDER BY p.is_best_seller DESC, p.id DESC
                        LIMIT 4";
            $rec_stmt = $conn->prepare($rec_sql);
            $rec_stmt->bind_param('i', $cat_id);
            $rec_stmt->execute();
            $rec_result = $rec_stmt->get_result();
            $rec_products = [];
            while ($row = $rec_result->fetch_assoc()) {
                $row['price_formatted'] = formatJOD($row['price_jod']);
                $rec_products[] = $row;
            }
            $rec_stmt->close();

            // If fewer than 4 recommended, fill with best sellers / newest
            if (count($rec_products) < 4) {
                $existing_ids = array_map(fn($p) => (int)$p['id'], $rec_products);
                $exclude = !empty($existing_ids) ? implode(',', $existing_ids) : '0';
                $remaining = 4 - count($rec_products);
                $fill_sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar,
                                    p.price_jod, p.supplier_cost, p.stock_quantity, p.image_link, p.subcategory_id, p.brand_id,
                                    p.original_price, p.discount_percentage, p.has_discount,
                                    p.is_recommended, p.is_best_seller,
                                    s.name_en AS subcategory_en, s.name_ar AS subcategory_ar,
                                    c.name_en AS category_en, c.name_ar AS category_ar,
                                    b.name_en AS brand_en, b.name_ar AS brand_ar
                             FROM products p
                             LEFT JOIN subcategories s ON p.subcategory_id = s.id
                             LEFT JOIN categories c ON s.category_id = c.id
                             LEFT JOIN brands b ON p.brand_id = b.id
                             WHERE s.category_id = $cat_id AND p.id NOT IN ($exclude)
                             ORDER BY p.is_best_seller DESC, p.id DESC
                             LIMIT $remaining";
                $fill_result = $conn->query($fill_sql);
                if ($fill_result) {
                    while ($row = $fill_result->fetch_assoc()) {
                        $row['price_formatted'] = formatJOD($row['price_jod']);
                        $rec_products[] = $row;
                    }
                }
            }

            // Count total products in this category
            $count_sql = "SELECT COUNT(*) as cnt FROM products p JOIN subcategories s ON p.subcategory_id = s.id WHERE s.category_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param('i', $cat_id);
            $count_stmt->execute();
            $count_row = $count_stmt->get_result()->fetch_assoc();
            $count_stmt->close();

            $category_recommended[] = [
                'category' => $cat,
                'products' => $rec_products,
                'total_count' => (int)$count_row['cnt'],
            ];
        } catch (Exception $e) {
            error_log("Failed to load recommended for category {$cat_id}: " . $e->getMessage());
        }
    }
}

// ── Homepage banners ──
$homepage_banners = [];
$hero_banners = [];
try {
    // Check if banner_type column exists
    $col_check = $conn->query("SHOW COLUMNS FROM homepage_banners LIKE 'banner_type'");
    $has_banner_type = ($col_check && $col_check->num_rows > 0);

    if ($has_banner_type) {
        // Load hero banners for the top slider
        $hero_result = $conn->query("SELECT * FROM homepage_banners WHERE banner_type = 'hero' AND is_active = 1 ORDER BY sort_order ASC, id ASC");
        if ($hero_result) {
            while ($b = $hero_result->fetch_assoc()) {
                $hero_banners[] = $b;
            }
        }
        // Load section banners (between category sections)
        $banners_result = $conn->query("SELECT * FROM homepage_banners WHERE banner_type = 'section' AND is_active = 1 ORDER BY position ASC, id ASC");
    } else {
        // Legacy: all banners are section banners
        $banners_result = $conn->query("SELECT * FROM homepage_banners WHERE is_active = 1 ORDER BY position ASC, id ASC");
    }

    if ($banners_result) {
        while ($b = $banners_result->fetch_assoc()) {
            $pos = (int)$b['position'];
            // Negative position = "before" that section index
            // e.g. position = -1 means "before section 0", -2 means "before section 1"
            if ($pos < 0) {
                $key = 'before_' . abs($pos + 1);
            } else {
                $key = $pos;
            }
            if (!isset($homepage_banners[$key])) {
                $homepage_banners[$key] = [];
            }
            $homepage_banners[$key][] = $b;
        }
    }
} catch (Exception $e) {
    // homepage_banners table might not exist yet - that's OK
    error_log("Homepage banners: " . $e->getMessage());
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
    
    <?php require_once __DIR__ . '/includes/home_theme_header.php'; ?>
    
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
            --shadow-xl: 0 20px 50px rgba(0,0,0,0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --rose: #E8C4B8;
            --rose-soft: #F5E6E0;
        }

        [dir="rtl"] { text-align: right; }
        [dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }
        [dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }
        [dir="rtl"] .ms-3 { margin-left: 0 !important; margin-right: 1rem !important; }

        /* Navbar handled by home_navbar.php */

        /* ============ HERO BANNER SLIDER (BeautyBox-inspired) ============ */
        .hero-banner {
            position: relative;
            width: 100%;
            height: 500px;
            overflow: hidden;
            background: #f5f0eb;
        }

        .hero-banner-track {
            display: flex;
            height: 100%;
            transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        .hero-banner-slide {
            width: 100vw;
            min-width: 100vw;
            height: 100%;
            position: relative;
            flex-shrink: 0;
        }

        .hero-banner-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* ============ TOP SEARCH BAR (above hero) ============ */
        .top-search-bar {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 0.6rem 0;
            position: sticky;
            top: 60px;
            z-index: 90;
        }

        .top-search-inner {
            max-width: 680px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .top-search-form {
            width: 100%;
        }

        .top-search-bar .search-wrapper {
            position: relative;
        }

        .top-search-bar .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            font-size: 0.88rem;
            background: #f9fafb;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .top-search-bar .search-input:focus {
            border-color: var(--accent);
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.15);
        }

        .top-search-bar .search-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        [dir="rtl"] .top-search-bar .search-icon {
            left: auto;
            right: 0.9rem;
        }

        [dir="rtl"] .top-search-bar .search-input {
            padding: 0.65rem 2.5rem 0.65rem 1rem;
        }

        /* Optional text overlay - only rendered if banner has title */
        .hero-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(0, 0, 0, 0.45) 0%,
                rgba(0, 0, 0, 0.18) 45%,
                transparent 70%
            );
            display: flex;
            align-items: center;
            padding: 0 6%;
        }

        [dir="rtl"] .hero-banner-overlay {
            background: linear-gradient(
                -105deg,
                rgba(0, 0, 0, 0.45) 0%,
                rgba(0, 0, 0, 0.18) 45%,
                transparent 70%
            );
        }

        .hero-banner-text {
            max-width: 500px;
        }

        .hero-banner-text .hero-label {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--accent, #C5A059);
            margin-bottom: 0.75rem;
        }

        .hero-banner-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 0.6rem;
        }

        .hero-banner-text p {
            color: rgba(255, 255, 255, 0.88);
            font-size: clamp(0.88rem, 1.5vw, 1.05rem);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .hero-banner-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
            color: #111;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
            border: none;
            cursor: pointer;
        }

        .hero-banner-cta:hover {
            background: #111;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        /* Navigation arrows */
        .hero-banner-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: none;
            color: #111;
            font-size: 1rem;
            cursor: pointer;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .hero-banner-arrow:hover {
            background: #fff;
            transform: translateY(-50%) scale(1.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .hero-banner-arrow.prev { left: 1.25rem; }
        .hero-banner-arrow.next { right: 1.25rem; }

        [dir="rtl"] .hero-banner-arrow.prev { left: auto; right: 1.25rem; }
        [dir="rtl"] .hero-banner-arrow.next { right: auto; left: 1.25rem; }

        /* Bottom controls: dots + pause/play */
        .hero-banner-controls {
            position: absolute;
            bottom: 1.25rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 5;
        }

        .hero-banner-dots {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .hero-banner-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.45);
            border: none;
            padding: 0;
            cursor: pointer;
            transition: all 0.35s ease;
        }

        .hero-banner-dot.active {
            width: 28px;
            border-radius: 5px;
            background: #fff;
        }

        .hero-banner-pause {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: none;
            color: #fff;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .hero-banner-pause:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* ============ SECTION BANNERS (compact stacked) ============ */
        .section-banners {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.5rem 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .section-banner-card {
            display: block;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
        }

        .section-banner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .section-banner-card img {
            width: 100%;
            display: block;
            height: 160px;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .section-banners {
                padding: 0.4rem 1rem 0.75rem;
            }
            .section-banner-card img {
                height: 120px;
            }
        }

        @media (max-width: 480px) {
            .section-banner-card img {
                height: 100px;
                border-radius: 10px;
            }
        }

        /* ============ CATEGORY EXPLORE BAR ============ */
        .category-explore {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .category-hero-banner {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            height: 140px;
            margin-bottom: 1rem;
        }

        .category-hero-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-hero-banner .cat-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-hero-banner .cat-banner-title {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
            letter-spacing: 0.5px;
        }

        .category-explore-links {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .cat-explore-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1.5px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
        }

        .cat-explore-link:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .cat-explore-link i {
            font-size: 0.9rem;
        }

        .cat-explore-link.new-arrivals {
            background: linear-gradient(135deg, #f8f4f0, #fff);
            border-color: var(--accent);
            color: var(--accent);
        }

        .cat-explore-link.new-arrivals:hover {
            background: var(--accent);
            color: #fff;
        }

        .subcategory-chips {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .subcategory-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 500;
            text-decoration: none;
            background: var(--surface);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            transition: all 0.25s ease;
        }

        .subcategory-chip:hover {
            background: linear-gradient(135deg, #f8f4f0, #fff);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-1px);
        }

        .subcategory-chip i {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .subcategory-chip .chip-count {
            font-size: 0.68rem;
            opacity: 0.6;
        }

        @media (max-width: 768px) {
            .category-hero-banner { height: 110px; border-radius: 12px; }
            .category-hero-banner .cat-banner-title { font-size: 1.2rem; }
            .category-explore-links { gap: 0.5rem; }
            .cat-explore-link { padding: 0.45rem 0.9rem; font-size: 0.75rem; }
            .subcategory-chips { gap: 0.4rem; }
            .subcategory-chip { padding: 0.35rem 0.75rem; font-size: 0.72rem; }
        }

        @media (max-width: 480px) {
            .category-hero-banner { height: 90px; border-radius: 10px; }
            .category-hero-banner .cat-banner-title { font-size: 1rem; }
            .category-explore { padding: 0 1rem; }
        }

        /* Responsive hero */
        @media (max-width: 1024px) {
            .hero-banner {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .hero-banner {
                height: 280px;
            }
            .hero-banner-arrow {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }
            .hero-banner-arrow.prev { left: 0.5rem; }
            .hero-banner-arrow.next { right: 0.5rem; }
            .hero-banner-text h2 { font-size: 1.2rem; }
            .hero-banner-text p { font-size: 0.8rem; }
            .hero-banner-cta { padding: 0.5rem 1.2rem; font-size: 0.78rem; }
            .hero-banner-controls { bottom: 0.6rem; }
            .hero-banner-dot { width: 7px; height: 7px; }
            .hero-banner-dot.active { width: 20px; }
            .top-search-bar { top: 52px; padding: 0.4rem 0; }
            .top-search-bar .search-input { font-size: 16px !important; padding: 0.55rem 1rem 0.55rem 2.2rem; }
        }

        @media (max-width: 480px) {
            .hero-banner {
                height: 220px;
            }
            .hero-banner-overlay {
                padding: 0 0.75rem;
            }
            .hero-banner-text h2 { font-size: 1rem; }
            .hero-label { font-size: 0.6rem; }
            .hero-banner-cta { padding: 0.4rem 1rem; font-size: 0.72rem; }
            .top-search-inner { padding: 0 0.75rem; }
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
            max-width: 1280px;
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

        /* ─── Search Autocomplete Dropdown ─── */
        .search-wrapper {
            position: relative;
        }
        .search-suggestions {
            position: absolute;
            top: calc(100% + 4px);
            left: 0; right: 0;
            background: var(--surface);
            border: 1.5px solid var(--accent);
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            max-height: 380px;
            overflow-y: auto;
            display: none;
        }
        .search-suggestions.open { display: block; }
        .sugg-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid var(--border-light);
            transition: background 0.15s;
        }
        .sugg-item:last-child { border-bottom: none; }
        .sugg-item:hover, .sugg-item.focused { background: var(--surface-hover); }
        .sugg-img {
            width: 44px; height: 44px;
            border-radius: 8px;
            object-fit: contain;
            background: var(--surface-alt);
            flex-shrink: 0;
            border: 1px solid var(--border);
        }
        .sugg-img-placeholder {
            width: 44px; height: 44px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sugg-text { flex: 1; min-width: 0; }
        .sugg-name { font-weight: 600; font-size: 0.88rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sugg-meta { font-size: 0.75rem; color: var(--text-muted); }
        .sugg-price { font-weight: 700; font-size: 0.85rem; color: var(--accent-dark); white-space: nowrap; }
        .sugg-footer { padding: 0.5rem 1rem; font-size: 0.8rem; color: var(--text-muted); text-align: center; background: var(--surface-alt); border-radius: 0 0 14px 14px; }
        /* ─── Brand / Category Filter Dropdowns ─── */
        .search-filters {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .filter-select {
            padding: 0.4rem 2rem 0.4rem 0.75rem;
            border: 1.5px solid var(--border);
            border-radius: 50px;
            font-size: 0.82rem;
            font-family: inherit;
            background: var(--surface-alt);
            color: var(--text-secondary);
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23999'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.6rem center;
            transition: border-color 0.2s;
        }
        .filter-select:focus { outline: none; border-color: var(--accent); }
        .filter-select.active-filter { border-color: var(--accent); color: var(--accent-dark); background-color: rgba(201,169,110,0.08); }
        [dir="rtl"] .filter-select {
            padding: 0.4rem 0.75rem 0.4rem 2rem;
            background-position: left 0.6rem center;
        }
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
            border-color: #000;
            color: #000;
            background: rgba(0,0,0,0.02);
        }

        .cat-chip.active {
            background: #000;
            color: #fff;
            border-color: #000;
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
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .cat-chip i { font-size: 0.8rem; }

        /* ============ REWARDS BANNER ============ */
        .rewards-strip {
            background: var(--surface);
            padding: 0.85rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .rewards-inner {
            max-width: 1280px;
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
            color: var(--text-primary);
        }

        .rewards-icon { font-size: 1.3rem; color: var(--accent); }

        .rewards-text { font-size: 0.88rem; }
        .rewards-text strong { color: var(--accent-dark); font-size: 1rem; }

        .rewards-btn {
            background: #000;
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.82rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .rewards-btn:hover {
            background: #222;
            color: #fff;
            transform: translateY(-1px);
        }

        /* ============ PRODUCTS SECTION ============ */
        .products-section {
            max-width: 1280px;
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
            border: 1px solid var(--border);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .p-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            border-color: rgba(0,0,0,0.12);
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

        .p-card:hover .p-card-img img {
            transform: scale(1.08);
        }

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
            letter-spacing: 0.3px;
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

        .btn-cart:hover {
            background: #222;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
            border-color: #000;
            color: #000;
            background: rgba(0,0,0,0.03);
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
            background: #000;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .empty-state a:hover {
            background: #222;
            transform: translateY(-1px);
        }

        /* ============ FOOTER ============ */
        .site-footer {
            background: #111;
            color: #f5f5f5;
            margin-top: 2rem;
        }

        .footer-main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 3rem 1.5rem 1.5rem;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 2rem;
        }

        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.15em;
            margin-bottom: 0.75rem;
            line-height: 1.1;
        }

        .footer-brand small {
            display: block;
            font-family: 'Inter', 'Montserrat', sans-serif;
            font-size: 0.52rem;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--accent);
            margin-top: 0.2rem;
        }

        .footer-desc {
            font-size: 0.88rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.6);
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
            border: 1px solid rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .social-links a:hover {
            background: #fff;
            color: #111;
            border-color: #fff;
        }

        .footer-heading {
            font-weight: 600;
            color: #fff;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li { margin-bottom: 0.5rem; }

        .footer-links a {
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.88rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: #fff;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            text-align: center;
            padding: 1rem 1.5rem;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.4);
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


            
            /* Filter bar */
            .filter-bar {
                padding: 0.75rem 0.75rem 0.5rem;
            }
            .filter-inner {
                gap: 0.5rem;
            }
            .search-row {
                gap: 0.5rem;
            }
            .search-form {
                max-width: 100%;
            }
            .search-input {
                font-size: 16px !important; /* prevent iOS zoom */
                padding: 0.7rem 1rem 0.7rem 2.5rem;
            }
            .search-filters {
                gap: 0.4rem;
                justify-content: flex-start;
            }
            .filter-select {
                flex: 1;
                min-width: 130px;
                font-size: 0.8rem;
                padding: 0.45rem 1.75rem 0.45rem 0.65rem;
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
            
            /* Search autocomplete on mobile */
            .search-suggestions {
                border-radius: 10px;
            }
            .sugg-item {
                padding: 0.6rem 0.75rem;
            }
            .sugg-img {
                width: 38px; height: 38px;
            }
            .sugg-img-placeholder {
                width: 38px; height: 38px;
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


        }

        /* ============ UTILITIES ============ */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: opacity, transform;
        }

        .fade-in.is-visible {
            opacity: 1;
            transform: translateY(0);
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

        /* Out of stock card styling */
        .out-of-stock-card .p-card-img img { opacity: 0.5; filter: grayscale(40%); }
        .out-of-stock-card .p-card-body { opacity: 0.75; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/home_navbar.php'; ?>

    <!-- ======== ANNOUNCEMENT BAR ======== -->
    <div id="promo-tape" class="announcement-bar">
        <i class="fas fa-truck me-1"></i>
        <?= $lang === 'ar' ? 'شحن مجاني للطلبات فوق 35 دينار | استخدم كود <strong>WELCOME</strong> لخصم على طلبك الأول' : 'Free Shipping on Orders Over 35 JOD | Use code <strong>WELCOME</strong> for a discount on your first order' ?>
        <button class="announcement-close" onclick="document.getElementById('promo-tape').style.display='none'">&times;</button>
    </div>

    <!-- ======== SEARCH BAR (above hero) ======== -->
    <div class="top-search-bar">
        <div class="top-search-inner">
            <form method="GET" action="index.php" class="top-search-form" id="topSearchForm">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" id="searchInput" class="search-input" 
                           placeholder="<?= t('search_products') ?>" 
                           value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
                <input type="hidden" name="brand" id="searchBrandHidden" value="<?= $active_brand ?>">
                <input type="hidden" name="category" id="searchCategoryHidden" value="<?= $active_category ?>">
            </form>
        </div>
    </div>

    <!-- ======== HERO BANNER SLIDER ======== -->
    <?php
    // Build slides array: from DB hero banners, or fallback defaults
    $slides = [];
    if (!empty($hero_banners)) {
        foreach ($hero_banners as $hb) {
            $slides[] = [
                'image'       => $hb['image_path'],
                'title'       => $lang === 'ar' && !empty($hb['title_ar']) ? $hb['title_ar'] : ($hb['title'] ?? ''),
                'subtitle'    => $lang === 'ar' && !empty($hb['subtitle_ar']) ? $hb['subtitle_ar'] : ($hb['subtitle'] ?? ''),
                'cta_text'    => $lang === 'ar' && !empty($hb['cta_text_ar']) ? $hb['cta_text_ar'] : ($hb['cta_text'] ?? ''),
                'link'        => $hb['link_url'] ?? '#products',
            ];
        }
    } else {
        // Default hero slides when no DB banners uploaded yet
        $slides = [
            [
                'image'    => 'images/hero-beauty-1.png',
                'title'    => $lang === 'ar' ? 'اكتشفي مجموعتنا الجديدة' : 'The New Glow Collection',
                'subtitle' => $lang === 'ar' ? 'منتجات فاخرة تمنح بشرتك إشراقة ناعمة' : 'Luxury products for a refined, radiant glow',
                'cta_text' => $lang === 'ar' ? 'تسوقي الآن' : 'Shop Now',
                'link'     => '#products',
            ],
            [
                'image'    => 'images/hero-beauty-2.png',
                'title'    => $lang === 'ar' ? 'روتين متكامل لبشرة مشرقة' : 'Glow. Lift. Renew.',
                'subtitle' => $lang === 'ar' ? 'روتين شامل لبشرة أكثر شباباً وتألقاً' : 'A complete routine for younger-looking, luminous skin',
                'cta_text' => $lang === 'ar' ? 'اكتشفي الآن' : 'Discover Now',
                'link'     => '#products',
            ],
        ];
    }
    $slide_count = count($slides);
    ?>
    <section class="hero-banner" id="heroBanner">
        <div class="hero-banner-track" id="heroBannerTrack">
            <?php foreach ($slides as $i => $slide): ?>
            <div class="hero-banner-slide">
                <img src="<?= htmlspecialchars($slide['image']) ?>"
                     alt="<?= htmlspecialchars($slide['title'] ?: 'Poshy Store Banner') ?>"
                     <?= $i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>>
                <?php if (!empty($slide['title'])): ?>
                <div class="hero-banner-overlay">
                    <div class="hero-banner-text">
                        <?php if (!empty($slide['subtitle'])): ?>
                        <span class="hero-label"><?= htmlspecialchars($slide['subtitle']) ?></span>
                        <?php endif; ?>
                        <h2><?= htmlspecialchars($slide['title']) ?></h2>
                        <?php if (!empty($slide['cta_text'])): ?>
                        <a href="<?= htmlspecialchars($slide['link'] ?: '#products') ?>" class="hero-banner-cta">
                            <i class="fas fa-shopping-bag"></i> <?= htmlspecialchars($slide['cta_text']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($slide_count > 1): ?>
        <!-- Navigation Arrows -->
        <button class="hero-banner-arrow prev" onclick="heroPrev()" aria-label="Previous slide">
            <i class="fas fa-chevron-<?= $lang === 'ar' ? 'right' : 'left' ?>"></i>
        </button>
        <button class="hero-banner-arrow next" onclick="heroNext()" aria-label="Next slide">
            <i class="fas fa-chevron-<?= $lang === 'ar' ? 'left' : 'right' ?>"></i>
        </button>

        <!-- Bottom Controls: Dots + Pause -->
        <div class="hero-banner-controls">
            <div class="hero-banner-dots">
                <?php for ($d = 0; $d < $slide_count; $d++): ?>
                <button class="hero-banner-dot <?= $d === 0 ? 'active' : '' ?>"
                        onclick="heroGoTo(<?= $d ?>)"
                        aria-label="Go to slide <?= $d + 1 ?>"></button>
                <?php endfor; ?>
            </div>
            <button class="hero-banner-pause" id="heroPauseBtn" onclick="heroTogglePause()" aria-label="Pause slideshow">
                <i class="fas fa-pause" id="heroPauseIcon"></i>
            </button>
        </div>
        <?php endif; ?>
    </section>

    <!-- ======== CATEGORY STORIES (Instagram Style) ======== -->
    <?php if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && !empty($homepage_categories)): ?>
    <section style="max-width: 1280px; margin: 0 auto; padding: 2rem 1.5rem 0.5rem;">
        <div style="display: flex; justify-content: center; gap: 2.5rem; flex-wrap: wrap;">
            <?php foreach ($homepage_categories as $cat): ?>
            <a href="pages/shop/category.php?id=<?= (int)$cat['id'] ?>" style="text-decoration: none; text-align: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 80px; height: 80px; border-radius: 50%; padding: 3px; background: linear-gradient(135deg, var(--accent), var(--accent-light), var(--rose)); margin: 0 auto;">
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--accent-dark);">
                        <?php
                            $catName = strtolower(trim($cat['name_en'] ?? ''));
                            if (str_contains($catName, 'skin')) echo '<i class="fas fa-spa"></i>';
                            elseif (str_contains($catName, 'hair')) echo '<i class="fas fa-wind"></i>';
                            elseif (str_contains($catName, 'makeup') || str_contains($catName, 'cosmetic')) echo '<i class="fas fa-palette"></i>';
                            else echo '<i class="fas fa-star"></i>';
                        ?>
                    </div>
                </div>
                <p style="margin-top: 0.6rem; font-size: 0.82rem; font-weight: 600; color: var(--text-primary);">
                    <?= htmlspecialchars($lang === 'ar' && !empty($cat['name_ar']) ? $cat['name_ar'] : $cat['name_en']) ?>
                </p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

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

    <!-- ======== FILTERS & CATEGORIES ======== -->
    <div class="filter-bar" id="filterBar">
        <div class="filter-inner">
            <div class="search-row">
                <!-- Hidden form for filter JS (actual search input is above hero) -->
                <form method="GET" action="index.php" id="searchForm" style="display:none">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>">
                    <input type="hidden" name="brand" id="searchBrandHidden" value="<?= $active_brand ?>">
                    <input type="hidden" name="category" id="searchCategoryHidden" value="<?= $active_category ?>">
                </form>
                <!-- Brand & Category filter dropdowns (submit via JS) -->
                <div class="search-filters">
                    <select class="filter-select <?= $active_brand > 0 ? 'active-filter' : '' ?>"
                            id="brandFilter"
                            onchange="applySearchFilter()">
                        <option value="0"><?= $lang === 'ar' ? '🏷 كل الماركات' : '🏷 All Brands' ?></option>
                        <?php foreach ($all_brands as $brand): ?>
                            <option value="<?= (int)$brand['id'] ?>" <?= $active_brand === (int)$brand['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang === 'ar' && !empty($brand['name_ar']) ? $brand['name_ar'] : $brand['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select <?= $active_category > 0 ? 'active-filter' : '' ?>"
                            id="categoryFilter"
                            onchange="applySearchFilter()">
                        <option value="0"><?= $lang === 'ar' ? '📂 كل الفئات' : '📂 All Categories' ?></option>
                        <?php foreach ($homepage_categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $active_category === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang === 'ar' && !empty($cat['name_ar']) ? $cat['name_ar'] : $cat['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($active_brand > 0 || $active_category > 0 || $active_subcategory > 0): ?>
                        <a href="index.php" class="filter-select" style="background:none; border-color:#E53935; color:#E53935; text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem; padding: 0.4rem 0.75rem; font-weight:600;">
                            <i class="fas fa-times"></i> <?= $lang === 'ar' ? 'مسح' : 'Clear' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Categories -->
            <?php if (!$is_search_mode && !$is_tag_mode && !$is_brand_mode && !empty($homepage_categories)): ?>
            <div class="category-chips">
                <?php foreach ($homepage_categories as $cat): ?>
                    <a href="index.php?category=<?= (int)$cat['id'] ?>#products"
                       class="cat-chip <?= ($active_category === (int)$cat['id'] && $active_subcategory === 0) ? 'active' : '' ?>">
                        <?php if (!empty($cat['icon'])): ?>
                            <i class="<?= htmlspecialchars($cat['icon']) ?>"></i>
                        <?php else: ?>
                            <i class="fas fa-tag"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($lang === 'ar' && !empty($cat['name_ar']) ? $cat['name_ar'] : $cat['name_en']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======== PRODUCTS SECTION ======== -->
    <?php if ($is_filtered_mode): ?>
    <!-- Filtered/Search View: show products in a single grid -->
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
                <?php elseif ($active_category > 0): ?>
                    <?= htmlspecialchars($active_category_name ?: t('products')) ?>
                <?php elseif ($is_search_mode): ?>
                    <?= t('search_results_for') ?>
                <?php else: ?>
                    <?= $show_all ? t('view_all_products') : t('featured_products') ?>
                <?php endif; ?>
            </h2>
        </div>

        <?php if (empty($products_array)): ?>
            <div class="empty-state fade-in">
                <i class="fas fa-box-open"></i>
                <h3><?= t('no_products_found') ?></h3>
                <p><?= t('try_searching_else') ?></p>
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> <?= t('view_all_products') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products_array as $idx => $product): ?>
                <?php include __DIR__ . '/includes/product_card_partial.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php else: ?>
    <!-- ======== HOMEPAGE: Recommended sections per category ======== -->
    <div id="products">
    <?php foreach ($category_recommended as $sec_idx => $section): ?>
        <?php
            $sec_cat = $section['category'];
            $sec_products = $section['products'];
            $sec_total = $section['total_count'];
            $sec_cat_name = $lang === 'ar' && !empty($sec_cat['name_ar']) ? $sec_cat['name_ar'] : $sec_cat['name_en'];
            $sec_cat_lower = strtolower(trim($sec_cat['name_en'] ?? ''));
            $sec_icon = 'fas fa-star';
            if (str_contains($sec_cat_lower, 'skin')) $sec_icon = 'fas fa-spa';
            elseif (str_contains($sec_cat_lower, 'hair')) $sec_icon = 'fas fa-wind';
            elseif (str_contains($sec_cat_lower, 'makeup') || str_contains($sec_cat_lower, 'cosmetic')) $sec_icon = 'fas fa-palette';
        ?>

        <?php
        // ── Show banner(s) BEFORE this category section (position = sec_idx, displayed before) ──
        $before_key = 'before_' . $sec_idx;
        if (isset($homepage_banners[$before_key]) && !empty($homepage_banners[$before_key])): ?>
            <div class="section-banners fade-in">
                <?php foreach ($homepage_banners[$before_key] as $banner): ?>
                <?php if (!empty($banner['link_url'])): ?>
                    <a href="<?= htmlspecialchars($banner['link_url']) ?>" class="section-banner-card">
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy">
                    </a>
                <?php else: ?>
                    <div class="section-banner-card">
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy">
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="products-section" style="padding-bottom: 1rem;">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="<?= $sec_icon ?>" style="color: var(--accent);"></i>
                    <?= $lang === 'ar' ? 'مختارات ' . htmlspecialchars($sec_cat_name) : htmlspecialchars($sec_cat_name) . ' Picks' ?>
                </h2>
                <a href="pages/shop/category.php?id=<?= (int)$sec_cat['id'] ?>" class="view-all-link">
                    <?= $lang === 'ar' ? 'عرض الكل' : 'View All' ?> (<?= $sec_total ?>)
                    <i class="fas fa-arrow-<?= $lang === 'ar' ? 'left' : 'right' ?>"></i>
                </a>
            </div>

            <!-- Category Explore Bar -->
            <div class="category-explore fade-in">
                <?php if (!empty($sec_cat['image_url'])): ?>
                <div class="category-hero-banner">
                    <img src="<?= htmlspecialchars($sec_cat['image_url']) ?>" alt="<?= htmlspecialchars($sec_cat_name) ?>" loading="lazy">
                    <div class="cat-banner-overlay">
                        <div class="cat-banner-title"><?= htmlspecialchars($sec_cat_name) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="category-explore-links">
                    <a href="pages/shop/category.php?id=<?= (int)$sec_cat['id'] ?>&sort=newest" class="cat-explore-link new-arrivals">
                        <i class="fas fa-sparkles"></i> 
                        <?= $lang === 'ar' ? 'تسوق أحدث المنتجات' : 'Shop ' . htmlspecialchars($sec_cat['name_en']) . ' New Arrivals' ?>
                    </a>
                </div>

                <?php if (!empty($sec_cat['subcategories'])): ?>
                <div class="subcategory-chips">
                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; margin-right: 0.5rem;">
                        <?= $lang === 'ar' ? 'تسوق حسب الفئة:' : 'Shop by Category:' ?>
                    </span>
                    <?php foreach ($sec_cat['subcategories'] as $sub): ?>
                        <a href="pages/shop/category.php?id=<?= (int)$sec_cat['id'] ?>&subcategory=<?= (int)$sub['id'] ?>" class="subcategory-chip">
                            <i class="<?= $sub['icon'] ?: 'fas fa-tag' ?>"></i>
                            <?= $lang === 'ar' ? htmlspecialchars($sub['name_ar'] ?: $sub['name_en']) : htmlspecialchars($sub['name_en']) ?>
                            <span class="chip-count">(<?= $sub['product_count'] ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($sec_products)): ?>
            <div class="product-grid">
                <?php foreach ($sec_products as $idx => $product): ?>
                <?php include __DIR__ . '/includes/product_card_partial.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state fade-in" style="padding: 2rem;">
                <i class="fas fa-box-open"></i>
                <h3><?= $lang === 'ar' ? 'لا توجد منتجات بعد' : 'No products yet' ?></h3>
            </div>
            <?php endif; ?>
        </section>

        <?php
        // ── Show banner(s) AFTER this category section (compact stacked) ──
        if (isset($homepage_banners[$sec_idx])): ?>
            <div class="section-banners fade-in">
                <?php foreach ($homepage_banners[$sec_idx] as $banner): ?>
                <?php if (!empty($banner['link_url'])): ?>
                    <a href="<?= htmlspecialchars($banner['link_url']) ?>" class="section-banner-card">
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy">
                    </a>
                <?php else: ?>
                    <div class="section-banner-card">
                        <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy">
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ======== FOOTER ======== -->
    <?php require_once __DIR__ . '/includes/home_footer.php'; ?>

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
                if (product.stock_quantity <= 0) {
                    actionsHtml = '<button class="btn-cart" disabled style="opacity:0.6;cursor:not-allowed;background:#999;">' +
                        '<i class="fas fa-ban"></i><span>' + (CURRENT_LANG === 'ar' ? 'نفذت الكمية' : 'Out of Stock') + '</span></button>';
                } else {
                    actionsHtml = '<button class="btn-cart" onclick="addToCart(' + product.id + ', this)">' +
                        '<i class="fas fa-cart-plus"></i><span>' + ADD_TO_CART_TEXT + '</span></button>';
                }
                actionsHtml += '<a href="' + product.slug + '" class="btn-view" title="' + DETAILS_TEXT + '">' +
                    '<i class="fas fa-eye"></i></a>';
                
                cardsHtml += '<div class="p-card fade-in" style="animation-delay: ' + (idx * 0.05) + 's;">' +
                    '<div class="p-card-img">' + discountTag + catTag +
                    '<a href="' + product.slug + '"><img src="' + product.image_src + '" alt="' + product.name_en + '" loading="lazy" ' +
                    'onerror="this.onerror=null; this.src=\'images/placeholder-cosmetics.svg\';"></a></div>' +
                    '<div class="p-card-body">' +
                    '<a href="' + product.slug + '" style="text-decoration:none; color:inherit;">' +
                    '<div class="p-card-name">' + (CURRENT_LANG === 'ar' ? (product.name_ar || product.name_en) : product.name_en) + '</div>' +
                    '<div class="p-card-name-ar">' + (CURRENT_LANG === 'ar' ? product.name_en : product.name_ar) + '</div></a>' +
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

        // Use guest cart API for non-logged-in users, regular API for logged-in
        const apiUrl = IS_LOGGED_IN ? 'api/add_to_cart_api.php' : 'api/guest_cart_api.php';
        const bodyParams = IS_LOGGED_IN 
            ? 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
            : 'action=add&product_id=' + encodeURIComponent(productId) + '&quantity=1';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParams
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
    // Brand / Category Filter (dropdown submit)
    // ==========================================
    function applySearchFilter() {
        const brand    = document.getElementById('brandFilter').value;
        const catSel   = document.getElementById('categoryFilter');
        const catVal   = catSel.value;
        const isSub    = catSel.options[catSel.selectedIndex]?.dataset?.isSub === '1';
        const search   = document.getElementById('searchInput').value.trim();

        let url = 'index.php?';
        if (search)  url += 'search=' + encodeURIComponent(search) + '&';
        if (brand  > 0) url += 'brand='    + brand + '&';
        if (catVal > 0) url += (isSub ? 'subcategory=' : 'category=') + catVal + '&';
        window.location.href = url.replace(/&$/, '');
    }

    // ==========================================
    // Search Autocomplete
    // ==========================================
    (function() {
        const input   = document.getElementById('searchInput');
        const box     = document.getElementById('searchSuggestions');
        const LANG    = '<?= $lang ?>';
        let timer     = null;
        let focused   = -1;
        let lastQuery = '';

        if (!input || !box) return;

        function showBox(items) {
            if (!items.length) { box.classList.remove('open'); return; }
            focused = -1;
            box.innerHTML = items.map((it, i) => {
                const imgHtml = it.image
                    ? `<img class="sugg-img" src="${escHtml(it.image)}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                      + `<div class="sugg-img-placeholder" style="display:none"><i class="fas fa-box" style="color:white;font-size:0.9rem"></i></div>`
                    : `<div class="sugg-img-placeholder"><i class="fas fa-box" style="color:white;font-size:0.9rem"></i></div>`;
                const meta = [it.brand, it.category].filter(Boolean).join(' · ');
                return `<div class="sugg-item" data-slug="${escHtml(it.slug)}" data-name="${escHtml(it.name_en)}" onclick="pickSugg(this)">
                    ${imgHtml}
                    <div class="sugg-text">
                        <div class="sugg-name">${escHtml(it.name)}</div>
                        ${meta ? `<div class="sugg-meta">${escHtml(meta)}</div>` : ''}
                    </div>
                    <div class="sugg-price">${escHtml(it.price)}</div>
                </div>`;
            }).join('') + `<div class="sugg-footer"><?= $lang === 'ar' ? 'اضغط Enter للبحث الكامل' : 'Press Enter for full results' ?></div>`;
            box.classList.add('open');
        }

        function closeBox() { box.classList.remove('open'); focused = -1; }

        window.pickSugg = function(el) {
            const slug = el.dataset.slug;
            if (slug) { window.location.href = slug; }
            else { input.value = el.dataset.name; document.getElementById('searchForm').submit(); }
        };

        function escHtml(s) {
            if (!s) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function fetchSuggestions(q) {
            if (q.length < 1) { closeBox(); return; }
            if (q === lastQuery) return;
            lastQuery = q;
            fetch(`api/search_suggestions.php?q=${encodeURIComponent(q)}&lang=${LANG}&limit=8`)
                .then(r => r.json())
                .then(items => { if (input.value.trim() === q) showBox(items); })
                .catch(() => {});
        }

        input.addEventListener('input', function() {
            clearTimeout(timer);
            const q = this.value.trim();
            if (!q) { closeBox(); lastQuery = ''; return; }
            timer = setTimeout(() => fetchSuggestions(q), 220);
        });

        input.addEventListener('keydown', function(e) {
            const items = box.querySelectorAll('.sugg-item');
            if (!box.classList.contains('open')) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (focused < items.length - 1) { focused++; }
                items.forEach((el,i) => el.classList.toggle('focused', i === focused));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (focused > 0) { focused--; }
                items.forEach((el,i) => el.classList.toggle('focused', i === focused));
            } else if (e.key === 'Enter' && focused >= 0) {
                e.preventDefault();
                pickSugg(items[focused]);
            } else if (e.key === 'Escape') {
                closeBox();
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-wrapper')) closeBox();
        });
    })();

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
    // ==========================================
    // Hero Banner Slider (horizontal slide) - FIXED
    // ==========================================
    (function() {
        const track = document.getElementById('heroBannerTrack');
        const slides = document.querySelectorAll('.hero-banner-slide');
        const dots = document.querySelectorAll('.hero-banner-dot');
        const pauseIcon = document.getElementById('heroPauseIcon');
        const slideCount = slides.length;
        let current = 0;
        let autoInterval = null;
        let isPaused = false;

        if (!track || slideCount <= 1) return;

        function goTo(index) {
            if (index < 0) index = slideCount - 1;
            if (index >= slideCount) index = 0;
            current = index;
            // Each slide is 100% of viewport width, move by 100% per slide
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach((d, i) => d.classList.toggle('active', i === current));
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        function startAuto() {
            stopAuto();
            autoInterval = setInterval(next, 5000);
        }

        function stopAuto() {
            if (autoInterval) { clearInterval(autoInterval); autoInterval = null; }
        }

        function togglePause() {
            isPaused = !isPaused;
            if (isPaused) {
                stopAuto();
                if (pauseIcon) { pauseIcon.className = 'fas fa-play'; }
            } else {
                startAuto();
                if (pauseIcon) { pauseIcon.className = 'fas fa-pause'; }
            }
        }

        // Touch / swipe support
        let touchStartX = 0;
        const banner = document.getElementById('heroBanner');

        if (banner) {
            banner.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
                stopAuto();
            }, { passive: true });

            banner.addEventListener('touchend', e => {
                const diff = touchStartX - e.changedTouches[0].screenX;
                const isRtl = document.documentElement.dir === 'rtl';
                if (Math.abs(diff) > 50) {
                    if ((diff > 0 && !isRtl) || (diff < 0 && isRtl)) { next(); } else { prev(); }
                }
                if (!isPaused) startAuto();
            }, { passive: true });
        }

        // Expose to global for inline onclick handlers
        window.heroGoTo = function(i) { goTo(i); if (!isPaused) startAuto(); };
        window.heroNext = function() { next(); if (!isPaused) startAuto(); };
        window.heroPrev = function() { prev(); if (!isPaused) startAuto(); };
        window.heroTogglePause = togglePause;

        // Start auto-slide
        startAuto();
    })();
    // ==========================================
    // Intersection Observer for Scroll Animations
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1 // Trigger when 10% of the element is visible
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    // Optional: stop observing once it's visible so it doesn't fade out when scrolling up
                    observer.unobserve(entry.target); 
                }
            });
        }, observerOptions);

        // Find all elements with fade-in and observe them
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach(el => observer.observe(el));
    });
    </script>
</body>
</html>
