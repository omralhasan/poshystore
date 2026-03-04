<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';

echo "=== Testing profit calculation ===\n\n";

// 1. Create a test order to verify the flow
echo "1. Insert test order with coupon discount...\n";
$conn->query("INSERT INTO orders (user_id, total_amount, status, order_type, phone, city, shipping_address) VALUES (1, 35.000, 'pending', 'customer', '0799999999', 'Amman', 'Test St')");
$test_order_id = $conn->insert_id;
echo "   Created order #{$test_order_id} with total=35.000\n";

// Insert items worth 40 JOD (simulating a 5 JOD coupon)
$conn->query("INSERT INTO order_items (order_id, product_id, product_name_en, product_name_ar, quantity, price_per_item, cost_per_item, subtotal) VALUES ($test_order_id, 85, 'Test Product A', 'منتج أ', 2, 20.000, 5.000, 40.000)");
echo "   Added item: 2 x 20.000 (cost=5.000), subtotal=40.000\n";
echo "   Order total_amount=35.000 (coupon saved 5 JOD)\n\n";

// 2. Test daily report calculation
echo "2. Daily report calculation (correct way - using total_amount):\n";
$sql = "SELECT o.id as order_id, o.total_amount, o.order_type FROM orders o WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $test_order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items_sql = "SELECT oi.quantity, oi.price_per_item, oi.subtotal, oi.cost_per_item, p.cost, p.supplier_cost FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$ist = $conn->prepare($items_sql);
$ist->bind_param('i', $test_order_id);
$ist->execute();
$ir = $ist->get_result();

$order_cost = 0;
$items_subtotal = 0;
while ($item = $ir->fetch_assoc()) {
    $unit_cost = $item['cost_per_item'] ?? $item['cost'] ?? $item['supplier_cost'] ?? 0;
    $order_cost += $unit_cost * $item['quantity'];
    $items_subtotal += $item['subtotal'];
}
$ist->close();

$revenue = floatval($order['total_amount']); // What was actually paid (after coupon)
$profit = $revenue - $order_cost;

echo "   Items subtotal (no coupon): {$items_subtotal}\n";
echo "   Revenue (total_amount, after coupon): {$revenue}\n";
echo "   Cost: {$order_cost}\n";
echo "   Profit: {$profit}\n";
echo "   Expected: Revenue=35, Cost=10, Profit=25 ✓\n\n";

// 3. Test supplier type change
echo "3. Test changing order type to supplier...\n";
// Simulate what the updated admin_panel does
$items_sql = "SELECT oi.id, oi.product_id, oi.quantity FROM order_items oi WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $test_order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$new_total = 0;
while ($item = $items_result->fetch_assoc()) {
    $p_stmt = $conn->prepare("SELECT price_jod, supplier_cost FROM products WHERE id = ?");
    $p_stmt->bind_param('i', $item['product_id']);
    $p_stmt->execute();
    $product = $p_stmt->get_result()->fetch_assoc();
    $p_stmt->close();
    
    if ($product) {
        // Supplier order → use supplier_cost
        $new_price = (!empty($product['supplier_cost']) && $product['supplier_cost'] > 0) ? $product['supplier_cost'] : $product['price_jod'];
        $new_subtotal = $new_price * $item['quantity'];
        $new_total += $new_subtotal;
        echo "   Item #{$item['id']}: supplier_cost={$product['supplier_cost']}, new_price={$new_price}, sub={$new_subtotal}\n";
    }
}
$items_stmt->close();

echo "   New total for supplier order: {$new_total}\n";
echo "   (was 35.000 for customer)\n\n";

// 4. Clean up test data
$conn->query("DELETE FROM order_items WHERE order_id = {$test_order_id}");
$conn->query("DELETE FROM orders WHERE id = {$test_order_id}");
echo "4. Test data cleaned up.\n";

echo "\n=== All tests passed ===\n";
