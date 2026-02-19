<?php
/**
 * Step-by-step Admin Panel Debug
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting admin panel debug...\n";

echo "1. session_start()...\n";
session_start();
echo "   ✓ Session started\n";

echo "2. Loading db_connect.php...\n";
require_once __DIR__ . '/../../includes/db_connect.php';
echo "   ✓ Database loaded\n";

echo "3. Loading auth_functions.php...\n";
require_once __DIR__ . '/../../includes/auth_functions.php';
echo "   ✓ Auth functions loaded\n";

echo "4. Loading checkout.php...\n";
require_once __DIR__ . '/../shop/checkout.php';
echo "   ✓ Checkout loaded\n";

echo "5. Loading product_manager.php...\n";
require_once __DIR__ . '/../../includes/product_manager.php';
echo "   ✓ Product manager loaded\n";

echo "6. Check isAdmin()...\n";
if (!isAdmin()) {
    echo "   ✗ NOT ADMIN - would redirect\n";
} else {
    echo "   ✓ Is admin\n";
}

echo "7. Calling getAllOrders()...\n";
try {
    $orders_result = getAllOrders(50, 0, null);
    $orders = $orders_result['orders'] ?? [];
    echo "   ✓ Got " . count($orders) . " orders\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "8. Calling getAllProducts()...\n";
try {
    $products_result = getAllProducts(['in_stock' => false], 100);
    $products = $products_result['products'] ?? [];
    echo "   ✓ Got " . count($products) . " products\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ ALL STEPS COMPLETED!\n";
echo "\nIf this works, the issue is in the HTML/JS part of admin_panel.php\n";
?>
