<?php
/**
 * Directly delete all orders from database
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

try {
    // Get counts before deletion
    $orders_before = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
    $items_before = $conn->query("SELECT COUNT(*) as cnt FROM order_items")->fetch_assoc()['cnt'] ?? 0;
    
    // Delete in correct order (respecting foreign keys)
    $conn->query("DELETE FROM order_items WHERE 1=1");
    $items_deleted = $conn->affected_rows;
    
    $conn->query("DELETE FROM orders WHERE 1=1");
    $orders_deleted = $conn->affected_rows;
    
    // Verify deletion
    $orders_after = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
    $items_after = $conn->query("SELECT COUNT(*) as cnt FROM order_items")->fetch_assoc()['cnt'] ?? 0;
    
    echo "✅ DATABASE CLEANUP COMPLETE\n";
    echo "=====================================\n\n";
    echo "BEFORE:\n";
    echo "  Orders: $orders_before\n";
    echo "  Order Items: $items_before\n\n";
    echo "DELETED:\n";
    echo "  Orders: $orders_deleted\n";
    echo "  Order Items: $items_deleted\n\n";
    echo "AFTER:\n";
    echo "  Orders: $orders_after\n";
    echo "  Order Items: $items_after\n\n";
    
    if ($orders_after === 0 && $items_after === 0) {
        echo "✅ SUCCESS: All orders have been removed from the database!\n";
    } else {
        echo "⚠️ WARNING: Some records may remain in the database.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
