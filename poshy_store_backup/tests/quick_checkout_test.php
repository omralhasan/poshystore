<?php
/**
 * Quick Test - Create a new order to test order items display
 */

session_start();
require_once 'db_connect.php';
require_once 'auth_functions.php';
require_once 'cart_handler.php';
require_once 'product_manager.php';
require_once 'checkout.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ Please log in first at: <a href='../pages/auth/signin.php'>../pages/auth/signin.php</a>");
}

$user_id = $_SESSION['user_id'];

echo "<h2>🧪 Testing Order Items Display</h2>";

// Check current cart
$cart = viewCart($user_id);
echo "<h3>Current Cart:</h3>";
if ($cart['success'] && !empty($cart['cart_items'])) {
    echo "<ul>";
    foreach ($cart['cart_items'] as $item) {
        echo "<li>{$item['name_en']} - Qty: {$item['quantity']} - {$item['subtotal_formatted']}</li>";
    }
    echo "</ul>";
    echo "<p><strong>Total: {$cart['total_formatted']}</strong></p>";
    
    echo "<h3>Processing Checkout...</h3>";
    $result = processCheckout($user_id);
    
    if ($result['success']) {
        echo "<p>✅ Order placed successfully!</p>";
        echo "<p>Order ID: #{$result['order']['order_id']}</p>";
        echo "<p>Total: {$result['order']['total_amount_formatted']}</p>";
        echo "<p>Items: {$result['order']['item_count']}</p>";
        
        echo "<h3>📦 Order Items Saved:</h3>";
        echo "<ul>";
        foreach ($result['order']['items'] as $item) {
            echo "<li>{$item['product_name_en']} ({$item['product_name_ar']}) - Qty: {$item['quantity']} × " . formatJOD($item['price']) . " = " . formatJOD($item['subtotal']) . "</li>";
        }
        echo "</ul>";
        
        echo "<hr><p><strong>Now go to: <a href='my_orders.php' style='color: #667eea; font-size: 1.2rem;'>My Orders Page</a> to see the details!</strong></p>";
    } else {
        echo "<p>❌ Checkout failed: {$result['error']}</p>";
    }
    
} else {
    echo "<p>⚠️ Cart is empty. Adding some products first...</p>";
    
    // Add a couple products to cart
    $products = getAllProducts();
    if ($products['success'] && !empty($products['products'])) {
        $added = 0;
        foreach ($products['products'] as $product) {
            if ($added < 2 && $product['stock'] > 0) {
                $add_result = addToCart($user_id, $product['product_id'], 1);
                if ($add_result['success']) {
                    echo "<p>✅ Added {$product['name_en']} to cart</p>";
                    $added++;
                }
            }
        }
        
        if ($added > 0) {
            echo "<p>🔄 <a href='quick_checkout_test.php'>Refresh this page</a> to checkout</p>";
        }
    }
}
?>
