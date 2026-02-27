<?php
/**
 * Dynamic Product Feed for Google Merchant Center & Meta Commerce Manager
 *
 * Generates an RSS 2.0 XML feed following Google Shopping specifications.
 * Accessible at: http://yourdomain.com/feed.php
 *
 * Supported parameters:
 *   ?lang=en|ar        – title/description language (default: en)
 *   ?currency=JOD|SAR  – price currency (default: JOD)
 *
 * Usage:
 *   Google Merchant Center → Scheduled Fetch → URL: https://yourdomain.com/feed.php
 *   Meta Commerce Manager  → Data Feed URL:        https://yourdomain.com/feed.php
 */

// ─── Bootstrap ─────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// ─── Parameters ────────────────────────────────────────────────────────
$lang     = (isset($_GET['lang']) && $_GET['lang'] === 'ar') ? 'ar' : 'en';
$currency = (isset($_GET['currency']) && strtoupper($_GET['currency']) === 'SAR') ? 'SAR' : 'JOD';

// Simple JOD → SAR conversion rate (update as needed)
$sar_rate = 5.15;

// ─── Fetch products with brand, category, and images ───────────────────
$sql = "SELECT p.id,
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
               p.discount_percentage,
               p.stock_quantity,
               p.image_link,
               COALESCE(b.name_en, 'Poshy Lifestyle') AS brand_en,
               COALESCE(b.name_ar, 'بوشي لايف ستايل') AS brand_ar,
               COALESCE(c.name_en, '')  AS category_en,
               COALESCE(c.name_ar, '')  AS category_ar,
               COALESCE(s.name_en, '')  AS subcategory_en,
               COALESCE(s.name_ar, '')  AS subcategory_ar
        FROM products p
        LEFT JOIN brands        b ON p.brand_id      = b.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        LEFT JOIN categories    c ON s.category_id    = c.id
        WHERE p.stock_quantity >= 0
        ORDER BY p.id ASC";

$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Feed generation error.';
    error_log('Product feed query failed: ' . $conn->error);
    exit;
}

// Gather additional images per product (if product_images table exists)
$extra_images = [];
$img_check = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($img_check && $img_check->num_rows > 0) {
    $img_result = $conn->query("SELECT product_id, image_path FROM product_images ORDER BY product_id, sort_order ASC, id ASC");
    if ($img_result) {
        while ($img = $img_result->fetch_assoc()) {
            $extra_images[(int)$img['product_id']][] = $img['image_path'];
        }
    }
}

// ─── Build XML ─────────────────────────────────────────────────────────
header('Content-Type: application/xml; charset=UTF-8');

$site_url = rtrim(SITE_URL, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:g="http://base.google.com/ns/1.0"
     xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Poshy Lifestyle Store – Product Feed</title>
    <link><?= htmlspecialchars($site_url) ?></link>
    <description>Poshy Lifestyle Store product catalog for Google Merchant Center and Meta Commerce Manager.</description>
    <atom:link href="<?= htmlspecialchars($site_url . '/feed.php') ?>" rel="self" type="application/rss+xml"/>
<?php
while ($p = $result->fetch_assoc()):

    // ── Title & Description ────────────────────────────────────────────
    $title = ($lang === 'ar' && !empty($p['name_ar']))
        ? $p['name_ar']
        : $p['name_en'];

    $description = '';
    if ($lang === 'ar') {
        $description = !empty($p['short_description_ar'])
            ? $p['short_description_ar']
            : (!empty($p['description_ar']) ? $p['description_ar'] : $p['description']);
    } else {
        $description = !empty($p['short_description_en'])
            ? $p['short_description_en']
            : $p['description'];
    }
    // Strip HTML tags for clean feed content
    $description = strip_tags($description);
    // Limit to 5000 chars (Google max)
    $description = mb_substr($description, 0, 5000, 'UTF-8');

    // ── Price ──────────────────────────────────────────────────────────
    $price_jod = (float) $p['price_jod'];

    if ($currency === 'SAR') {
        $price_value = round($price_jod * $sar_rate, 2);
        $price_str   = number_format($price_value, 2, '.', '') . ' SAR';
    } else {
        $price_str = number_format($price_jod, 3, '.', '') . ' JOD';
    }

    // Sale price (if product has a discount)
    $sale_price_str = '';
    if (!empty($p['has_discount']) && $p['has_discount'] && !empty($p['original_price']) && (float)$p['original_price'] > $price_jod) {
        if ($currency === 'SAR') {
            $orig_sar = round((float)$p['original_price'] * $sar_rate, 2);
            // In discount scenario: original_price is full price, price_jod is sale price
            $sale_price_str = $price_str;                                             // current = sale
            $price_str      = number_format($orig_sar, 2, '.', '') . ' SAR';          // full price
        } else {
            $sale_price_str = $price_str;                                             // current = sale
            $price_str      = number_format((float)$p['original_price'], 3, '.', '') . ' JOD';
        }
    }

    // ── Availability ───────────────────────────────────────────────────
    $availability = ((int)$p['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock';

    // ── Product link ───────────────────────────────────────────────────
    $link = $site_url;
    if (!empty($p['slug'])) {
        $link .= '/' . urlencode($p['slug']);
    } else {
        $link .= '/product.php?id=' . (int)$p['id'];
    }

    // ── Image link (absolute URL) ──────────────────────────────────────
    $image_link = '';
    if (!empty($p['image_link'])) {
        $img = trim($p['image_link']);
        // Already absolute? Replace domain to ensure canonical poshystore.com domain
        if (preg_match('#^https?://#i', $img)) {
            $image_link = preg_replace('#^https?://[^/]+#i', $site_url, $img);
        } else {
            $image_link = $site_url . '/' . ltrim($img, '/');
        }
    }

    // ── Brand ──────────────────────────────────────────────────────────
    $brand = ($lang === 'ar' && !empty($p['brand_ar']))
        ? $p['brand_ar']
        : $p['brand_en'];

    // ── Google product category (best-effort mapping) ──────────────────
    $google_category = 'Health &amp; Beauty';  // Default
    $cat_lower = strtolower($p['category_en']);
    if (str_contains($cat_lower, 'skin') || str_contains($cat_lower, 'care')) {
        $google_category = 'Health &amp; Beauty &gt; Personal Care &gt; Cosmetics &gt; Skin Care';
    } elseif (str_contains($cat_lower, 'hair')) {
        $google_category = 'Health &amp; Beauty &gt; Personal Care &gt; Hair Care';
    } elseif (str_contains($cat_lower, 'makeup') || str_contains($cat_lower, 'cosmetic')) {
        $google_category = 'Health &amp; Beauty &gt; Personal Care &gt; Cosmetics';
    } elseif (str_contains($cat_lower, 'fragrance') || str_contains($cat_lower, 'perfume')) {
        $google_category = 'Health &amp; Beauty &gt; Personal Care &gt; Cosmetics &gt; Fragrance';
    }

    // ── Product type (store category path) ─────────────────────────────
    $product_type = '';
    if (!empty($p['category_en'])) {
        $product_type = htmlspecialchars($p['category_en']);
        if (!empty($p['subcategory_en'])) {
            $product_type .= ' &gt; ' . htmlspecialchars($p['subcategory_en']);
        }
    }

    // ── Additional images ──────────────────────────────────────────────
    $additional_images = [];
    if (isset($extra_images[(int)$p['id']])) {
        foreach ($extra_images[(int)$p['id']] as $ai) {
            if (preg_match('#^https?://#i', $ai)) {
                $additional_images[] = preg_replace('#^https?://[^/]+#i', $site_url, $ai);
            } else {
                $additional_images[] = $site_url . '/' . ltrim($ai, '/');
            }
        }
    }
?>
    <item>
      <g:id><?= (int)$p['id'] ?></g:id>
      <title><![CDATA[<?= $title ?>]]></title>
      <description><![CDATA[<?= $description ?>]]></description>
      <link><?= htmlspecialchars($link) ?></link>
      <g:image_link><?= htmlspecialchars($image_link) ?></g:image_link>
<?php foreach (array_slice($additional_images, 0, 10) as $ai_url): ?>
      <g:additional_image_link><?= htmlspecialchars($ai_url) ?></g:additional_image_link>
<?php endforeach; ?>
      <g:price><?= $price_str ?></g:price>
<?php if ($sale_price_str): ?>
      <g:sale_price><?= $sale_price_str ?></g:sale_price>
<?php endif; ?>
      <g:availability><?= $availability ?></g:availability>
      <g:brand><![CDATA[<?= $brand ?>]]></g:brand>
      <g:condition>new</g:condition>
      <g:google_product_category><?= $google_category ?></g:google_product_category>
<?php if ($product_type): ?>
      <g:product_type><?= $product_type ?></g:product_type>
<?php endif; ?>
      <g:identifier_exists>false</g:identifier_exists>
    </item>
<?php endwhile; ?>
  </channel>
</rss>
