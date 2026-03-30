<?php
/**
 * Product Feed CSV Generator
 * Google Merchant Center & Meta Commerce Manager
 *
 * Generates a UTF-8 CSV file (with BOM for Excel Arabic support).
 * Overwrites the file on every run — safe to call from cron.
 *
 * Output file : /var/www/html/feeds/products.csv
 * Public URL  : https://poshystore.com/feeds/products.csv
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
require_once $root . '/includes/product_image_helper.php';

// ─── Configuration ────────────────────────────────────────────────────────────
$CURRENCY       = 'JOD';        // Jordanian Dinar
$SAR_RATE       = 5.15;         // JOD → SAR conversion rate (unused when JOD)
$LANG           = 'en';         // 'en' or 'ar'
$OUTPUT_DIR     = $root . '/feeds';
$OUTPUT_FILE    = $OUTPUT_DIR . '/products.csv';
$LOG_FILE       = '/var/log/feed_csv.log';

// ─── Start log entry ─────────────────────────────────────────────────────────
log_msg('Start: Generating CSV');

// ─── Ensure output directory exists ──────────────────────────────────────────
if (!is_dir($OUTPUT_DIR)) {
    if (!mkdir($OUTPUT_DIR, 0755, true)) {
        log_msg('Error: Could not create directory: ' . $OUTPUT_DIR);
        exit(1);
    }
    log_msg('Info: Created directory: ' . $OUTPUT_DIR);
}

// ─── Fetch products ───────────────────────────────────────────────────────────
$has_gtin = column_exists($conn, 'products', 'gtin');
$has_mpn  = column_exists($conn, 'products', 'mpn');

$gtin_select = $has_gtin
    ? "COALESCE(NULLIF(TRIM(p.gtin), ''), '') AS gtin,"
    : "'' AS gtin,";

$mpn_select = $has_mpn
    ? "COALESCE(NULLIF(TRIM(p.mpn), ''), '') AS mpn,"
    : "'' AS mpn,";

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
            $gtin_select
            $mpn_select
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
    log_msg('Error: DB query failed – ' . $conn->error);
    exit(1);
}

$first_gallery_images = load_first_gallery_images($conn);
$fallback_relative_image = find_global_fallback_relative_image($root);
// Product-specific stable image overrides for known GMC image-processing edge cases.
$feed_safe_overrides_by_slug = [
    'dr-althea-345-relief-cream' => 'images/feed/dr-althea-345-relief-cream.png',
    'dr-althea-345-relief-cream-duo-pack' => 'images/feed/dr-althea-345-relief-cream-duo-pack.png',
];
if ($fallback_relative_image === '') {
    log_msg('Warn: No supported fallback image found in /images (png/jpg/jpeg/gif).');
}

$total_fetched = $result->num_rows;
log_msg('Info: Fetched ' . $total_fetched . ' products from database');

// ─── Open file for writing (overwrite) ───────────────────────────────────────
$fh = fopen($OUTPUT_FILE, 'w');
if (!$fh) {
    log_msg('Error: Cannot open file for writing: ' . $OUTPUT_FILE);
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
    'gtin',
    'mpn',
    'identifier_exists',
    'condition',
    'google_product_category',
];
fputcsv($fh, $headers);

// ─── Write rows ───────────────────────────────────────────────────────────────
// Use canonical domain for all public URLs in the feed (improves Google/Meta indexing)
$feed_domain = 'https://poshystore.com';  // canonical domain for feed image/product URLs
$count       = 0;
$skipped_missing_image = 0;
$resolved_from_db_image = 0;
$resolved_from_gallery = 0;
$resolved_from_folder_match = 0;
$resolved_from_global_fallback = 0;
$resolved_from_slug_override = 0;

while ($p = $result->fetch_assoc()) {

    // title — fix ALL CAPS (Meta flags this as error / lowers quality score)
    $title = ($LANG === 'ar' && !empty($p['name_ar']))
        ? $p['name_ar']
        : $p['name_en'];
    $title = fix_capitalization($title);

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
    // Fix ALL CAPS descriptions
    $desc = fix_capitalization($desc);
    // Fallback: use product title if description is empty
    if (empty(trim($desc))) {
        $desc = $title;
    }
    $desc = mb_substr($desc, 0, 5000, 'UTF-8');   // Google max length

    // product URL — always absolute with canonical domain
    $link = !empty($p['slug'])
        ? $feed_domain . '/' . rawurlencode($p['slug'])
        : $feed_domain . '/product.php?id=' . (int)$p['id'];
    $link = normalize_feed_url($link);

    // image URL — resolve to an on-disk image that uses accepted types for GMC.
    [$image_relative, $image_source] = resolve_supported_product_image_relative($p, $first_gallery_images, $root);

    $slug_key = trim((string)($p['slug'] ?? ''));
    if ($slug_key !== '' && isset($feed_safe_overrides_by_slug[$slug_key])) {
        $override_relative = ensure_supported_relative_image($feed_safe_overrides_by_slug[$slug_key], $root);
        if ($override_relative !== '') {
            $image_relative = $override_relative;
            $image_source = 'slug_override';
        }
    }

    if ($image_relative === '' && $fallback_relative_image !== '') {
        $image_relative = $fallback_relative_image;
        $image_source = 'global_fallback';
    }

    $image_link = '';
    if ($image_relative !== '') {
        $image_link = normalize_feed_url($feed_domain . '/' . ltrim($image_relative, '/'));
    }

    // Last-resort safeguard: skip only if absolutely no image can be resolved.
    if ($image_link === '') {
        $skipped_missing_image++;
        log_msg('Warn: Skipped product ID ' . (int)$p['id'] . ' (no resolvable image found)');
        continue;
    }

    if ($image_source === 'db_image_link') {
        $resolved_from_db_image++;
    } elseif ($image_source === 'product_images_table') {
        $resolved_from_gallery++;
    } elseif ($image_source === 'folder_match') {
        $resolved_from_folder_match++;
    } elseif ($image_source === 'global_fallback') {
        $resolved_from_global_fallback++;
    } elseif ($image_source === 'slug_override') {
        $resolved_from_slug_override++;
    }

    // Google Merchant required values: in_stock / out_of_stock / preorder / backorder
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
        $price_str = number_format($has_disc ? $orig_jod : $price_jod, 2, '.', '') . ' JOD';
        $sale_str  = $has_disc ? number_format($price_jod, 2, '.', '') . ' JOD' : '';
    }

    // brand — use actual brand from DB, fallback to 'Poshy Store'
    $brand = ($LANG === 'ar' && !empty($p['brand_ar']))
        ? $p['brand_ar']
        : (!empty($p['brand_en']) ? $p['brand_en'] : 'Poshy Store');

    // Product identifiers for Google quality/compliance.
    $gtin_raw = (string)($p['gtin'] ?? '');
    $gtin = clean_gtin($gtin_raw);

    $mpn = trim((string)($p['mpn'] ?? ''));
    if ($mpn !== '') {
        $mpn = mb_substr($mpn, 0, 70, 'UTF-8');
    }

    // If GTIN/MPN are not available, declare no identifiers explicitly.
    $identifier_exists = ($gtin === '' && $mpn === '') ? 'no' : 'yes';

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
        (int)$p['id'],  // Real DB product ID (must match Meta Pixel content_ids)
        $title,
        $desc,
        $link,
        $image_link,
        $availability,
        $price_str,
        $sale_str,
        $brand,
        $gtin,
        $mpn,
        $identifier_exists,
        'new',
        $gpc,
    ]);

    $count++;
}

fclose($fh);
$conn->close();

// ─── Fix permissions so web server (apache) can overwrite next run ────────────
@chmod($OUTPUT_FILE, 0664);
@chown($OUTPUT_FILE, 'apache');

// ─── Success log ─────────────────────────────────────────────────────────────
$size = round(filesize($OUTPUT_FILE) / 1024, 1);
log_msg('Success: ' . $count . ' products written to products.csv (' . $size . ' KB)');
if ($skipped_missing_image > 0) {
    log_msg('Info: Skipped ' . $skipped_missing_image . ' product(s) with empty image_link');
}
log_msg('Info: Image sources → db=' . $resolved_from_db_image
    . ', gallery=' . $resolved_from_gallery
    . ', folder_match=' . $resolved_from_folder_match
    . ', fallback=' . $resolved_from_global_fallback
    . ', slug_override=' . $resolved_from_slug_override);
log_msg('Info: URL → ' . $feed_domain . '/feeds/products.csv');

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Checks if a table contains a specific column.
 */
function column_exists(mysqli $conn, string $table, string $column): bool {
    $table_esc = $conn->real_escape_string($table);
    $col_esc = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$table_esc}` LIKE '{$col_esc}'";
    $res = $conn->query($sql);
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

/**
 * Normalizes URL path encoding for Google feeds.
 * Keeps scheme/host and re-encodes each path segment safely.
 */
function normalize_feed_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return $url;
    }

    $path = $parts['path'] ?? '';
    $segments = explode('/', $path);
    $encoded_segments = [];

    foreach ($segments as $seg) {
        if ($seg === '') {
            $encoded_segments[] = '';
            continue;
        }
        $encoded_segments[] = rawurlencode(rawurldecode($seg));
    }

    $normalized = $parts['scheme'] . '://' . $parts['host'] . implode('/', $encoded_segments);
    if (isset($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
        $normalized .= '#' . $parts['fragment'];
    }

    return $normalized;
}

/**
 * Cleans GTIN and returns it only when length is valid.
 */
function clean_gtin(string $gtin): string {
    $digits = preg_replace('/\D+/', '', trim($gtin));
    if ($digits === null) {
        return '';
    }

    $len = strlen($digits);
    if ($len < 8 || $len > 14) {
        return '';
    }

    return $digits;
}

/**
 * Returns first gallery image path per product_id from product_images table.
 */
function load_first_gallery_images(mysqli $conn): array {
    $images = [];

    $has_table = $conn->query("SHOW TABLES LIKE 'product_images'");
    if (!($has_table instanceof mysqli_result) || $has_table->num_rows === 0) {
        return $images;
    }

    $res = $conn->query("SELECT product_id, image_path FROM product_images ORDER BY product_id ASC, sort_order ASC, id ASC");
    if (!($res instanceof mysqli_result)) {
        return $images;
    }

    while ($row = $res->fetch_assoc()) {
        $pid = (int)($row['product_id'] ?? 0);
        if ($pid <= 0 || isset($images[$pid])) {
            continue;
        }
        $path = trim((string)($row['image_path'] ?? ''));
        if ($path !== '') {
            $images[$pid] = $path;
        }
    }

    return $images;
}

/**
 * Resolves the best supported image path for a product.
 * Order: DB image_link → product_images table → folder matching by product name.
 */
function resolve_supported_product_image_relative(array $product, array $first_gallery_images, string $root): array {
    $candidates = [];

    if (!empty($product['image_link'])) {
        $candidates[] = ['path' => (string)$product['image_link'], 'source' => 'db_image_link'];
    }

    $pid = (int)($product['id'] ?? 0);
    if ($pid > 0 && !empty($first_gallery_images[$pid])) {
        $candidates[] = ['path' => (string)$first_gallery_images[$pid], 'source' => 'product_images_table'];
    }

    foreach ($candidates as $candidate) {
        $relative = candidate_to_relative_path((string)$candidate['path']);
        if ($relative === '') {
            continue;
        }

        $supported = ensure_supported_relative_image($relative, $root);
        if ($supported !== '') {
            return [$supported, (string)$candidate['source']];
        }
    }

    $name_en = trim((string)($product['name_en'] ?? ''));
    if ($name_en !== '' && function_exists('find_product_image_folder')) {
        $images_dir = $root . '/images';
        $folder = find_product_image_folder($name_en, $images_dir);
        if (!empty($folder)) {
            $file = choose_supported_image_from_folder($images_dir . '/' . $folder);
            if ($file !== '') {
                return ['images/' . $folder . '/' . $file, 'folder_match'];
            }
        }
    }

    return ['', 'missing'];
}

/**
 * Converts absolute/local candidate paths into clean relative paths.
 */
function candidate_to_relative_path(string $candidate): string {
    $candidate = trim($candidate);
    if ($candidate === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $candidate)) {
        $path = parse_url($candidate, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }
        $candidate = $path;
    }

    $candidate = preg_replace('/[?#].*$/', '', $candidate);
    $candidate = str_replace('\\', '/', $candidate);
    $candidate = rawurldecode($candidate);

    return ltrim($candidate, '/');
}

/**
 * Ensures a relative image path points to an accepted format.
 * Accepted: png, jpg, jpeg, gif.
 * If only webp exists, tries same-basename variants, then auto-generates jpg.
 */
function ensure_supported_relative_image(string $relative, string $root): string {
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '') {
        return '';
    }

    $abs = rtrim($root, '/') . '/' . $relative;
    if (!is_file($abs)) {
        return '';
    }

    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true)) {
        return $relative;
    }

    if ($ext !== 'webp') {
        return '';
    }

    $dir = dirname($abs);
    $base = pathinfo($abs, PATHINFO_FILENAME);
    foreach (['png', 'jpg', 'jpeg', 'gif'] as $target_ext) {
        $candidate_abs = $dir . '/' . $base . '.' . $target_ext;
        if (is_file($candidate_abs)) {
            return ltrim(substr($candidate_abs, strlen(rtrim($root, '/'))), '/');
        }
    }

    $generated_jpg = create_jpeg_from_webp($abs);
    if ($generated_jpg !== '') {
        return ltrim(substr($generated_jpg, strlen(rtrim($root, '/'))), '/');
    }

    return '';
}

/**
 * Chooses the most suitable supported image filename from a folder.
 */
function choose_supported_image_from_folder(string $folder_abs_path): string {
    if (!is_dir($folder_abs_path)) {
        return '';
    }

    foreach (['1.png', '1.jpg', '1.jpeg', '1.gif'] as $main) {
        if (is_file($folder_abs_path . '/' . $main)) {
            return $main;
        }
    }

    $files = @scandir($folder_abs_path);
    if (!$files) {
        return '';
    }

    $supported = [];
    $webp_files = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true)) {
            $supported[] = $file;
        } elseif ($ext === 'webp') {
            $webp_files[] = $file;
        }
    }

    if (!empty($supported)) {
        natsort($supported);
        return (string)reset($supported);
    }

    if (!empty($webp_files)) {
        natsort($webp_files);
        $first_webp = (string)reset($webp_files);
        $generated_jpg = create_jpeg_from_webp($folder_abs_path . '/' . $first_webp);
        if ($generated_jpg !== '') {
            return basename($generated_jpg);
        }
    }

    return '';
}

/**
 * Creates a jpg derivative from a webp file when needed by Merchant feed.
 */
function create_jpeg_from_webp(string $webp_abs_path): string {
    if (!is_file($webp_abs_path)) {
        return '';
    }

    $jpg_abs_path = preg_replace('/\.webp$/i', '.jpg', $webp_abs_path);
    if (!is_string($jpg_abs_path) || $jpg_abs_path === '') {
        return '';
    }

    if (is_file($jpg_abs_path)) {
        return $jpg_abs_path;
    }

    if (!function_exists('imagecreatefromwebp') || !function_exists('imagejpeg')) {
        return '';
    }

    $img = @imagecreatefromwebp($webp_abs_path);
    if ($img === false) {
        return '';
    }

    $ok = @imagejpeg($img, $jpg_abs_path, 90);
    imagedestroy($img);

    if (!$ok || !is_file($jpg_abs_path)) {
        return '';
    }

    @chmod($jpg_abs_path, 0664);
    return $jpg_abs_path;
}

/**
 * Finds a site-wide supported fallback image (png/jpg/jpeg/gif).
 */
function find_global_fallback_relative_image(string $root): string {
    $fixed_candidates = [
        'images/hero-beauty-1.png',
        'images/category-circles.png',
        'images/hero-beauty-2.png',
    ];

    foreach ($fixed_candidates as $candidate) {
        $supported = ensure_supported_relative_image($candidate, $root);
        if ($supported !== '') {
            return $supported;
        }
    }

    $images_root = rtrim($root, '/') . '/images';
    if (!is_dir($images_root)) {
        return '';
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($images_root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $file_info) {
        if (!$file_info->isFile()) {
            continue;
        }

        $ext = strtolower($file_info->getExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true)) {
            continue;
        }

        $abs = str_replace('\\', '/', $file_info->getPathname());
        $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        if (str_starts_with($abs, $prefix)) {
            return ltrim(substr($abs, strlen($prefix)), '/');
        }
    }

    return '';
}

/**
 * Convert ALL-CAPS or mostly-uppercase text to Title Case.
 * Handles brand names like "SEOUL 1988", "THE ORDINARY", etc.
 * Words of 3 chars or fewer (of, the, for, etc.) are lowercased unless first word.
 */
function fix_capitalization(string $text): string {
    if (!preg_match('/[A-Za-z]/', $text)) {
        return $text; // No Latin characters (Arabic-only), skip
    }
    // Count uppercase vs lowercase
    preg_match_all('/[A-Z]/', $text, $up);
    preg_match_all('/[a-z]/', $text, $lo);
    $upper_count = count($up[0]);
    $lower_count = count($lo[0]);
    // If 3+ consecutive uppercase letters exist, or more uppercase than lowercase → fix
    if ($upper_count >= 3 && $upper_count > $lower_count) {
        $text = mb_convert_case(mb_strtolower($text, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
    return $text;
}

function log_msg(string $msg): void {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] Feed CSV: ' . $msg . PHP_EOL;
    // Always print to stdout (captured by cron's >> redirect)
    echo $line;
    // Also append directly to the log file
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
