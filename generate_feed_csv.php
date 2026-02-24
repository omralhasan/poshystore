<?php
/**
 * Product Feed CSV Generator
 * Google Merchant Center & Meta Commerce Manager
 *
 * Generates a UTF-8 CSV file (with BOM for Excel Arabic support).
 * Overwrites the file on every run — safe to call from cron.
 *
 * Output file : /var/www/html/feeds/products.csv
 * Public URL  : http://159.223.180.154/feeds/products.csv
 *
 * Cron (every hour):
 *   0 * * * * php /var/www/html/generate_feed_csv.php >> /var/log/feed_csv.log 2>&1
 *
 * Manual run:
 *   php /var/www/html/generate_feed_csv.php
 */

// ─── Bootstrap ────────────────────────────────────────────────────────────────
define('CLI_RUN', php_sapi_name() === 'cli');

// Allow running from any directory
$root = rtrim(realpath(__DIR__), '/');
require_once $root . '/config.php';
require_once $root . '/includes/db_connect.php';

// ─── Configuration ────────────────────────────────────────────────────────────
$CURRENCY       = 'JOD';     // Change to 'SAR' if needed (see conversion below)
$SAR_RATE       = 5.15;      // JOD → SAR conversion (update as needed)
$LANG           = 'en';      // 'en' for English titles, 'ar' for Arabic
$OUTPUT_DIR     = $root . '/feeds';
$OUTPUT_FILE    = $OUTPUT_DIR . '/products.csv';
$LOG_PREFIX     = '[' . date('Y-m-d H:i:s') . '] Feed CSV: ';

// ─── Ensure output directory exists ──────────────────────────────────────────
if (!is_dir($OUTPUT_DIR)) {
    if (!mkdir($OUTPUT_DIR, 0755, true)) {
        log_msg("ERROR – Could not create directory: $OUTPUT_DIR");
        exit(1);
    }
    log_msg("Created directory: $OUTPUT_DIR");
}

// ─── Fetch products ───────────────────────────────────────────────────────────
$sql = "SELECT
            p.id,
            p.name_en,
            p.name_ar,
            p.slug,
            p.short_description_en,
            p.short_description_ar,
            p.description,
            p.description_ar,
            p.price_jod,
            p.original_price,
            p.has_discount,
            p.stock_quantity,
            p.image_link,
            COALESCE(b.name_en, 'Poshy Lifestyle') AS brand_en,
            COALESCE(b.name_ar, 'بوشي لايف ستايل')  AS brand_ar,
            COALESCE(c.name_en, 'Health & Beauty')   AS category_en
        FROM products p
        LEFT JOIN brands        b ON p.brand_id      = b.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        LEFT JOIN categories    c ON s.category_id    = c.id
        WHERE p.stock_quantity >= 0
        ORDER BY p.id ASC";

$result = $conn->query($sql);
if (!$result) {
    log_msg("ERROR – Query failed: " . $conn->error);
    exit(1);
}

// ─── Open file for writing (overwrite) ───────────────────────────────────────
$fh = fopen($OUTPUT_FILE, 'w');
if (!$fh) {
    log_msg("ERROR – Cannot open file for writing: $OUTPUT_FILE");
    exit(1);
}

// Write UTF-8 BOM so Excel opens Arabic text correctly
fwrite($fh, "\xEF\xBB\xBF");

// ─── CSV headers (Google / Meta standard attributes) ─────────────────────────
$headers = [
    'id',
    'title',
    'description',
    'link',
    'image_link',
    'availability',
    'price',
    'sale_price',
    'brand',
    'condition',
    'google_product_category',
];
fputcsv($fh, $headers);

// ─── Write rows ───────────────────────────────────────────────────────────────
$site_url = rtrim(SITE_URL, '/');
$count    = 0;

while ($p = $result->fetch_assoc()) {

    // title
    $title = ($LANG === 'ar' && !empty($p['name_ar']))
        ? $p['name_ar']
        : $p['name_en'];

    // description (prefer short → full, strip HTML)
    if ($LANG === 'ar') {
        $desc = !empty($p['short_description_ar'])
            ? $p['short_description_ar']
            : (!empty($p['description_ar']) ? $p['description_ar'] : $p['description']);
    } else {
        $desc = !empty($p['short_description_en'])
            ? $p['short_description_en']
            : $p['description'];
    }
    $desc = strip_tags((string)$desc);
    $desc = mb_substr($desc, 0, 5000, 'UTF-8');   // Google max length

    // product URL
    $link = !empty($p['slug'])
        ? $site_url . '/' . $p['slug']
        : $site_url . '/product.php?id=' . (int)$p['id'];

    // image URL (absolute)
    $image_link = '';
    if (!empty($p['image_link'])) {
        $img = trim($p['image_link']);
        $image_link = preg_match('#^https?://#i', $img)
            ? $img
            : $site_url . '/' . ltrim($img, '/');
    }

    // availability
    $availability = ((int)$p['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock';

    // price & sale_price
    $price_jod  = (float)$p['price_jod'];
    $orig_jod   = (float)$p['original_price'];
    $has_disc   = !empty($p['has_discount']) && $orig_jod > $price_jod;

    if ($CURRENCY === 'SAR') {
        $sale_val  = round($price_jod * $SAR_RATE, 2);
        $full_val  = $has_disc ? round($orig_jod * $SAR_RATE, 2) : $sale_val;
        $price_str = number_format($has_disc ? $full_val : $sale_val, 2, '.', '') . ' SAR';
        $sale_str  = $has_disc ? number_format($sale_val, 2, '.', '') . ' SAR' : '';
    } else {
        $price_str = number_format($has_disc ? $orig_jod : $price_jod, 3, '.', '') . ' JOD';
        $sale_str  = $has_disc ? number_format($price_jod, 3, '.', '') . ' JOD' : '';
    }

    // brand
    $brand = ($LANG === 'ar' && !empty($p['brand_ar']))
        ? $p['brand_ar']
        : $p['brand_en'];

    // google_product_category (best-effort)
    $cat_lower = strtolower($p['category_en']);
    if (str_contains($cat_lower, 'skin') || str_contains($cat_lower, 'care')) {
        $gpc = 'Health & Beauty > Personal Care > Cosmetics > Skin Care';
    } elseif (str_contains($cat_lower, 'hair')) {
        $gpc = 'Health & Beauty > Personal Care > Hair Care';
    } elseif (str_contains($cat_lower, 'makeup') || str_contains($cat_lower, 'cosmetic')) {
        $gpc = 'Health & Beauty > Personal Care > Cosmetics';
    } elseif (str_contains($cat_lower, 'fragrance') || str_contains($cat_lower, 'perfume')) {
        $gpc = 'Health & Beauty > Personal Care > Cosmetics > Fragrance';
    } else {
        $gpc = 'Health & Beauty';
    }

    fputcsv($fh, [
        (int)$p['id'],
        $title,
        $desc,
        $link,
        $image_link,
        $availability,
        $price_str,
        $sale_str,
        $brand,
        'new',
        $gpc,
    ]);

    $count++;
}

fclose($fh);
$conn->close();

// ─── Summary ──────────────────────────────────────────────────────────────────
$size = round(filesize($OUTPUT_FILE) / 1024, 1);
log_msg("OK – Wrote $count products ($size KB) → $OUTPUT_FILE");
log_msg("URL: $site_url/feeds/products.csv");

// ─── Helper ───────────────────────────────────────────────────────────────────
function log_msg(string $msg): void {
    global $LOG_PREFIX;
    echo $LOG_PREFIX . $msg . PHP_EOL;
}
