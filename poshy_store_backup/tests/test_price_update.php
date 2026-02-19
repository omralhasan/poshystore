<?php
/**
 * Test Price Update Function
 */

require_once 'db_connect.php';
require_once 'product_manager.php';

echo "<h2>Testing Price Update</h2>";

// Get first product
$products = getAllProducts(['in_stock' => false], 1);
if ($products['success'] && !empty($products['products'])) {
    $product = $products['products'][0];
    
    echo "<h3>Product Details:</h3>";
    echo "ID: {$product['product_id']}<br>";
    echo "Name: {$product['name_en']}<br>";
    echo "Current Price: {$product['price_formatted']}<br>";
    echo "Current Price (raw): {$product['price']}<br>";
    
    $new_price = $product['price'] + 10.500;
    echo "<br><strong>Testing update to: " . formatJOD($new_price) . "</strong><br><br>";
    
    $result = updateProductPrice($product['product_id'], $new_price);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ SUCCESS! Price updated.</p>";
        
        // Verify the change
        $updated = getProductById($product['product_id']);
        if ($updated['success']) {
            echo "<p>New price in database: {$updated['product']['price_formatted']}</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ FAILED: {$result['error']}</p>";
    }
} else {
    echo "No products found to test.";
}
?>
