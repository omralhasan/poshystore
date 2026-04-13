<?php
/**
 * Sync product images from filesystem to database (safe mode)
 *
 * - Inserts missing rows into product_images table
 * - Fills products.image_link only when empty
 * - Does NOT overwrite existing image values
 *
 * Usage:
 *   /sync_product_images_db.php?token=poshy_sync_images_2026
 */

header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';
$expectedToken = getenv('SYNC_IMAGES_TOKEN') ?: 'poshy_sync_images_2026';
if (!hash_equals($expectedToken, (string)$token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/product_image_helper.php';

function safe_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function table_exists_local(mysqli $conn, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    $stmt = $conn->prepare('SHOW TABLES LIKE ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = $res && $res->num_rows > 0;
    $stmt->close();
    return $ok;
}

function normalize_rel(string $path): string
{
    $path = rawurldecode($path);
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $path = preg_replace('#/+#', '/', $path);
    return (string)$path;
}

function pick_primary(array $files): ?string
{
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_FILENAME) === '1') {
            return $file;
        }
    }
    return $files[0] ?? null;
}

$dbName = '';
$dbRes = $conn->query("SELECT DATABASE() AS db_name");
if ($dbRes && ($row = $dbRes->fetch_assoc())) {
    $dbName = (string)($row['db_name'] ?? '');
}

if (!table_exists_local($conn, 'products')) {
    safe_json([
        'success' => false,
        'database' => $dbName,
        'error' => 'products table not found in active database'
    ], 500);
}

$imagesDir = __DIR__ . '/images';
if (!is_dir($imagesDir)) {
    safe_json([
        'success' => false,
        'database' => $dbName,
        'error' => 'images directory not found'
    ], 500);
}

$createSql = "CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id)
)";
if (!$conn->query($createSql)) {
    safe_json([
        'success' => false,
        'database' => $dbName,
        'error' => 'failed creating product_images table',
        'details' => $conn->error
    ], 500);
}

$stats = [
    'products_scanned' => 0,
    'products_with_folder' => 0,
    'gallery_rows_inserted' => 0,
    'image_link_updated' => 0,
    'errors' => []
];

$existing = [];
$maxSort = [];
$firstImage = [];

$existingRes = $conn->query("SELECT product_id, image_path, sort_order FROM product_images ORDER BY product_id ASC, sort_order ASC, id ASC");
if ($existingRes) {
    while ($row = $existingRes->fetch_assoc()) {
        $pid = (int)($row['product_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $rel = normalize_rel((string)($row['image_path'] ?? ''));
        if ($rel === '') {
            continue;
        }
        $existing[$pid][$rel] = true;
        $sort = (int)($row['sort_order'] ?? 0);
        if (!isset($maxSort[$pid]) || $sort > $maxSort[$pid]) {
            $maxSort[$pid] = $sort;
        }
        if (!isset($firstImage[$pid])) {
            $firstImage[$pid] = $rel;
        }
    }
}

$insertStmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
$updateStmt = $conn->prepare("UPDATE products SET image_link = ? WHERE id = ? AND (image_link IS NULL OR TRIM(image_link) = '' OR LOWER(TRIM(image_link))='null')");
if (!$insertStmt || !$updateStmt) {
    safe_json([
        'success' => false,
        'database' => $dbName,
        'error' => 'failed preparing statements',
        'details' => $conn->error
    ], 500);
}

$productsRes = $conn->query("SELECT id, name_en, image_link FROM products ORDER BY id ASC");
if (!$productsRes) {
    safe_json([
        'success' => false,
        'database' => $dbName,
        'error' => 'failed loading products',
        'details' => $conn->error
    ], 500);
}

while ($product = $productsRes->fetch_assoc()) {
    $stats['products_scanned']++;

    $pid = (int)($product['id'] ?? 0);
    if ($pid <= 0) {
        continue;
    }

    $nameEn = trim((string)($product['name_en'] ?? ''));
    $currentImage = trim((string)($product['image_link'] ?? ''));

    $folder = ($nameEn !== '') ? find_product_image_folder($nameEn, $imagesDir) : null;
    $files = [];

    if (!empty($folder)) {
        $stats['products_with_folder']++;
        $folderPath = rtrim($imagesDir, '/') . '/' . $folder;
        $files = get_png_files($folderPath);

        foreach ($files as $file) {
            $rel = normalize_rel('images/' . $folder . '/' . $file);
            if ($rel === '') {
                continue;
            }

            if (!isset($existing[$pid][$rel])) {
                $nextSort = ($maxSort[$pid] ?? 0) + 1;
                $insertStmt->bind_param('isi', $pid, $rel, $nextSort);
                if ($insertStmt->execute()) {
                    $stats['gallery_rows_inserted']++;
                    $existing[$pid][$rel] = true;
                    $maxSort[$pid] = $nextSort;
                    if (!isset($firstImage[$pid])) {
                        $firstImage[$pid] = $rel;
                    }
                } else {
                    $stats['errors'][] = 'insert failed for product #' . $pid;
                }
            }
        }
    }

    $isEmpty = ($currentImage === '' || strtolower($currentImage) === 'null');
    if ($isEmpty) {
        $newPrimary = null;

        if (!empty($files) && !empty($folder)) {
            $primaryFile = pick_primary($files);
            if ($primaryFile !== null) {
                $newPrimary = normalize_rel('images/' . $folder . '/' . $primaryFile);
            }
        }

        if ($newPrimary === null && isset($firstImage[$pid])) {
            $newPrimary = $firstImage[$pid];
        }

        if (!empty($newPrimary)) {
            $updateStmt->bind_param('si', $newPrimary, $pid);
            if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                $stats['image_link_updated']++;
            }
        }
    }
}

$insertStmt->close();
$updateStmt->close();

$totalProducts = 0;
$withImageLink = 0;
$productImagesRows = 0;

$r1 = $conn->query("SELECT COUNT(*) AS c FROM products");
if ($r1 && ($row = $r1->fetch_assoc())) {
    $totalProducts = (int)$row['c'];
}
$r2 = $conn->query("SELECT COUNT(*) AS c FROM products WHERE image_link IS NOT NULL AND TRIM(image_link) <> ''");
if ($r2 && ($row = $r2->fetch_assoc())) {
    $withImageLink = (int)$row['c'];
}
$r3 = $conn->query("SELECT COUNT(*) AS c FROM product_images");
if ($r3 && ($row = $r3->fetch_assoc())) {
    $productImagesRows = (int)$row['c'];
}

safe_json([
    'success' => true,
    'database' => $dbName,
    'stats' => $stats,
    'totals' => [
        'products_total' => $totalProducts,
        'products_with_image_link' => $withImageLink,
        'product_images_rows' => $productImagesRows
    ]
]);
