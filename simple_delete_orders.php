<?php
// Simple order deletion script
$mysqli = new mysqli("localhost", "poshy_user", "Poshy2026secure", "poshy_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get counts
$orders_count = $mysqli->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$items_count = $mysqli->query("SELECT COUNT(*) FROM order_items")->fetch_row()[0];

echo "Orders before deletion: $orders_count\n";
echo "Order items before deletion: $items_count\n\n";

// Delete data
$mysqli->query("DELETE FROM order_items");
$items_deleted = $mysqli->affected_rows;

$mysqli->query("DELETE FROM orders");
$orders_deleted = $mysqli->affected_rows;

echo "Order items deleted: $items_deleted\n";
echo "Orders deleted: $orders_deleted\n\n";

// Verify
$orders_after = $mysqli->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$items_after = $mysqli->query("SELECT COUNT(*) FROM order_items")->fetch_row()[0];

echo "Orders after deletion: $orders_after\n";
echo "Order items after deletion: $items_after\n";

if ($orders_after == 0 && $items_after == 0) {
    echo "\n✅ SUCCESS: All orders deleted!\n";
} else {
    echo "\n⚠️ Some records remain.\n";
}

$mysqli->close();
?>
