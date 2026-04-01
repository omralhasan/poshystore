<?php
/**
 * Rebuild Google Merchant CSV from feeds/meta_products.csv
 *
 * Why this exists:
 * - meta_products.csv currently has the full 71 products.
 * - Google feed variants were reduced to 40 because WebP image links were excluded.
 *
 * This script rewrites feeds/products_gmc_imagetype_clean.csv with:
 * - 71 rows (no product skipping)
 * - non-WebP image links (png/jpg/jpeg/gif), with a global fallback image if needed
 * - Google-style columns and availability values
 */

$root = rtrim(__DIR__, '/');
$sourceFile = $root . '/feeds/meta_products.csv';
$targetFile = $root . '/feeds/products_gmc_imagetype_clean.csv';
$syncTargets = [
    $root . '/feeds/products_gmc_reupload_ready.csv',
];

$feedDomain = 'https://poshystore.com';

if (!is_file($sourceFile)) {
    fwrite(STDERR, "Source CSV not found: {$sourceFile}\n");
    exit(1);
}

$fallbackRelative = findGlobalFallbackImage($root);
if ($fallbackRelative === '') {
    fwrite(STDERR, "No fallback image found under images/. Add at least one png/jpg/jpeg/gif image.\n");
    exit(1);
}

$in = fopen($sourceFile, 'r');
if (!$in) {
    fwrite(STDERR, "Cannot open source file: {$sourceFile}\n");
    exit(1);
}

$out = fopen($targetFile, 'w');
if (!$out) {
    fclose($in);
    fwrite(STDERR, "Cannot write target file: {$targetFile}\n");
    exit(1);
}

// Keep BOM for spreadsheet compatibility.
fwrite($out, "\xEF\xBB\xBF");

$srcHeaders = fgetcsv($in);
if (!is_array($srcHeaders)) {
    fclose($in);
    fclose($out);
    fwrite(STDERR, "Source CSV has no header row.\n");
    exit(1);
}

$srcHeaders[0] = stripUtf8Bom((string)$srcHeaders[0]);
$idx = array_flip($srcHeaders);

$requiredSourceColumns = ['id', 'title', 'description', 'availability', 'price', 'link', 'image_link', 'brand'];
foreach ($requiredSourceColumns as $col) {
    if (!isset($idx[$col])) {
        fclose($in);
        fclose($out);
        fwrite(STDERR, "Missing source column: {$col}\n");
        exit(1);
    }
}

$googleHeaders = [
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
fputcsv($out, $googleHeaders);

$written = 0;
$fallbackUsed = 0;
$webpRemaining = 0;
$emptyImages = 0;
$duplicateIds = 0;
$seenIds = [];

while (($row = fgetcsv($in)) !== false) {
    $id = trim((string)($row[$idx['id']] ?? ''));
    $title = trim((string)($row[$idx['title']] ?? ''));
    $description = trim((string)($row[$idx['description']] ?? ''));
    $availability = normalizeAvailability((string)($row[$idx['availability']] ?? ''));
    $price = normalizePrice((string)($row[$idx['price']] ?? ''));
    $link = normalizeFeedUrl((string)($row[$idx['link']] ?? ''));
    $imageLink = trim((string)($row[$idx['image_link']] ?? ''));
    $brand = trim((string)($row[$idx['brand']] ?? ''));

    if ($id === '') {
        // Stable fallback ID from product URL when missing.
        $id = 'slug-' . substr(sha1($link), 0, 16);
    }

    if (isset($seenIds[$id])) {
        $duplicateIds++;
        $id = $id . '-dup-' . $duplicateIds;
    }
    $seenIds[$id] = true;

    if ($title === '') {
        $title = 'Product';
    }

    if ($description === '') {
        $description = $title;
    }

    if ($brand === '') {
        $brand = 'Poshy Lifestyle';
    }

    if ($link === '') {
        $link = $feedDomain;
    }

    $resolvedImageLink = resolveGoogleImageLink($imageLink, $root, $feedDomain, $fallbackRelative);
    if ($resolvedImageLink === '') {
        $emptyImages++;
        $resolvedImageLink = normalizeFeedUrl($feedDomain . '/' . ltrim($fallbackRelative, '/'));
    }

    if (str_ends_with(strtolower((string)parse_url($resolvedImageLink, PHP_URL_PATH)), '.webp')) {
        $webpRemaining++;
    }

    if (normalizeFeedUrl($feedDomain . '/' . ltrim($fallbackRelative, '/')) === $resolvedImageLink) {
        $fallbackUsed++;
    }

    fputcsv($out, [
        $id,
        $title,
        $description,
        $link,
        $resolvedImageLink,
        $availability,
        $price,
        '',
        $brand,
        '',
        '',
        'no',
        'new',
        'Health & Beauty',
    ]);

    $written++;
}

fclose($in);
fclose($out);

@chmod($targetFile, 0664);

// Keep the upload-ready file in sync.
foreach ($syncTargets as $syncFile) {
    @copy($targetFile, $syncFile);
    @chmod($syncFile, 0664);
}

echo "Rebuilt: {$targetFile}\n";
echo "Rows written: {$written}\n";
echo "Fallback image: {$fallbackRelative}\n";
echo "Fallback used: {$fallbackUsed}\n";
echo "Remaining webp links: {$webpRemaining}\n";
echo "Empty image rows: {$emptyImages}\n";

exit(0);

function stripUtf8Bom(string $value): string
{
    return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
}

function normalizeAvailability(string $value): string
{
    $v = strtolower(trim($value));
    $v = str_replace(['-', '  '], [' ', ' '], $v);

    if ($v === 'out of stock' || $v === 'out_of_stock') {
        return 'out_of_stock';
    }

    if ($v === 'preorder' || $v === 'pre order') {
        return 'preorder';
    }

    if ($v === 'backorder' || $v === 'back order') {
        return 'backorder';
    }

    return 'in_stock';
}

function normalizePrice(string $value): string
{
    $v = trim($value);
    if ($v === '') {
        return '0.00 JOD';
    }

    if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*([A-Za-z]{3})?/', $v, $m)) {
        $amount = number_format((float)$m[1], 2, '.', '');
        $currency = strtoupper($m[2] ?? 'JOD');
        return $amount . ' ' . $currency;
    }

    return $v;
}

function resolveGoogleImageLink(string $imageLink, string $root, string $feedDomain, string $fallbackRelative): string
{
    $relative = candidateToRelativePath($imageLink);

    if ($relative !== '') {
        $supported = ensureSupportedRelativeImage($relative, $root);
        if ($supported !== '') {
            return normalizeFeedUrl($feedDomain . '/' . ltrim($supported, '/'));
        }
    }

    if ($fallbackRelative !== '') {
        return normalizeFeedUrl($feedDomain . '/' . ltrim($fallbackRelative, '/'));
    }

    return '';
}

function candidateToRelativePath(string $candidate): string
{
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
    $candidate = str_replace('\\', '/', (string)$candidate);
    $candidate = rawurldecode((string)$candidate);

    return ltrim((string)$candidate, '/');
}

function ensureSupportedRelativeImage(string $relative, string $root): string
{
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

    foreach (['png', 'jpg', 'jpeg', 'gif'] as $targetExt) {
        $candidateAbs = $dir . '/' . $base . '.' . $targetExt;
        if (is_file($candidateAbs)) {
            return ltrim(substr($candidateAbs, strlen(rtrim($root, '/'))), '/');
        }
    }

    return '';
}

function findGlobalFallbackImage(string $root): string
{
    $preferred = [
        'images/hero-beauty-1.png',
        'images/category-circles.png',
        'images/hero-beauty-2.png',
    ];

    foreach ($preferred as $candidate) {
        $supported = ensureSupportedRelativeImage($candidate, $root);
        if ($supported !== '') {
            return $supported;
        }
    }

    $imagesRoot = rtrim($root, '/') . '/images';
    if (!is_dir($imagesRoot)) {
        return '';
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imagesRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $ext = strtolower($fileInfo->getExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true)) {
            continue;
        }

        $abs = str_replace('\\', '/', $fileInfo->getPathname());
        $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        if (str_starts_with($abs, $prefix)) {
            return ltrim(substr($abs, strlen($prefix)), '/');
        }
    }

    return '';
}

function normalizeFeedUrl(string $url): string
{
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
    $encoded = [];

    foreach ($segments as $seg) {
        if ($seg === '') {
            $encoded[] = '';
            continue;
        }
        $encoded[] = rawurlencode(rawurldecode($seg));
    }

    $normalized = $parts['scheme'] . '://' . $parts['host'] . implode('/', $encoded);
    if (isset($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
        $normalized .= '#' . $parts['fragment'];
    }

    return $normalized;
}
