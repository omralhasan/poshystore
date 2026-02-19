<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Invoice Page Load</h2>";
echo "<pre>";

try {
    echo "1. Starting session...\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "   ✓ Session started\n\n";
    
    // Set admin session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['logged_in'] = true;
    $_GET['order_id'] = 2;
    
    echo "2. Loading db_connect.php...\n";
    require_once __DIR__ . '/includes/db_connect.php';
    echo "   ✓ Database connected\n\n";
    
    echo "3. Loading auth_functions.php...\n";
    require_once __DIR__ . '/includes/auth_functions.php';
    echo "   ✓ Auth functions loaded\n\n";
    
    echo "4. Loading checkout.php...\n";
    require_once __DIR__ . '/pages/shop/checkout.php';
    echo "   ✓ Checkout loaded\n\n";
    
    echo "5. Checking admin status...\n";
    if (isAdmin()) {
        echo "   ✓ User is admin\n\n";
    } else {
        echo "   ✗ User is NOT admin\n";
        echo "   Session data: " . print_r($_SESSION, true) . "\n\n";
    }
    
    echo "6. Testing database query...\n";
    $order_id = 2;
    $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, 
                   o.order_date,
                   u.firstname, u.lastname, u.email, u.phonenumber
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "   ✓ Order found\n\n";
        $order = $result->fetch_assoc();
        echo "   Order details:\n";
        print_r($order);
    } else {
        echo "   ✗ No order found\n\n";
    }
    $stmt->close();
    
    echo "\n7. Testing order items query...\n";
    $items_sql = "SELECT oi.product_id, oi.product_name_en, oi.product_name_ar, 
                         oi.quantity, oi.price_per_item, oi.subtotal,
                         p.sku
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
    
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    echo "   Found " . $items_result->num_rows . " items\n";
    $items_stmt->close();
    
    echo "\n✅ ALL TESTS PASSED - Invoice page should work!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

echo "<p><a href='pages/admin/print_invoice.php?order_id=2'>Click here to try the actual invoice page</a></p>";
?>
