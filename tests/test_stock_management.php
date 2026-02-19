<?php
/**
 * Test Stock Management
 * Tests stock reduction during order and restoration on cancellation
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/product_manager.php';
require_once __DIR__ . '/checkout.php';

// Get a product to test
$test_sql = "SELECT id, name_en, stock_quantity FROM products WHERE stock_quantity > 10 LIMIT 1";
$test_result = $conn->query($test_sql);

if ($test_result->num_rows === 0) {
    die("No products with stock > 10 found for testing\n");
}

$product = $test_result->fetch_assoc();
$product_id = $product['id'];
$initial_stock = $product['stock_quantity'];

echo "=== Stock Management Test ===\n";
echo "Product: {$product['name_en']} (ID: {$product_id})\n";
echo "Initial Stock: {$initial_stock} units\n\n";

// Test 1: Reduce stock (simulate order)
echo "Test 1: Reducing stock by 5 units...\n";
$reduce_result = updateStock($product_id, -5, true);
if ($reduce_result['success']) {
    echo "✓ Old Stock: {$reduce_result['old_stock']}\n";
    echo "✓ New Stock: {$reduce_result['new_stock']}\n";
} else {
    echo "✗ Failed: {$reduce_result['error']}\n";
}

// Check current stock in database
$check_sql = "SELECT stock_quantity FROM products WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$current_stock = $check_result->fetch_assoc()['stock_quantity'];
$check_stmt->close();
echo "✓ Current Stock in DB: {$current_stock} units\n\n";

// Test 2: Create a test order
echo "Test 2: Creating test order...\n";
$order_sql = "INSERT INTO orders (user_id, total_amount, status) VALUES (1, 50.000, 'pending')";
if ($conn->query($order_sql)) {
    $test_order_id = $conn->insert_id;
    echo "✓ Test order created (ID: {$test_order_id})\n";
    
    // Add order item
    $item_sql = "INSERT INTO order_items (order_id, product_id, product_name_en, product_name_ar, quantity, price_per_item, subtotal) 
                 VALUES (?, ?, ?, '', 5, 10.000, 50.000)";
    $item_stmt = $conn->prepare($item_sql);
    $item_stmt->bind_param('iis', $test_order_id, $product_id, $product['name_en']);
    if ($item_stmt->execute()) {
        echo "✓ Order item added (5 units)\n\n";
    }
    $item_stmt->close();
    
    // Test 3: Cancel order and restore stock
    echo "Test 3: Cancelling order to restore stock...\n";
    $cancel_result = updateOrderStatus($test_order_id, 'cancelled');
    if ($cancel_result['success']) {
        echo "✓ Order cancelled\n";
        if (isset($cancel_result['stock_restored'])) {
            echo "✓ Stock restored: YES\n";
            foreach ($cancel_result['restored_items'] as $item) {
                echo "  - Product {$item['product_id']}: +{$item['quantity_restored']} units\n";
            }
        } else {
            echo "✗ Stock restored: NO\n";
        }
    } else {
        echo "✗ Failed: {$cancel_result['error']}\n";
    }
    
    // Check final stock
    $check_stmt2 = $conn->prepare($check_sql);
    $check_stmt2->bind_param('i', $product_id);
    $check_stmt2->execute();
    $check_result2 = $check_stmt2->get_result();
    $final_stock = $check_result2->fetch_assoc()['stock_quantity'];
    $check_stmt2->close();
    
    echo "✓ Final Stock in DB: {$final_stock} units\n\n";
    
    // Summary
    echo "=== Summary ===\n";
    echo "Initial Stock: {$initial_stock}\n";
    echo "After Reduction (-5): {$current_stock}\n";
    echo "After Cancellation (should restore +5): {$final_stock}\n";
    
    if ($final_stock == $current_stock + 5) {
        echo "✓✓✓ SUCCESS: Stock properly restored!\n";
    } else {
        echo "✗✗✗ FAILURE: Stock NOT properly restored!\n";
        echo "Expected: " . ($current_stock + 5) . ", Got: {$final_stock}\n";
    }
    
    // Cleanup
    echo "\nCleaning up test data...\n";
    $conn->query("DELETE FROM order_items WHERE order_id = {$test_order_id}");
    $conn->query("DELETE FROM orders WHERE id = {$test_order_id}");
    
    // Restore original stock
    $restore_sql = "UPDATE products SET stock_quantity = ? WHERE id = ?";
    $restore_stmt = $conn->prepare($restore_sql);
    $restore_stmt->bind_param('ii', $initial_stock, $product_id);
    $restore_stmt->execute();
    $restore_stmt->close();
    echo "✓ Test data cleaned up and stock restored to initial value\n";
    
} else {
    echo "✗ Failed to create test order\n";
}
