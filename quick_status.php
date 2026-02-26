<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Simple count check
$orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$products = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$categories = $conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];

echo "Status Check:\n";
echo "Orders: $orders\n";
echo "Products: $products\n";
echo "Categories: $categories\n";
?>
