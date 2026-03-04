<?php
// Migration script: Add cost column to products and cost_per_item to order_items
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

echo "=== Running Cost Column Migration ===\n\n";

// 1. Check if cost column exists in products
$result = $conn->query("SHOW COLUMNS FROM products LIKE 'cost'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE products ADD COLUMN cost DECIMAL(10,3) DEFAULT NULL COMMENT 'Actual cost of product for profit calculation' AFTER supplier_cost";
    if ($conn->query($sql)) {
        echo "✓ Added 'cost' column to products table\n";
    } else {
        echo "✗ Failed to add 'cost' column: " . $conn->error . "\n";
    }
} else {
    echo "~ 'cost' column already exists in products table\n";
}

// 2. Migrate: set cost = supplier_cost for existing products where cost is NULL
$sql = "UPDATE products SET cost = supplier_cost WHERE cost IS NULL AND supplier_cost IS NOT NULL";
if ($conn->query($sql)) {
    echo "✓ Migrated " . $conn->affected_rows . " products: cost = supplier_cost\n";
} else {
    echo "✗ Failed to migrate costs: " . $conn->error . "\n";
}

// 3. Check if cost_per_item column exists in order_items
$result = $conn->query("SHOW COLUMNS FROM order_items LIKE 'cost_per_item'");
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE order_items ADD COLUMN cost_per_item DECIMAL(10,3) DEFAULT NULL COMMENT 'Cost per item at time of order' AFTER price_per_item";
    if ($conn->query($sql)) {
        echo "✓ Added 'cost_per_item' column to order_items table\n";
    } else {
        echo "✗ Failed to add 'cost_per_item' column: " . $conn->error . "\n";
    }
} else {
    echo "~ 'cost_per_item' column already exists in order_items table\n";
}

// 4. Populate cost_per_item for existing order_items
$sql = "UPDATE order_items oi JOIN products p ON oi.product_id = p.id SET oi.cost_per_item = p.cost WHERE oi.cost_per_item IS NULL AND p.cost IS NOT NULL";
if ($conn->query($sql)) {
    echo "✓ Populated cost_per_item for " . $conn->affected_rows . " existing order items\n";
} else {
    echo "✗ Failed to populate cost_per_item: " . $conn->error . "\n";
}

// 5. Show verification
echo "\n=== Verification ===\n";
$result = $conn->query("SELECT id, name_en, price_jod, supplier_cost, cost FROM products LIMIT 5");
echo "Sample products (first 5):\n";
echo str_pad("ID", 5) . str_pad("Name", 35) . str_pad("Customer Price", 16) . str_pad("Supplier Price", 16) . str_pad("Cost", 10) . "\n";
echo str_repeat("-", 82) . "\n";
while ($row = $result->fetch_assoc()) {
    echo str_pad($row['id'], 5) . str_pad(substr($row['name_en'], 0, 33), 35) . str_pad($row['price_jod'] ?? '-', 16) . str_pad($row['supplier_cost'] ?? '-', 16) . str_pad($row['cost'] ?? '-', 10) . "\n";
}

$result = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as with_cost FROM products");
$stats = $result->fetch_assoc();
echo "\nTotal products: {$stats['total']}, With cost set: {$stats['with_cost']}\n";

$result = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN cost_per_item IS NOT NULL THEN 1 ELSE 0 END) as with_cost FROM order_items");
$stats = $result->fetch_assoc();
echo "Total order items: {$stats['total']}, With cost_per_item set: {$stats['with_cost']}\n";

echo "\n=== Migration Complete ===\n";
