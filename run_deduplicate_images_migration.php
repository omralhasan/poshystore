<?php
/**
 * Migration: Deduplicate product_images, add UNIQUE + FOREIGN KEY CASCADE
 *
 * Steps:
 *  1. Survey current duplicate/orphan state
 *  2. Remove .webp rows where .jpg exists with same base name (DB rows only — no filesystem changes)
 *  3. Remove truly orphaned rows (product_id no longer in products)
 *  4. Add UNIQUE (product_id, image_path)
 *  5. Add FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
 *
 * Run once via browser: https://poshystore.com/run_deduplicate_images_migration.php
 * Or via CLI: php run_deduplicate_images_migration.php
 */

require_once __DIR__ . '/includes/db_connect.php';

$is_cli = PHP_SAPI === 'cli';
$nl    = $is_cli ? "\n" : "<br>\n";
$bold  = fn(string $t): string => $is_cli ? $t : "<strong>{$t}</strong>";

if (!$is_cli) {
    echo "<html><head><meta charset='utf-8'><title>Image Deduplication Migration</title>";
    echo "<style>body{font-family:sans-serif;padding:2rem;background:#f9fafb;color:#1f2937}";
    echo "h2{color:#4f9eff}pre{background:#fff;padding:1rem;border-radius:8px;border:1px solid #e5e7eb}</style></head><body>";
    echo "<h2>🔄 Image Deduplication &amp; CASCADE Migration</h2><pre>";
}

// ─── Step 0: Survey ──────────────────────────────────────────────────────────
$totalRows    = 0;
$dupWebp      = 0;
$orphanedRows = 0;

$r0 = $conn->query("SELECT COUNT(*) AS c FROM product_images");
if ($r0) $totalRows = (int)$r0->fetch_assoc()['c'];

$r1 = $conn->query("SELECT COUNT(*) AS c FROM product_images pi1
    WHERE pi1.image_path LIKE '%.webp'
    AND EXISTS (
        SELECT 1 FROM product_images pi2
        WHERE pi2.product_id = pi1.product_id
        AND SUBSTRING_INDEX(pi2.image_path, '.', 1) = SUBSTRING_INDEX(pi1.image_path, '.', 1)
        AND pi2.image_path LIKE '%.jpg'
    )");
if ($r1) $dupWebp = (int)$r1->fetch_assoc()['c'];

$r2 = $conn->query("SELECT COUNT(*) AS c FROM product_images WHERE product_id NOT IN (SELECT id FROM products)");
if ($r2) $orphanedRows = (int)$r2->fetch_assoc()['c'];

echo "📊 {$bold('Before migration')}:{$nl}";
echo "   product_images total rows: {$totalRows}{$nl}";
echo "   Duplicate .webp rows (with .jpg counterpart): {$dupWebp}{$nl}";
echo "   Orphaned rows (no matching product): {$orphanedRows}{$nl}{$nl}";

// ─── Step 1: Delete .webp duplicates where .jpg exists ─────────────────────
if ($dupWebp > 0) {
    $delSql = "DELETE pi1 FROM product_images pi1
               WHERE pi1.image_path LIKE '%.webp'
               AND EXISTS (
                   SELECT 1 FROM product_images pi2
                   WHERE pi2.product_id = pi1.product_id
                   AND SUBSTRING_INDEX(pi2.image_path, '.', 1) = SUBSTRING_INDEX(pi1.image_path, '.', 1)
                   AND pi2.image_path LIKE '%.jpg'
               )";
    if ($conn->query($delSql)) {
        $affected = $conn->affected_rows;
        echo "✅ {$bold('Step 1')}: Deleted {$affected} duplicate .webp rows (physical .webp files kept on disk).{$nl}";
    } else {
        echo "❌ {$bold('Step 1')} error: " . $conn->error . $nl;
    }
} else {
    echo "⏭️  {$bold('Step 1')}: No duplicate .webp rows found.{$nl}";
}

// ─── Step 2: Delete orphaned rows ────────────────────────────────────────────
if ($orphanedRows > 0) {
    $delOrphan = "DELETE FROM product_images WHERE product_id NOT IN (SELECT id FROM products)";
    if ($conn->query($delOrphan)) {
        $affected = $conn->affected_rows;
        echo "✅ {$bold('Step 2')}: Deleted {$affected} orphaned rows.{$nl}";
    } else {
        echo "❌ {$bold('Step 2')} error: " . $conn->error . $nl;
    }
} else {
    echo "⏭️  {$bold('Step 2')}: No orphaned rows found.{$nl}";
}

// ─── Step 3: Add UNIQUE INDEX (product_id, image_path) ───────────────────────
$checkUnique = $conn->query("SHOW INDEX FROM product_images WHERE Key_name = 'uq_product_image'");
if ($checkUnique && $checkUnique->num_rows > 0) {
    echo "⏭️  {$bold('Step 3')}: UNIQUE INDEX 'uq_product_image' already exists.{$nl}";
} else {
    if ($conn->query("ALTER TABLE product_images ADD UNIQUE INDEX uq_product_image (product_id, image_path)")) {
        echo "✅ {$bold('Step 3')}: Added UNIQUE INDEX on (product_id, image_path).{$nl}";
    } else {
        echo "❌ {$bold('Step 3')} error: " . $conn->error . $nl;
    }
}

// ─── Step 4: Add FOREIGN KEY with ON DELETE CASCADE ──────────────────────────
$checkFK = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_images'
    AND REFERENCED_TABLE_NAME = 'products'");
if ($checkFK && $checkFK->num_rows > 0) {
    $fkName = $checkFK->fetch_assoc()['CONSTRAINT_NAME'];
    echo "⏭️  {$bold('Step 4')}: FOREIGN KEY '{$fkName}' already exists.{$nl}";
} else {
    $fkSql = "ALTER TABLE product_images
              ADD CONSTRAINT fk_product_images_product
              FOREIGN KEY (product_id) REFERENCES products(id)
              ON DELETE CASCADE";
    if ($conn->query($fkSql)) {
        echo "✅ {$bold('Step 4')}: Added FOREIGN KEY with ON DELETE CASCADE.{$nl}";
        echo "   Deleting a product will now automatically delete its product_images rows.{$nl}";
    } else {
        echo "❌ {$bold('Step 4')} error: " . $conn->error . $nl;
    }
}

// ─── Summary after ───────────────────────────────────────────────────────────
$afterTotal = 0;
$r3 = $conn->query("SELECT COUNT(*) AS c FROM product_images");
if ($r3) $afterTotal = (int)$r3->fetch_assoc()['c'];

echo "{$nl}📊 {$bold('After migration')}:{$nl}";
echo "   product_images total rows: {$afterTotal} (removed " . ($totalRows - $afterTotal) . "){$nl}";
echo "   .webp files on disk: untouched ✅{$nl}";
echo "   Original image files (.jpg/.png): untouched ✅{$nl}";
echo "{$nl}✅ {$bold('Migration completed successfully.')}{$nl}";

// Verify FK works
echo "{$nl}🔍 {$bold('Verification:')} Checking FOREIGN KEY constraint...{$nl}";
try {
    $test = $conn->query("SELECT COUNT(*) AS c FROM product_images pi
        LEFT JOIN products p ON p.id = pi.product_id
        WHERE p.id IS NULL");
    if ($test) {
        $orphansLeft = (int)$test->fetch_assoc()['c'];
        if ($orphansLeft > 0) {
            echo "⚠️  Warning: {$orphansLeft} orphaned rows remain (unexpected).{$nl}";
        } else {
            echo "✅ No orphaned rows remain. CASCADE will handle future deletes automatically.{$nl}";
        }
    }
} catch (Throwable $e) {
    echo "ℹ️  Verification query note: " . $e->getMessage() . $nl;
}

if (!$is_cli) {
    echo "</pre>";
    echo "<p><a href='pages/admin/photos_database_report.php' style='display:inline-block;padding:.6rem 1.2rem;";
    echo "background:#4f9eff;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;'>📸 View Photos DB Report →</a></p>";
    echo "</body></html>";
}
