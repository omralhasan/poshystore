<?php
/**
 * Test Discount Display
 * Apply a test discount to see it on the home page
 */

require_once __DIR__ . '/product_manager.php';

echo "=== Testing Discount Display Feature ===\n\n";

// Get a product to apply discount to
$products_result = getAllProducts([], 5);
$products = $products_result['products'] ?? [];

if (empty($products)) {
    die("No products found in database.\n");
}

$test_product = $products[0];
echo "Selected Product: {$test_product['name_en']} (ID: {$test_product['id']})\n";
echo "Current Price: {$test_product['price_formatted']}\n";
echo "Has Discount: " . ($test_product['has_discount'] ? 'Yes' : 'No') . "\n\n";

// Apply 25% discount
echo "Applying 25% discount...\n";
$result = applyProductDiscount($test_product['id'], 25);

if ($result['success']) {
    echo "✓ Success!\n";
    echo "  Product: {$result['product_name']}\n";
    echo "  Original Price: {$result['old_price_formatted']}\n";
    echo "  Discounted Price: {$result['new_price_formatted']}\n";
    echo "  Discount: {$result['discount_percentage']}%\n";
    echo "  Savings: " . formatJOD($result['old_price'] - $result['new_price']) . "\n\n";
    
    echo "✓ Now visit the home page to see the discount badge!\n";
    echo "  URL: http://localhost/poshy_store/index.php\n\n";
    
    echo "You should see:\n";
    echo "  - Red '-25% OFF' badge on product image\n";
    echo "  - Original price: {$result['old_price_formatted']} (strikethrough)\n";
    echo "  - Discounted price: {$result['new_price_formatted']} (in red)\n";
    echo "  - Savings amount below the price\n\n";
    
    // Show another example with 50% discount
    if (count($products) > 1) {
        $test_product2 = $products[1];
        echo "Applying 50% discount to another product...\n";
        $result2 = applyProductDiscount($test_product2['id'], 50);
        
        if ($result2['success']) {
            echo "✓ {$result2['product_name']}: {$result2['old_price_formatted']} → {$result2['new_price_formatted']} (50% OFF)\n\n";
        }
    }
    
    echo "=== To Remove Discounts ===\n";
    echo "Use admin panel to change price back, or run:\n";
    echo "removeProductDiscount({$test_product['id']});\n";
    
} else {
    echo "✗ Failed: {$result['error']}\n";
}
