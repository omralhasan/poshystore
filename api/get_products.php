<?php
/**
 * API: Get Products
 * Returns JSON for AJAX category filtering and "View All" on index.php
 *
 * GET params:
 *   subcategory=ID   - Filter by subcategory ID
 *   show_all=1       - Return all products (no limit)
 *   (none)           - Return featured products (limit 8)
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/product_manager.php';
require_once __DIR__ . '/../includes/product_image_helper.php';
require_once __DIR__ . '/../includes/language.php';

// Determine current language
$lang = $_SESSION['language'] ?? 'en';

// Parse request params
$subcategory_id = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : 0;
$show_all       = isset($_GET['show_all']) && $_GET['show_all'] == '1';

// ─── Determine limit ──────────────────────────────────────────────────────────
if ($subcategory_id > 0 || $show_all) {
    $limit = 200; // return up to 200 when filtering or viewing all
} else {
    $limit = 8;   // featured — same as the initial PHP render
}

// ─── Query products ───────────────────────────────────────────────────────────
if ($subcategory_id > 0) {
    $result = getAllProducts(['subcategory_id' => $subcategory_id, 'in_stock' => true], $limit);
} else {
    $result = getAllProducts(['in_stock' => true], $limit);
}

if (!$result['success']) {
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'DB error']);
    exit();
}

$raw_products = $result['products'];

// ─── Get total count (all in-stock products) ──────────────────────────────────
$total_result       = getAllProducts(['in_stock' => true], 1000);
$total_products_count = $total_result['count'] ?? count($raw_products);

// ─── Get filter name (subcategory label) ──────────────────────────────────────
$filter_name = '';
if ($subcategory_id > 0) {
    $sub_stmt = $conn->prepare("SELECT name_en, name_ar FROM subcategories WHERE id = ?");
    if ($sub_stmt) {
        $sub_stmt->bind_param('i', $subcategory_id);
        $sub_stmt->execute();
        $sub_row = $sub_stmt->get_result()->fetch_assoc();
        $sub_stmt->close();
        if ($sub_row) {
            $filter_name = $lang === 'ar' ? ($sub_row['name_ar'] ?? $sub_row['name_en']) : $sub_row['name_en'];
        }
    }
}

// ─── Base dir for thumbnail resolution ────────────────────────────────────────
// api/ is one level below project root
$base_dir = __DIR__ . '/..';

// ─── Format products for JS ───────────────────────────────────────────────────
$products = [];
foreach ($raw_products as $p) {
    // Compute thumbnail using helper
    $image_src = get_product_thumbnail(
        trim($p['name_en']),
        $p['image_link'] ?? '',
        $base_dir
    );

    $products[] = [
        'id'                  => (int)$p['id'],
        'name_en'             => $p['name_en'],
        'name_ar'             => $p['name_ar'] ?? '',
        'slug'                => $p['slug'] ?? ('#'),
        'price_jod'           => number_format((float)$p['price_jod'], 3),
        'original_price'      => !empty($p['original_price']) ? number_format((float)$p['original_price'], 3) : '',
        'has_discount'        => !empty($p['has_discount']) && (float)$p['discount_percentage'] > 0,
        'discount_percentage' => (int)round($p['discount_percentage'] ?? 0),
        'subcategory_en'      => $p['subcategory_en'] ?? '',
        'subcategory_ar'      => $p['subcategory_ar'] ?? '',
        'image_src'           => $image_src,
        'stock_quantity'      => (int)($p['stock_quantity'] ?? 0),
    ];
}

echo json_encode([
    'success'      => true,
    'products'     => $products,
    'total_count'  => (int)$total_products_count,
    'filter_name'  => $filter_name,
    'show_all'     => $show_all,
    'subcategory'  => $subcategory_id,
    'lang'         => $lang,
]);
