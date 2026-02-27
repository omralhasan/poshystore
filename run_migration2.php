<?php
/**
 * One-time migration: Add is_recommended and is_best_seller columns.
 * Access once via browser then DELETE this file.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: application/json');
$results = [];

// Add is_recommended column
$r1 = $conn->query("SHOW COLUMNS FROM products LIKE 'is_recommended'");
if ($r1->num_rows === 0) {
    $ok = $conn->query("ALTER TABLE products ADD COLUMN is_recommended TINYINT(1) NOT NULL DEFAULT 0 AFTER has_discount");
    $results['is_recommended'] = $ok ? 'added' : 'failed: ' . $conn->error;
} else {
    $results['is_recommended'] = 'already exists';
}

// Add is_best_seller column
$r2 = $conn->query("SHOW COLUMNS FROM products LIKE 'is_best_seller'");
if ($r2->num_rows === 0) {
    $ok = $conn->query("ALTER TABLE products ADD COLUMN is_best_seller TINYINT(1) NOT NULL DEFAULT 0 AFTER is_recommended");
    $results['is_best_seller'] = $ok ? 'added' : 'failed: ' . $conn->error;
} else {
    $results['is_best_seller'] = 'already exists';
}

// Auto-detect best sellers: top 5 by order count
$conn->query("UPDATE products SET is_best_seller = 0");
$conn->query("UPDATE products p 
    JOIN (SELECT product_id, SUM(quantity) as total_sold 
          FROM order_items 
          GROUP BY product_id 
          ORDER BY total_sold DESC 
          LIMIT 5) top 
    ON p.id = top.product_id 
    SET p.is_best_seller = 1");
$results['auto_best_sellers'] = 'set top 5 products by sales';

echo json_encode([
    'success' => true,
    'results' => $results,
    'message' => 'Migration complete. DELETE this file now.'
], JSON_PRETTY_PRINT);
