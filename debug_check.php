<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

echo "=== orders table ===\n";
$r = $conn->query("DESCRIBE orders");
while ($row = $r->fetch_assoc()) echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Default'] . "\n";

echo "\n=== order_items table ===\n";
$r = $conn->query("DESCRIBE order_items");
while ($row = $r->fetch_assoc()) echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Default'] . "\n";

echo "\n=== products pricing columns ===\n";
$r = $conn->query("SELECT id, name_en, price_jod, supplier_cost, cost FROM products LIMIT 5");
while ($row = $r->fetch_assoc()) echo "P#{$row['id']}: {$row['name_en']} | cust={$row['price_jod']} sup={$row['supplier_cost']} cost={$row['cost']}\n";

echo "\n=== Sample orders ===\n";
$r = $conn->query("SELECT id, total_amount, order_type, status FROM orders ORDER BY id DESC LIMIT 5");
if ($r && $r->num_rows > 0) {
    while ($row = $r->fetch_assoc()) echo "Order #{$row['id']}: total={$row['total_amount']}, type={$row['order_type']}, status={$row['status']}\n";
} else {
    echo "No orders found\n";
}

echo "\n=== Sample order_items ===\n";
$r = $conn->query("SELECT oi.order_id, oi.product_name_en, oi.quantity, oi.price_per_item, oi.cost_per_item, oi.subtotal FROM order_items oi ORDER BY oi.order_id DESC LIMIT 10");
if ($r && $r->num_rows > 0) {
    while ($row = $r->fetch_assoc()) echo "Order #{$row['order_id']}: {$row['product_name_en']} x{$row['quantity']} price={$row['price_per_item']} cost={$row['cost_per_item']} sub={$row['subtotal']}\n";
} else {
    echo "No order items found\n";
}

echo "\n=== coupons table ===\n";
$r = $conn->query("DESCRIBE coupons");
if ($r) { while ($row = $r->fetch_assoc()) echo $row['Field'] . ' | ' . $row['Type'] . "\n"; }
else echo "No coupons table\n";

echo "\n=== Test daily reports query ===\n";
$start = '2026-01-01';
$end = '2026-12-31';
$sql = "SELECT o.id as order_id, o.total_amount, o.order_type FROM orders o WHERE DATE(o.order_date) BETWEEN ? AND ? ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();
echo "Orders in 2026: " . $result->num_rows . "\n";
while ($o = $result->fetch_assoc()) {
    echo "  Order #{$o['order_id']}: total={$o['total_amount']}, type={$o['order_type']}\n";
    
    $items_sql = "SELECT oi.quantity, oi.price_per_item, oi.subtotal, oi.cost_per_item, p.cost, p.supplier_cost FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $ist = $conn->prepare($items_sql);
    $ist->bind_param('i', $o['order_id']);
    $ist->execute();
    $ir = $ist->get_result();
    while ($item = $ir->fetch_assoc()) {
        $unit_cost = $item['cost_per_item'] ?? $item['cost'] ?? $item['supplier_cost'] ?? 0;
        echo "    item: price={$item['price_per_item']} sub={$item['subtotal']} cost_snap={$item['cost_per_item']} cost={$item['cost']} sup_cost={$item['supplier_cost']} => unit_cost={$unit_cost}\n";
    }
    $ist->close();
}
$stmt->close();

echo "\nDone.\n";
