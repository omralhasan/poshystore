<?php
/**
 * Meta Commerce Manager CSV Feed Generator
 *
 * Generates: /feeds/meta_products.csv
 * Public URL: https://poshystore.com/feeds/meta_products.csv
 *
 * Required Meta columns:
 * id,title,description,availability,condition,price,link,image_link,brand
 *
 * Usage:
 *   php meta_feed.php
 *   https://poshystore.com/meta_feed.php
 *
 * Optional query params (web):
 *   ?lang=ar         Use Arabic title/description where available.
 *   ?serve=1         Generate then stream CSV directly as response.
 */

define('CLI_RUN', php_sapi_name() === 'cli');

$root = rtrim(realpath(__DIR__), '/');
require_once $root . '/config.php';
require_once $root . '/includes/db_connect.php';
require_once $root . '/includes/product_image_helper.php';

$LANG = (isset($_GET['lang']) && $_GET['lang'] === 'ar') ? 'ar' : 'en';
$SERVE_DIRECT = !CLI_RUN && isset($_GET['serve']) && $_GET['serve'] === '1';

$META_CURRENCY = getenv('META_FEED_CURRENCY') ?: 'JOD';
$OUTPUT_DIR = $root . '/feeds';
$OUTPUT_FILE = $OUTPUT_DIR . '/meta_products.csv';
$LOG_FILE = $root . '/logs/meta_feed.log';
$FEED_DOMAIN = rtrim(SITE_URL ?: 'https://poshystore.com', '/');

meta_log($LOG_FILE, 'Start: Generating Meta CSV');

if (!is_dir($OUTPUT_DIR) && !mkdir($OUTPUT_DIR, 0755, true)) {
    meta_log($LOG_FILE, 'Error: Could not create directory: ' . $OUTPUT_DIR);
    http_response_code(500);
    exit('Failed to create feeds directory.');
}

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
            p.stock_quantity,
            p.image_link,
            COALESCE(b.name_en, 'Poshy Lifestyle') AS brand_en,
            COALESCE(b.name_ar, 'بوشي لايف ستايل')  AS brand_ar
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.stock_quantity >= 0
        ORDER BY p.id ASC";

$result = $conn->query($sql);
if (!$result) {
    meta_log($LOG_FILE, 'Error: DB query failed - ' . $conn->error);
    http_response_code(500);
    exit('Database query failed.');
}

$first_gallery_images = load_first_gallery_images($conn);
$fallback_relative = find_meta_fallback_relative_image($root);
if ($fallback_relative === '') {
    meta_log($LOG_FILE, 'Warn: No fallback image found. Products with missing images may be skipped.');
}

$fh = fopen($OUTPUT_FILE, 'w');
if (!$fh) {
    meta_log($LOG_FILE, 'Error: Cannot open file for writing: ' . $OUTPUT_FILE);
    http_response_code(500);
    exit('Cannot write CSV file.');
}

// BOM for better compatibility with spreadsheet tools.
fwrite($fh, "\xEF\xBB\xBF");

$headers = [
    'id',
    'title',
    'description',
    'availability',
    'condition',
    'price',
    'link',
    'image_link',
    'brand',
];
fputcsv($fh, $headers);

$rows_written = 0;
$used_fallback = 0;
$used_webp = 0;
$skipped = 0;

while ($p = $result->fetch_assoc()) {
    $title = ($LANG === 'ar' && !empty($p['name_ar'])) ? $p['name_ar'] : (string)$p['name_en'];

    $raw_desc = '';
    if ($LANG === 'ar') {
        $raw_desc = !empty($p['short_description_ar'])
            ? (string)$p['short_description_ar']
            : (!empty($p['description_ar']) ? (string)$p['description_ar'] : (string)$p['description']);
    } else {
        $raw_desc = !empty($p['short_description_en'])
            ? (string)$p['short_description_en']
            : (string)$p['description'];
    }
    $description = clean_meta_description($raw_desc, $title);

    $availability = ((int)$p['stock_quantity'] > 0) ? 'in stock' : 'out of stock';
    $condition = 'new';

    $price_value = (float)$p['price_jod'];
    $price = number_format($price_value, 2, '.', '') . ' ' . $META_CURRENCY;

    $link = !empty($p['slug'])
        ? $FEED_DOMAIN . '/' . rawurlencode((string)$p['slug'])
        : $FEED_DOMAIN . '/product.php?id=' . (int)$p['id'];
    $link = normalize_feed_url($link);

    $image_relative = resolve_meta_image_relative($p, $first_gallery_images, $root, $fallback_relative);
    if ($image_relative === '') {
        $skipped++;
        continue;
    }
    if ($image_relative === $fallback_relative) {
        $used_fallback++;
    }
    if (str_ends_with(strtolower($image_relative), '.webp')) {
        $used_webp++;
    }

    $image_link = normalize_feed_url($FEED_DOMAIN . '/' . ltrim($image_relative, '/'));

    $brand = ($LANG === 'ar' && !empty($p['brand_ar']))
        ? (string)$p['brand_ar']
        : (string)$p['brand_en'];

    fputcsv($fh, [
        (int)$p['id'],
        $title,
        $description,
        $availability,
        $condition,
        $price,
        $link,
        $image_link,
        $brand,
    ]);

    $rows_written++;
}

fclose($fh);
$conn->close();

@chmod($OUTPUT_FILE, 0664);
@chown($OUTPUT_FILE, 'apache');

$size_kb = round((float)filesize($OUTPUT_FILE) / 1024, 1);
meta_log($LOG_FILE, 'Success: ' . $rows_written . ' products written to meta_products.csv (' . $size_kb . ' KB)');
meta_log($LOG_FILE, 'Info: Image stats - webp=' . $used_webp . ', fallback=' . $used_fallback . ', skipped=' . $skipped);
meta_log($LOG_FILE, 'Info: URL - ' . $FEED_DOMAIN . '/feeds/meta_products.csv');

if ($SERVE_DIRECT) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: inline; filename="meta_products.csv"');
    readfile($OUTPUT_FILE);
    exit;
}

if (!CLI_RUN) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Meta CSV generated successfully\n";
    echo "Rows: {$rows_written}\n";
    echo "File: {$OUTPUT_FILE}\n";
    echo "URL: {$FEED_DOMAIN}/feeds/meta_products.csv\n";
}

/**
 * Load first image_path per product from product_images table.
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
 * Resolve a Meta-ready image path, preferring WebP output.
 */
function resolve_meta_image_relative(array $product, array $first_gallery_images, string $root, string $fallback_relative): string {
    $candidates = [];

    if (!empty($product['image_link'])) {
        $candidates[] = (string)$product['image_link'];
    }

    $pid = (int)($product['id'] ?? 0);
    if ($pid > 0 && !empty($first_gallery_images[$pid])) {
        $candidates[] = (string)$first_gallery_images[$pid];
    }

    $name_en = trim((string)($product['name_en'] ?? ''));
    if ($name_en !== '' && function_exists('find_product_image_folder')) {
        $images_dir = $root . '/images';
        $folder = find_product_image_folder($name_en, $images_dir);
        if (!empty($folder)) {
            $folder_file = pick_primary_image_from_folder($images_dir . '/' . $folder);
            if ($folder_file !== '') {
                $candidates[] = 'images/' . $folder . '/' . $folder_file;
            }
        }
    }

    foreach ($candidates as $candidate) {
        $relative = candidate_to_relative_path($candidate);
        if ($relative === '') {
            continue;
        }

        $webp_relative = ensure_meta_webp_path($relative, $root);
        if ($webp_relative !== '') {
            return $webp_relative;
        }
    }

    return $fallback_relative;
}

/**
 * Pick a likely primary image file from a product folder.
 */
function pick_primary_image_from_folder(string $folder_abs): string {
    if (!is_dir($folder_abs)) {
        return '';
    }

    foreach (['1.webp', '1.jpg', '1.jpeg', '1.png', '1.gif'] as $name) {
        if (is_file($folder_abs . '/' . $name)) {
            return $name;
        }
    }

    $files = @scandir($folder_abs);
    if (!$files) {
        return '';
    }

    $all = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif'], true)) {
            $all[] = $file;
        }
    }

    if (empty($all)) {
        return '';
    }

    natsort($all);

    foreach ($all as $file) {
        if (str_ends_with(strtolower($file), '.webp')) {
            return $file;
        }
    }

    return (string)reset($all);
}

/**
 * Convert candidate URL/path to relative filesystem path.
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
 * Ensure resulting path points to WebP.
 * If source is non-webp image, tries existing webp sibling, then converts.
 */
function ensure_meta_webp_path(string $relative, string $root): string {
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '') {
        return '';
    }

    $abs = rtrim($root, '/') . '/' . $relative;
    if (!is_file($abs)) {
        return '';
    }

    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if ($ext === 'webp') {
        return $relative;
    }

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        return '';
    }

    $webp_relative = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $relative);
    if (!is_string($webp_relative) || $webp_relative === '') {
        return '';
    }

    $webp_abs = rtrim($root, '/') . '/' . $webp_relative;
    if (is_file($webp_abs)) {
        return $webp_relative;
    }

    if (create_webp_variant($abs, $webp_abs)) {
        return $webp_relative;
    }

    return '';
}

/**
 * Create a WebP variant from a jpg/jpeg/png/gif source.
 */
function create_webp_variant(string $source_abs, string $target_abs): bool {
    if (!function_exists('imagewebp')) {
        return false;
    }

    $ext = strtolower(pathinfo($source_abs, PATHINFO_EXTENSION));
    $img = false;

    if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
        $img = @imagecreatefromjpeg($source_abs);
    } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
        $img = @imagecreatefrompng($source_abs);
    } elseif ($ext === 'gif' && function_exists('imagecreatefromgif')) {
        $img = @imagecreatefromgif($source_abs);
    }

    if ($img === false) {
        return false;
    }

    imagepalettetotruecolor($img);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $ok = @imagewebp($img, $target_abs, 85);
    imagedestroy($img);

    if ($ok && is_file($target_abs)) {
        @chmod($target_abs, 0664);
        return true;
    }

    return false;
}

/**
 * Find a global WebP fallback image.
 */
function find_meta_fallback_relative_image(string $root): string {
    $preferred = [
        'images/hero-beauty-1.webp',
        'images/hero-beauty-2.webp',
        'images/category-circles.webp',
    ];

    foreach ($preferred as $candidate) {
        $relative = ensure_meta_webp_path($candidate, $root);
        if ($relative !== '') {
            return $relative;
        }
    }

    $images_root = rtrim($root, '/') . '/images';
    if (!is_dir($images_root)) {
        return '';
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($images_root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file_info) {
        if (!$file_info->isFile()) {
            continue;
        }
        if (strtolower($file_info->getExtension()) !== 'webp') {
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
 * Strip HTML and normalize whitespace for Meta descriptions.
 */
function clean_meta_description(string $text, string $fallback): string {
    $clean = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $clean = preg_replace('/\s+/u', ' ', trim($clean));

    if ($clean === '' || $clean === null) {
        $clean = trim($fallback);
    }

    if (!function_exists('mb_substr')) {
        return substr($clean, 0, 5000);
    }

    return mb_substr($clean, 0, 5000, 'UTF-8');
}

/**
 * Normalize URL by encoding each path segment.
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
 * Append a line to Meta feed log.
 */
function meta_log(string $log_file, string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] Meta Feed: ' . $msg . PHP_EOL;
    if (CLI_RUN) {
        echo $line;
    }
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}
