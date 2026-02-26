<?php
/**
 * Verify Order Deletion
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

if (!$conn) {
    die("❌ Database connection failed\n");
}

// Check counts
$orders = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
$items = $conn->query("SELECT COUNT(*) AS cnt FROM order_items")->fetch_assoc()['cnt'];

echo "✓ Verification after deletion:\n";
echo "  Orders: $orders\n";
echo "  Order Items: $items\n";

if ($orders == 0 && $items == 0) {
    echo "\n✅ CONFIRMED: All orders successfully removed!\n";
} else {
    echo "\n❌ ERROR: Orders still exist\n";
}

// Get a summary of other tables
$customers = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
$products = $conn->query("SELECT COUNT(*) AS cnt FROM products")->fetch_assoc()['cnt'];

echo "\n📊 System Status:\n";
echo "  Customers: $customers\n";
echo "  Products: $products\n";
?>
