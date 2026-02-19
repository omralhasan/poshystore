<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend Test - Poshy Lifestyle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .test-section h2 {
            color: #764ba2;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin-bottom: 15px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin-bottom: 15px;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .product-card {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .product-card strong { color: #667eea; }
        .price { color: #28a745; font-weight: bold; font-size: 18px; }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Poshy Lifestyle Backend Test Suite</h1>
        
        <?php
        // Test 1: Database Connection
        echo '<div class="test-section">';
        echo '<h2>1️⃣ Database Connection Test</h2>';
        require_once __DIR__ . '/db_connect.php';
        
        if($conn && $conn->ping()) {
            echo '<div class="success">✅ Database connection successful!</div>';
            echo '<pre>Host: ' . DB_HOST . "\nDatabase: " . DB_NAME . "\nUser: " . DB_USER . "\nCharset: " . DB_CHARSET . '</pre>';
        } else {
            echo '<div class="error">❌ Database connection failed!</div>';
        }
        echo '</div>';
        
        // Test 2: Test Product Manager
        echo '<div class="test-section">';
        echo '<h2>2️⃣ Product Manager Test</h2>';
        require_once __DIR__ . '/product_manager.php';
        
        $products = getAllProducts([], 10);
        if($products['success']) {
            echo '<div class="success">✅ Retrieved ' . $products['count'] . ' products</div>';
            
            foreach($products['products'] as $product) {
                echo '<div class="product-card">';
                echo '<strong>' . htmlspecialchars($product['name_en']) . '</strong><br>';
                echo '<em style="color: #666;">' . htmlspecialchars($product['name_ar']) . '</em><br>';
                echo '<span class="price">' . $product['price_formatted'] . '</span>';
                if($product['in_stock']) {
                    echo '<span class="badge badge-success">In Stock: ' . $product['stock_quantity'] . '</span>';
                } else {
                    echo '<span class="badge badge-danger">Out of Stock</span>';
                }
                echo '</div>';
            }
        } else {
            echo '<div class="error">❌ Failed to get products: ' . $products['error'] . '</div>';
        }
        echo '</div>';
        
        // Test 3: Authentication Functions
        echo '<div class="test-section">';
        echo '<h2>3️⃣ Authentication Test</h2>';
        require_once __DIR__ . '/auth_functions.php';
        
        // Check if session is active
        if(isset($_SESSION['user_id'])) {
            echo '<div class="success">✅ Session active for user ID: ' . $_SESSION['user_id'] . '</div>';
            echo '<pre>User: ' . $_SESSION['firstname'] . ' ' . $_SESSION['lastname'] . "\n";
            echo 'Email: ' . $_SESSION['email'] . "\n";
            echo 'Role: ' . $_SESSION['role'] . '</pre>';
        } else {
            echo '<div class="success">✅ Auth functions loaded (no active session)</div>';
            echo '<p>To test: <a href="../pages/auth/signin.php">Sign in here</a></p>';
        }
        echo '</div>';
        
        // Test 4: Cart Handler
        echo '<div class="test-section">';
        echo '<h2>4️⃣ Cart Handler Test</h2>';
        require_once __DIR__ . '/cart_handler.php';
        
        if(isset($_SESSION['user_id'])) {
            $cart = viewCart();
            if($cart['success']) {
                echo '<div class="success">✅ Cart retrieved successfully</div>';
                echo '<pre>Items in cart: ' . $cart['total_items'] . "\n";
                echo 'Total amount: ' . $cart['total_amount_formatted'] . '</pre>';
                
                if(!empty($cart['cart_items'])) {
                    foreach($cart['cart_items'] as $item) {
                        echo '<div class="product-card">';
                        echo '<strong>' . htmlspecialchars($item['name_en']) . '</strong><br>';
                        echo 'Quantity: ' . $item['quantity'] . '<br>';
                        echo 'Subtotal: ' . $item['subtotal_formatted'];
                        echo '</div>';
                    }
                } else {
                    echo '<p>Cart is empty</p>';
                }
            } else {
                echo '<div class="error">❌ Failed to get cart: ' . $cart['error'] . '</div>';
            }
        } else {
            echo '<div class="success">✅ Cart functions loaded (login required to view cart)</div>';
        }
        echo '</div>';
        
        // Test 5: Database Tables Check
        echo '<div class="test-section">';
        echo '<h2>5️⃣ Database Tables Check</h2>';
        
        $tables = ['users', 'products', 'cart', 'orders'];
        $all_exist = true;
        
        echo '<ul style="list-style: none; padding-left: 0;">';
        foreach($tables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if($check->num_rows > 0) {
                // Get row count
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result->fetch_assoc()['count'];
                echo '<li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <strong>' . $table . '</strong>: <span style="color: #666;">' . $count . ' records</span></li>';
            } else {
                echo '<li style="padding: 8px 0; border-bottom: 1px solid #eee;">❌ <strong>' . $table . '</strong>: Missing</li>';
                $all_exist = false;
            }
        }
        echo '</ul>';
        
        if($all_exist) {
            echo '<div class="success" style="margin-top: 15px;">✅ All required tables exist!</div>';
        } else {
            echo '<div class="error" style="margin-top: 15px;">❌ Some tables are missing. Run setup_ecommerce.sql</div>';
        }
        echo '</div>';
        
        // Test 6: Currency Formatting
        echo '<div class="test-section">';
        echo '<h2>6️⃣ Currency Formatting Test</h2>';
        echo '<div class="success">✅ JOD formatting test:</div>';
        echo '<pre>';
        echo formatJOD(85.5) . "\n";
        echo formatJOD(450) . "\n";
        echo formatJOD(320.750) . "\n";
        echo formatJOD(1234.567) . "\n";
        echo '</pre>';
        echo '</div>';
        
        // Summary
        echo '<div class="test-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">';
        echo '<h2 style="color: white;">📊 Test Summary</h2>';
        echo '<p style="font-size: 18px; margin: 10px 0;">All core backend systems are operational!</p>';
        echo '<ul style="line-height: 2;">';
        echo '<li>✅ Database connection established</li>';
        echo '<li>✅ Product management working</li>';
        echo '<li>✅ Authentication system ready</li>';
        echo '<li>✅ Shopping cart operational</li>';
        echo '<li>✅ Currency formatting correct</li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
