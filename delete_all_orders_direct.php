<?php
/**
 * Direct Order Deletion Script
 * Removes all orders and order_items from the database
 * No authentication required for CLI execution
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

if (!$conn) {
    die("❌ Database connection failed\n");
}

echo "🔄 Starting order deletion process...\n";

try {
    // First, check current counts
    $count_orders = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
    $count_items = $conn->query("SELECT COUNT(*) AS cnt FROM order_items")->fetch_assoc()['cnt'];
    
    echo "📊 Current Status:\n";
    echo "   - Orders: $count_orders\n";
    echo "   - Order Items: $count_items\n\n";
    
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // Delete order_items first (child records)
    echo "🗑️  Deleting order_items...\n";
    $result1 = $conn->query("DELETE FROM order_items");
    if ($result1) {
        echo "   ✅ All order_items deleted\n";
    } else {
        echo "   ❌ Failed to delete order_items: " . $conn->error . "\n";
    }
    
    // Delete orders
    echo "🗑️  Deleting orders...\n";
    $result2 = $conn->query("DELETE FROM orders");
    if ($result2) {
        echo "   ✅ All orders deleted\n";
    } else {
        echo "   ❌ Failed to delete orders: " . $conn->error . "\n";
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    // Verify deletion
    $count_orders_after = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
    $count_items_after = $conn->query("SELECT COUNT(*) AS cnt FROM order_items")->fetch_assoc()['cnt'];
    
    echo "\n✨ Final Status:\n";
    echo "   - Orders: $count_orders_after\n";
    echo "   - Order Items: $count_items_after\n";
    
    if ($count_orders_after === 0 && $count_items_after === 0) {
        echo "\n✅ SUCCESS! All orders have been deleted from the database.\n";
    } else {
        echo "\n⚠️  WARNING: Some records remain\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
