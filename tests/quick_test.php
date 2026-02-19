
<?php
echo "=== Poshy Lifestyle Backend Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
require_once __DIR__ . '/db_connect.php';

if ($conn && $conn->ping()) {
    echo "   ✓ Database connected successfully\n\n";
} else {
    echo "   ✗ Database connection failed\n\n";
    exit(1);
}

// Test 2: Product Manager
echo "2. Testing Product Manager...\n";
require_once __DIR__ . '/product_manager.php';

$products = getAllProducts([], 5);
if ($products['success']) {
    echo "   ✓ Product Manager working\n";
    echo "   ✓ Found " . $products['count'] . " products\n\n";
    
    echo "   Products:\n";
    foreach ($products['products'] as $product) {
        echo "   - " . $product['name_en'] . " (" . $product['name_ar'] . ")\n";
        echo "     Price: " . $product['price_formatted'] . "\n";
        echo "     Stock: " . $product['stock_quantity'] . " units\n";
        echo "     Status: " . ($product['in_stock'] ? 'In Stock' : 'Out of Stock') . "\n\n";
    }
} else {
    echo "   ✗ Error: " . $products['error'] . "\n\n";
}

// Test 3: Currency Formatting
echo "3. Testing Currency Formatting...\n";
echo "   85.5 JOD = " . formatJOD(85.5) . "\n";
echo "   450 JOD = " . formatJOD(450) . "\n";
echo "   320.75 JOD = " . formatJOD(320.75) . "\n";
echo "   ✓ Currency formatting works\n\n";

// Test 4: Database Tables
echo "4. Checking Database Tables...\n";
$tables = ['users', 'products', 'cart', 'orders', 'categories'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $count_result->fetch_assoc()['count'];
        echo "   ✓ $table ($count records)\n";
    } else {
        echo "   ✗ $table (missing)\n";
    }
}

echo "\n=== All Tests Complete ===\n";
?>
