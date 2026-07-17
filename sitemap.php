<?php
/**
 * Dynamic XML Sitemap Generator
 * Outputs both English and Arabic versions of every page.
 *
 * Usage:
 *   https://poshystore.com/sitemap.php
 *
 * Add to .htaccess or robots.txt:
 *   Sitemap: https://poshystore.com/sitemap.php
 */

define('SITE_BASE', 'https://poshystore.com');
define('CLI_RUN', php_sapi_name() === 'cli');

$root = rtrim(realpath(__DIR__), '/');
require_once $root . '/config.php';
require_once $root . '/includes/db_connect.php';

header('Content-Type: application/xml; charset=UTF-8');
if (!CLI_RUN) {
    header('X-Robots-Tag: noindex');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
          xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// ── Helper to output a URL entry with hreflang ──────────────────────────
function sitemap_url(string $en_path, string $lastmod = '', string $changefreq = 'weekly', string $priority = '0.8'): void {
    $en_url = SITE_BASE . '/' . ltrim($en_path, '/');
    $ar_url = SITE_BASE . '/ar/' . ltrim($en_path, '/');

    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($en_url) . "</loc>\n";
    if ($lastmod) echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo '    <xhtml:link rel="alternate" hreflang="en" href="' . htmlspecialchars($en_url) . '" />' . "\n";
    echo '    <xhtml:link rel="alternate" hreflang="ar" href="' . htmlspecialchars($ar_url) . '" />' . "\n";
    echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($en_url) . '" />' . "\n";
    echo "  </url>\n";
}

$today = date('Y-m-d');

// ── Homepage ────────────────────────────────────────────────────────────
sitemap_url('/', $today, 'daily', '1.0');

// ── Shop (redirects to /, but include for completeness) ─────────────────
sitemap_url('/pages/shop/shop.php', $today, 'weekly', '0.5');

// ── Policy Pages ────────────────────────────────────────────────────────
$policy_files = [
    'privacy-policy.php',
    'terms-of-service.php',
    'about-us.php',
    'contact-us.php',
    'return-policy.php',
    'shipping-policy.php',
    'cancellation-policy.php',
];
foreach ($policy_files as $pf) {
    sitemap_url('/pages/policies/' . $pf, $today, 'monthly', '0.4');
}

// ── Products ────────────────────────────────────────────────────────────
$product_result = $conn->query(
    "SELECT slug, updated_at FROM products WHERE slug IS NOT NULL AND slug != '' ORDER BY id ASC"
);
if ($product_result && $product_result->num_rows > 0) {
    while ($p = $product_result->fetch_assoc()) {
        $lastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $today;
        sitemap_url('/' . $p['slug'], $lastmod, 'weekly', '0.9');
    }
}

// ── Categories (as /category/slug) ──────────────────────────────────────
$cat_result = $conn->query(
    "SELECT name_en FROM categories ORDER BY sort_order ASC, id ASC"
);
if ($cat_result && $cat_result->num_rows > 0) {
    while ($c = $cat_result->fetch_assoc()) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $c['name_en']), '-'));
        if ($slug !== '') {
            sitemap_url('/category/' . $slug, $today, 'weekly', '0.7');
        }
    }
}

// ── Podcasts ────────────────────────────────────────────────────────────
$pod_result = $conn->query(
    "SELECT slug, updated_at FROM podcasts WHERE slug IS NOT NULL AND slug != '' ORDER BY id ASC"
);
if ($pod_result && $pod_result->num_rows > 0) {
    while ($pod = $pod_result->fetch_assoc()) {
        $lastmod = !empty($pod['updated_at']) ? date('Y-m-d', strtotime($pod['updated_at'])) : $today;
        sitemap_url('/podcast/' . $pod['slug'], $lastmod, 'monthly', '0.6');
    }
}

echo '</urlset>' . "\n";

$conn->close();
