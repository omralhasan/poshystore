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

// Homepage categories are dynamic: admin add/remove actions are reflected automatically.
$homepage_categories = [];
foreach ($all_categories as $cat) {
    if (!empty($cat['id'])) {
        $homepage_categories[] = $cat;
    }
}

// Keep recommended sections lightweight while category chips/stories remain fully dynamic.
$homepage_recommended_categories = array_slice($homepage_categories, 0, 6);

// Allow category filter by readable keyword slug (e.g. category=skin-care).
if ($active_category === 0 && $active_category_keyword !== '') {
    foreach ($homepage_categories as $cat) {
        $name_en = strtolower(trim((string)($cat['name_en'] ?? '')));
        $normalized = preg_replace('/[^a-z0-9]+/', '', $name_en);
        if ($normalized === '') {
            continue;
        }

        if (str_contains($active_category_keyword, $normalized) || str_contains($normalized, $active_category_keyword)) {
            $active_category = (int)$cat['id'];
            break;
        }
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
    foreach ($homepage_recommended_categories as $cat) {
        $cat_id = (int)$cat['id'];
        $is_home_subcategory = !empty($cat['is_subcategory_home_slot']);
        $where_filter_sql = $is_home_subcategory ? 'p.subcategory_id = ?' : 's.category_id = ?';
        $fill_where_sql = $is_home_subcategory ? "p.subcategory_id = $cat_id" : "s.category_id = $cat_id";
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
                        WHERE $where_filter_sql AND p.is_recommended = 1
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
                             WHERE $fill_where_sql AND p.id NOT IN ($exclude)
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
            $count_sql = $is_home_subcategory
                ? "SELECT COUNT(*) as cnt FROM products p WHERE p.subcategory_id = ?"
                : "SELECT COUNT(*) as cnt FROM products p JOIN subcategories s ON p.subcategory_id = s.id WHERE s.category_id = ?";
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

if (!function_exists('normalize_banner_link')) {
    function normalize_banner_link(string $link): string
    {
        $link = trim($link);

        if ($link === '') {
            return '#products';
        }

        // Never allow script protocols in banner links.
        if (preg_match('/^(?:javascript|data|vbscript):/i', $link)) {
            return '#products';
        }

        if ($link[0] === '#' || $link[0] === '/' || $link[0] === '?') {
            return $link;
        }

        if (str_starts_with($link, './') || str_starts_with($link, '../')) {
            return $link;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $link) || str_starts_with($link, '//')) {
            return $link;
        }

        if (str_starts_with(strtolower($link), 'www.')) {
            return 'https://' . $link;
        }

        // Accept bare domains such as instagram.com/page and normalize them.
        if (preg_match('/^[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(?:[\/:?#].*)?$/i', $link)) {
            return 'https://' . $link;
        }

        return $link;
    }
}

if (!function_exists('is_external_banner_link')) {
    function is_external_banner_link(string $link): bool
    {
        return (bool)preg_match('/^(https?:)?\/\//i', $link);
    }
}

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
    
        <link rel="stylesheet" href="assets/css/home.min.css">
    <style>
        @media (min-width: 1025px) {
            .hero-banner,
            .hero-banner-track,
            .hero-banner-slide {
                height: auto;
            }

            .hero-banner-slide img {
                width: 100%;
                height: auto;
                object-fit: contain;
                background: transparent;
            }

            .section-banner-card img {
                height: auto;
                object-fit: contain;
                background: transparent;
            }
        }

        .hero-banner-slide-link {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: block;
        }

        .hero-banner-overlay {
            z-index: 2;
            pointer-events: none;
        }

        .hero-banner-cta {
            pointer-events: auto;
            position: relative;
            z-index: 3;
        }
    </style>
    <?php if (!empty($slides) && !empty($slides[0]['image'])): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars(prefer_webp_relative_path((string)($slides[0]['image'] ?? ''), ROOT_DIR)) ?>">
    <?php endif; ?>
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
    <section class="hero-banner" id="heroBanner">
        <div class="hero-banner-track" id="heroBannerTrack">
              <?php foreach ($slides as $i => $slide): ?>
              <?php
                 $slide_link = normalize_banner_link((string)($slide['link'] ?? ''));
                 $slide_is_external = is_external_banner_link($slide_link);
                  $slide_label = trim((string)($slide['title'] ?? ''));
                  if ($slide_label === '') {
                     $slide_label = $lang === 'ar' ? 'فتح رابط البانر' : 'Open banner link';
                  }
              ?>
              <div class="hero-banner-slide"
                   data-banner-link="<?= htmlspecialchars($slide_link) ?>"
                  style="cursor:pointer;">
                 <a href="<?= htmlspecialchars($slide_link) ?>"
                    class="hero-banner-slide-link"
                    aria-label="<?= htmlspecialchars($slide_label) ?>"
                    <?= $slide_is_external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>></a>
                <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($slide['image'] ?? ''), ROOT_DIR)) ?>"
                     alt="<?= htmlspecialchars($slide['title'] ?: 'Poshy Store Banner') ?>"
                     <?= $i === 0 ? 'loading="eager" fetchpriority="high" decoding="async"' : 'loading="lazy" fetchpriority="low" decoding="async"' ?>>
                <?php if (!empty($slide['title'])): ?>
                <div class="hero-banner-overlay">
                    <div class="hero-banner-text">
                        <?php if (!empty($slide['subtitle'])): ?>
                        <span class="hero-label"><?= htmlspecialchars($slide['subtitle']) ?></span>
                        <?php endif; ?>
                        <h2><?= htmlspecialchars($slide['title']) ?></h2>
                        <?php if (!empty($slide['cta_text'])): ?>
                        <a href="<?= htmlspecialchars($slide_link) ?>"
                           class="hero-banner-cta"
                           <?= $slide_is_external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
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
            <?php
                $cat_target_id = !empty($cat['is_subcategory_home_slot']) ? (int)($cat['parent_category_id'] ?? 0) : (int)$cat['id'];
                $cat_sub_filter = !empty($cat['is_subcategory_home_slot']) ? '&subcategory=' . (int)$cat['id'] : '';
            ?>
            <a href="pages/shop/category.php?id=<?= $cat_target_id ?><?= $cat_sub_filter ?>" style="text-decoration: none; text-align: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 80px; height: 80px; border-radius: 50%; padding: 3px; background: linear-gradient(135deg, var(--accent), var(--accent-light), var(--rose)); margin: 0 auto;">
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--accent-dark); overflow: hidden;">
                        <?php if (!empty($cat['image_url'])): ?>
                            <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($cat['image_url'] ?? ''), ROOT_DIR)) ?>"
                                 alt="<?= htmlspecialchars($lang === 'ar' && !empty($cat['name_ar']) ? $cat['name_ar'] : $cat['name_en']) ?>"
                                 loading="lazy"
                                 decoding="async"
                                 fetchpriority="low"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <?php
                                $catName = strtolower(trim($cat['name_en'] ?? ''));
                                if (!empty($cat['icon'])) echo '<i class="' . htmlspecialchars($cat['icon']) . '"></i>';
                                elseif (str_contains($catName, 'skin')) echo '<i class="fas fa-spa"></i>';
                                elseif (str_contains($catName, 'hair')) echo '<i class="fas fa-wind"></i>';
                                elseif (str_contains($catName, 'makeup') || str_contains($catName, 'cosmetic')) echo '<i class="fas fa-palette"></i>';
                                elseif (str_contains($catName, 'lip') || str_contains($catName, 'balm')) echo '<i class="fas fa-heart"></i>';
                                elseif (str_contains($catName, 'dental') || str_contains($catName, 'oral') || str_contains($catName, 'tooth')) echo '<i class="fas fa-tooth"></i>';
                                elseif (str_contains($catName, 'body')) echo '<i class="fas fa-shower"></i>';
                                else echo '<i class="fas fa-star"></i>';
                            ?>
                        <?php endif; ?>
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
                            <?php
                                $is_sub_option = !empty($cat['is_subcategory_home_slot']);
                                $is_selected = $is_sub_option
                                    ? ($active_subcategory === (int)$cat['id'])
                                    : ($active_category === (int)$cat['id']);
                            ?>
                            <option value="<?= (int)$cat['id'] ?>" data-is-sub="<?= $is_sub_option ? '1' : '0' ?>" <?= $is_selected ? 'selected' : '' ?>>
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
                    <?php
                        $chip_is_sub = !empty($cat['is_subcategory_home_slot']);
                        $chip_href = $chip_is_sub
                            ? 'index.php?subcategory=' . (int)$cat['id'] . '#products'
                            : 'index.php?category=' . (int)$cat['id'] . '#products';
                        $chip_active = $chip_is_sub
                            ? ($active_subcategory === (int)$cat['id'])
                            : ($active_category === (int)$cat['id'] && $active_subcategory === 0);
                    ?>
                    <a href="<?= $chip_href ?>"
                       class="cat-chip <?= $chip_active ? 'active' : '' ?>">
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
            $sec_target_category_id = !empty($sec_cat['is_subcategory_home_slot']) ? (int)($sec_cat['parent_category_id'] ?? 0) : (int)$sec_cat['id'];
            $sec_sub_filter = !empty($sec_cat['is_subcategory_home_slot']) ? '&subcategory=' . (int)$sec_cat['id'] : '';
            $sec_icon = 'fas fa-star';
            if (str_contains($sec_cat_lower, 'skin')) $sec_icon = 'fas fa-spa';
            elseif (str_contains($sec_cat_lower, 'hair')) $sec_icon = 'fas fa-wind';
            elseif (str_contains($sec_cat_lower, 'makeup') || str_contains($sec_cat_lower, 'cosmetic')) $sec_icon = 'fas fa-palette';
            elseif (str_contains($sec_cat_lower, 'lip') || str_contains($sec_cat_lower, 'balm')) $sec_icon = 'fas fa-heart';
            elseif (str_contains($sec_cat_lower, 'dental') || str_contains($sec_cat_lower, 'oral') || str_contains($sec_cat_lower, 'tooth')) $sec_icon = 'fas fa-tooth';
            elseif (str_contains($sec_cat_lower, 'body')) $sec_icon = 'fas fa-shower';
        ?>

        <?php
        // ── Show banner(s) BEFORE this category section (position = sec_idx, displayed before) ──
        $before_key = 'before_' . $sec_idx;
        if (isset($homepage_banners[$before_key]) && !empty($homepage_banners[$before_key])): ?>
            <div class="section-banners fade-in">
                <?php foreach ($homepage_banners[$before_key] as $banner): ?>
                <?php
                    $banner_link = normalize_banner_link((string)($banner['link_url'] ?? ''));
                    $banner_is_external = is_external_banner_link($banner_link);
                ?>
                <?php if (!empty($banner['link_url'])): ?>
                    <a href="<?= htmlspecialchars($banner_link) ?>" class="section-banner-card" <?= $banner_is_external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($banner['image_path'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy" decoding="async" fetchpriority="low">
                    </a>
                <?php else: ?>
                    <div class="section-banner-card">
                        <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($banner['image_path'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy" decoding="async" fetchpriority="low">
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
                <a href="pages/shop/category.php?id=<?= $sec_target_category_id ?><?= $sec_sub_filter ?>" class="view-all-link">
                    <?= $lang === 'ar' ? 'عرض الكل' : 'View All' ?> (<?= $sec_total ?>)
                    <i class="fas fa-arrow-<?= $lang === 'ar' ? 'left' : 'right' ?>"></i>
                </a>
            </div>

            <!-- Category Explore Bar -->
            <div class="category-explore fade-in">
                <?php if (!empty($sec_cat['image_url'])): ?>
                <div class="category-hero-banner">
                    <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($sec_cat['image_url'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($sec_cat_name) ?>" loading="lazy" decoding="async" fetchpriority="low">
                    <div class="cat-banner-overlay">
                        <div class="cat-banner-title"><?= htmlspecialchars($sec_cat_name) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="category-explore-links">
                    <a href="pages/shop/category.php?id=<?= $sec_target_category_id ?><?= $sec_sub_filter ?>&sort=newest" class="cat-explore-link new-arrivals">
                        <i class="fas fa-sparkles"></i> 
                        <?= $lang === 'ar' ? 'تسوق أحدث المنتجات' : 'Shop ' . htmlspecialchars($sec_cat['name_en']) . ' New Arrivals' ?>
                    </a>
                </div>

                <?php if (!empty($sec_cat['subcategories'])): ?>
                <div class="subcategory-chips">
                    <span class="category-label">
                        <?= $lang === 'ar' ? 'تسوق حسب الفئة:' : 'Shop by Category:' ?>
                    </span>
                    <?php foreach ($sec_cat['subcategories'] as $sub): ?>
                        <a href="/pages/shop/category.php?id=<?= $sec_target_category_id ?>&subcategory=<?= (int)$sub['id'] ?>" class="subcategory-chip" title="<?= $lang === 'ar' ? htmlspecialchars($sub['name_ar'] ?: $sub['name_en']) : htmlspecialchars($sub['name_en']) ?>">
                            <div class="chip-icon">
                                <?php if (!empty($sub['image_url'])): ?>
                                    <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($sub['image_url'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($sub['name_en']) ?>" loading="lazy" decoding="async" fetchpriority="low" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                <?php else: ?>
                                    <i class="<?= $sub['icon'] ?: 'fas fa-tag' ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="chip-label"><?= $lang === 'ar' ? htmlspecialchars($sub['name_ar'] ?: $sub['name_en']) : htmlspecialchars($sub['name_en']) ?></div>
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
                <?php
                    $banner_link = normalize_banner_link((string)($banner['link_url'] ?? ''));
                    $banner_is_external = is_external_banner_link($banner_link);
                ?>
                <?php if (!empty($banner['link_url'])): ?>
                    <a href="<?= htmlspecialchars($banner_link) ?>" class="section-banner-card" <?= $banner_is_external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                        <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($banner['image_path'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy" decoding="async" fetchpriority="low">
                    </a>
                <?php else: ?>
                    <div class="section-banner-card">
                        <img src="<?= htmlspecialchars(prefer_webp_relative_path((string)($banner['image_path'] ?? ''), ROOT_DIR)) ?>" alt="<?= htmlspecialchars($banner['title'] ?? '') ?>" loading="lazy" decoding="async" fetchpriority="low">
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
    window.AppConfig = {
        lang: '<?= $lang ?>',
        isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
        currencyText: '<?= addslashes(t("currency")) ?>',
        addToCartText: '<?= addslashes(t("add_to_cart")) ?>',
        loginText: '<?= addslashes(t("login")) ?>',
        detailsText: '<?= addslashes(t("details")) ?>',
        featuredText: '<?= addslashes(t("featured_products")) ?>',
        viewAllText: '<?= addslashes(t("view_all_products")) ?>',
        viewAllLinkText: '<?= addslashes(t("view_all")) ?>',
        noProductsText: '<?= addslashes(t("no_products_found")) ?>',
        trySearchingText: '<?= addslashes(t("try_searching_else")) ?>',
        currentFilter: { subcategory: <?= $active_subcategory ?>, show_all: <?= $show_all ? 'true' : 'false' ?> },
        toastAdded: '<?= $lang === "ar" ? "تمت الإضافة للسلة" : "Added to cart!" ?>',
        searchFooter: '<?= $lang === "ar" ? "اضغط Enter للبحث الكامل" : "Press Enter for full results" ?>'
    };
    </script>
    <script src="assets/js/home.min.js" defer></script>
</body>
</html>
