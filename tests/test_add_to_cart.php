<!DOCTYPE html>
<html>
<head>
    <title>Test Add to Cart - Logged In</title>
</head>
<body>
    <h1>Testing Add to Cart (Logged In User)</h1>
    
    <?php
    // Sign in first
    require_once 'auth_functions.php';
    
    // Check if already logged in
    if (!isset($_SESSION['user_id'])) {
        // Get first user from database
        require_once 'db_connect.php';
        $result = $conn->query("SELECT * FROM users LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            echo "<p style='color: green;'>✅ Logged in as: " . htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ No users found in database</p>";
            exit;
        }
    } else {
        echo "<p style='color: green;'>✅ Already logged in as: " . htmlspecialchars($_SESSION['firstname']) . " " . htmlspecialchars($_SESSION['lastname']) . "</p>";
    }
    
    // Now test adding to cart
    require_once 'cart_handler.php';
    
    echo "<h2>Adding product #1 to cart...</h2>";
    $result = addToCart(1, 1);
    
    if ($result['success']) {
        echo "<p style='color: green; font-size: 18px;'>✅ SUCCESS! Product added to cart!</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        // Show cart contents
        echo "<h2>Current Cart Contents:</h2>";
        $cart = viewCart();
        if ($cart['success']) {
            echo "<p>Total items: " . $cart['total_items'] . "</p>";
            echo "<p>Total amount: " . $cart['total_amount_formatted'] . "</p>";
            foreach ($cart['cart_items'] as $item) {
                echo "<p>- " . htmlspecialchars($item['name_en']) . " (Qty: " . $item['quantity'] . ")</p>";
            }
        }
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ ERROR: " . htmlspecialchars($result['error']) . "</p>";
    }
    ?>
    
    <hr>
    <h2>Test AJAX Add to Cart (like the real button)</h2>
    <button id="testBtn" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
        Add Product #1 via AJAX
    </button>
    <div id="result" style="margin-top: 20px; padding: 15px; border-radius: 5px;"></div>
    
    <script>
    document.getElementById('testBtn').addEventListener('click', function() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '⏳ Loading...';
        resultDiv.style.background = '#f0f0f0';
        
        fetch('add_to_cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=1&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.style.background = '#d4edda';
                resultDiv.style.color = '#155724';
                resultDiv.innerHTML = '✅ SUCCESS! ' + (data.message || 'Product added to cart');
            } else {
                resultDiv.style.background = '#f8d7da';
                resultDiv.style.color = '#721c24';
                resultDiv.innerHTML = '❌ ERROR: ' + data.error;
            }
        })
        .catch(error => {
            resultDiv.style.background = '#f8d7da';
            resultDiv.style.color = '#721c24';
            resultDiv.innerHTML = '❌ Network Error: ' + error;
        });
    });
    </script>
    
    <hr>
    <p><a href="index.php">← Back to Store</a> | <a href="cart.php">View Cart</a></p>
</body>
</html>
