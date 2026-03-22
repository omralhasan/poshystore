<?php
/**
 * ─────────────────────────────────────────────────────────────────────────
 *  Poshy Store — Product Image Scanner
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  Scans the `products` table and identifies products whose images are
 *  missing. A product is considered "missing" when:
 *    1. The `image_link` column is NULL or empty, OR
 *    2. The referenced file does not exist on disk.
 *
 *  For every missing product the script assigns a configurable placeholder
 *  image path as a fallback.
 *
 *  Usage (CLI):
 *    php scan_product_images.php
 *
 *  Usage (Browser):
 *    https://poshystore.com/scan_product_images.php?token=poshy_scan_2026
 *
 *  Optional flags:
 *    --fix        Also UPDATE the database, setting image_link to the
 *                 placeholder for every product that is missing an image.
 *    --json       Output results as JSON instead of a human-readable table.
 *
 *  Browser params:
 *    ?fix=1       Same as --fix
 *    ?format=json Same as --json
 *
 *  @author  Poshy Store Dev Team
 *  @version 1.1
 * ─────────────────────────────────────────────────────────────────────────
 */

// ── Detect environment ─────────────────────────────────────────────────
$is_cli = (php_sapi_name() === 'cli');

// ── Auth (browser only) ────────────────────────────────────────────────
if (!$is_cli) {
    if (($_GET['token'] ?? '') !== 'poshy_scan_2026') {
        http_response_code(403);
        echo "Forbidden — supply ?token=poshy_scan_2026";
        exit;
    }
}

// ── Bootstrap ──────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// ── Configuration ──────────────────────────────────────────────────────
// The directory on disk where product images are stored.
// We check multiple possible locations that Poshy Store uses.
$image_base_dirs = [
    __DIR__ . '/uploads/products',   // Primary: /uploads/products/
    __DIR__ . '/images',             // Legacy:  /images/ (product name folders)
];

// Placeholder image served when a product has no valid image.
$placeholder_path = '/uploads/products/placeholder.webp';

// ── Parse flags ────────────────────────────────────────────────────────
if ($is_cli) {
    $do_fix    = in_array('--fix', $argv ?? []);
    $as_json   = in_array('--json', $argv ?? []);
} else {
    $do_fix    = isset($_GET['fix']) && $_GET['fix'] === '1';
    $as_json   = isset($_GET['format']) && $_GET['format'] === 'json';
}

// ── Set content type ───────────────────────────────────────────────────
if (!$is_cli) {
    header('Content-Type: ' . ($as_json ? 'application/json' : 'text/plain') . '; charset=utf-8');
}

// ── Helper: Check if image file exists on disk ─────────────────────────
/**
 * Given an image_link value from the database, check whether the referenced
 * file actually exists on the server's filesystem.
 *
 * Handles:
 *   - Absolute URLs  (https://poshystore.com/images/Foo/img.jpg → strip domain)
 *   - Relative paths (/images/Foo/img.jpg or uploads/products/foo.webp)
 *   - Product-name folders in /images/<ProductName>/
 *
 * @param  string $image_link  Value from the DB column
 * @param  string $product_name  Product name (for folder-based lookup)
 * @return bool
 */
function imageExistsOnDisk(string $image_link, string $product_name): bool
{
    global $image_base_dirs;
    $root = rtrim(ROOT_DIR, '/');

    // 1. Direct path check — strip domain if present
    $path = $image_link;
    $path = preg_replace('#^https?://[^/]+#i', '', $path); // strip domain
    $path = ltrim($path, '/');

    // Check from project root
    if ($path !== '' && file_exists($root . '/' . $path)) {
        return true;
    }

    // 2. Check each base directory directly
    foreach ($image_base_dirs as $base_dir) {
        $filename = basename($image_link);
        if ($filename !== '' && file_exists($base_dir . '/' . $filename)) {
            return true;
        }
    }

    // 3. Folder-based lookup: /images/<ProductName>/ contains at least one image
    $images_dir = $root . '/images';
    if (is_dir($images_dir . '/' . $product_name)) {
        $files = glob($images_dir . '/' . $product_name . '/*.{jpg,jpeg,png,webp,gif,avif}', GLOB_BRACE);
        if (!empty($files)) {
            return true;
        }
    }

    return false;
}

// ── Fetch all products ─────────────────────────────────────────────────
$sql = "SELECT id, name_en, name_ar, image_link, price_jod, stock_quantity
        FROM products
        ORDER BY id ASC";

$result = $conn->query($sql);

if (!$result) {
    $error = "Query failed: " . $conn->error;
    if ($as_json) {
        echo json_encode(['success' => false, 'error' => $error]);
    } else {
        echo $error . "\n";
    }
    exit(1);
}

// ── Scan & classify ────────────────────────────────────────────────────
$missing  = [];  // Products with missing images
$valid    = [];  // Products with valid images
$total    = 0;

while ($row = $result->fetch_assoc()) {
    $total++;
    $id          = (int) $row['id'];
    $name_en     = $row['name_en'] ?? '(unnamed)';
    $name_ar     = $row['name_ar'] ?? '';
    $image_link  = trim($row['image_link'] ?? '');
    $price       = $row['price_jod'];
    $stock       = (int) $row['stock_quantity'];

    $reason = '';

    if ($image_link === '' || $image_link === null) {
        // Case 1: image_link is empty or NULL
        $reason = 'image_link is empty';
    } elseif (!imageExistsOnDisk($image_link, $name_en)) {
        // Case 2: image_link is set but the file doesn't exist
        $reason = "file not found ({$image_link})";
    }

    if ($reason !== '') {
        $missing[] = [
            'id'               => $id,
            'name_en'          => $name_en,
            'name_ar'          => $name_ar,
            'current_image'    => $image_link ?: '(empty)',
            'reason'           => $reason,
            'price_jod'        => $price,
            'stock'            => $stock,
            'fallback_image'   => $placeholder_path,
        ];
    } else {
        $valid[] = [
            'id'       => $id,
            'name_en'  => $name_en,
            'image'    => $image_link,
        ];
    }
}

// ── Optional: Apply fix (update DB with placeholder) ───────────────────
$fixed_count = 0;
if ($do_fix && !empty($missing)) {
    $update_sql = "UPDATE products SET image_link = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);

    if ($stmt) {
        foreach ($missing as &$item) {
            $stmt->bind_param('si', $placeholder_path, $item['id']);
            if ($stmt->execute()) {
                $fixed_count++;
                $item['fixed'] = true;
            } else {
                $item['fixed'] = false;
                $item['fix_error'] = $stmt->error;
            }
        }
        unset($item);
        $stmt->close();
    }
}

// ── Build results ──────────────────────────────────────────────────────
$summary = [
    'scan_date'         => date('Y-m-d H:i:s T'),
    'total_products'    => $total,
    'with_valid_image'  => count($valid),
    'missing_image'     => count($missing),
    'coverage_pct'      => $total > 0 ? round((count($valid) / $total) * 100, 1) : 0,
    'placeholder_path'  => $placeholder_path,
];

if ($do_fix) {
    $summary['fixed_count'] = $fixed_count;
}

// ── Output ─────────────────────────────────────────────────────────────
if ($as_json) {
    // ── JSON output ────────────────────────────────────────────────────
    echo json_encode([
        'success'          => true,
        'summary'          => $summary,
        'missing_products' => $missing,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // ── Human-readable output ──────────────────────────────────────────
    $line = str_repeat('─', 80);

    echo "\n";
    echo "  ╔══════════════════════════════════════════════════════════════╗\n";
    echo "  ║          POSHY STORE — Product Image Scanner               ║\n";
    echo "  ╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "  Scan Date:        {$summary['scan_date']}\n";
    echo "  Total Products:   {$summary['total_products']}\n";
    echo "  Valid Images:     {$summary['with_valid_image']}\n";
    echo "  Missing Images:   {$summary['missing_image']}\n";
    echo "  Coverage:         {$summary['coverage_pct']}%\n";
    echo "  Placeholder:      {$summary['placeholder_path']}\n";

    if ($do_fix) {
        echo "  Fixed (DB update): {$summary['fixed_count']}\n";
    }

    echo "\n{$line}\n";
    echo "  PRODUCTS MISSING IMAGES\n";
    echo "{$line}\n\n";

    if (empty($missing)) {
        echo "  ✅  All products have valid images. Nothing to fix!\n\n";
    } else {
        // Table header
        echo sprintf(
            "  %-6s %-40s %-30s %s\n",
            'ID', 'Product Name (EN)', 'Reason', 'Fallback'
        );
        echo "  " . str_repeat('─', 6) . " " . str_repeat('─', 40) . " "
           . str_repeat('─', 30) . " " . str_repeat('─', 30) . "\n";

        foreach ($missing as $item) {
            $name_display = mb_strlen($item['name_en']) > 38
                ? mb_substr($item['name_en'], 0, 35) . '...'
                : $item['name_en'];

            $reason_display = mb_strlen($item['reason']) > 28
                ? mb_substr($item['reason'], 0, 25) . '...'
                : $item['reason'];

            $fixed_marker = '';
            if ($do_fix) {
                $fixed_marker = isset($item['fixed']) && $item['fixed'] ? ' ✅' : ' ❌';
            }

            echo sprintf(
                "  %-6d %-40s %-30s %s%s\n",
                $item['id'],
                $name_display,
                $reason_display,
                $item['fallback_image'],
                $fixed_marker
            );
        }

        echo "\n";

        if (!$do_fix) {
            echo "  💡 Tip: Run with --fix (CLI) or ?fix=1 (browser) to update the\n";
            echo "     database and set image_link to the placeholder for all missing.\n\n";
        }
    }

    // ── Valid products summary ──────────────────────────────────────────
    echo "{$line}\n";
    echo "  PRODUCTS WITH VALID IMAGES ({$summary['with_valid_image']})\n";
    echo "{$line}\n\n";

    if (!empty($valid)) {
        foreach (array_slice($valid, 0, 20) as $v) {
            echo sprintf("  %-6d %s\n", $v['id'], $v['name_en']);
        }
        if (count($valid) > 20) {
            echo "  ... and " . (count($valid) - 20) . " more.\n";
        }
    }
    echo "\n";
}
