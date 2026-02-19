<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Poshy Lifestyle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            max-width: 700px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .features {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
            text-align: left;
        }
        
        .feature-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .icon {
            font-size: 1.5rem;
        }
        
        .buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            flex: 1;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #28a745;
            color: white;
        }
        
        .quick-links {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .quick-links h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .quick-links a {
            display: inline-block;
            margin: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .quick-links a:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="logo">🛍️</div>
        <h1>Welcome to Poshy Lifestyle</h1>
        <p class="subtitle">Your luxury e-commerce destination</p>
        
        <div class="features">
            <div class="feature-item">
                <span class="icon">✅</span>
                <span><strong>9 Luxury Products</strong> ready to browse</span>
            </div>
            <div class="feature-item">
                <span class="icon">🛒</span>
                <span><strong>Shopping Cart</strong> with live updates</span>
            </div>
            <div class="feature-item">
                <span class="icon">💳</span>
                <span><strong>Secure Checkout</strong> process</span>
            </div>
            <div class="feature-item">
                <span class="icon">📦</span>
                <span><strong>Order Tracking</strong> system</span>
            </div>
            <div class="feature-item">
                <span class="icon">🌐</span>
                <span><strong>Arabic & English</strong> support</span>
            </div>
        </div>
        
        <div class="buttons">
            <a href="pages/auth/signup.php" class="btn btn-secondary">Sign Up</a>
            <a href="pages/auth/signin.php" class="btn btn-primary">Sign In</a>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                or browse products as guest →
            </a>
        </div>
        
        <div class="quick-links">
            <h3>Test Pages:</h3>
            <a href="index.php">🏠 Homepage</a>
            <a href="pages/shop/cart.php">🛒 Cart</a>
            <a href="pages/shop/my_orders.php">📦 Orders</a>
            <a href="test_backend.php">🧪 Backend Test</a>
            <a href="quick_test.php">⚡ CLI Test</a>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #e7f3ff; border-radius: 8px;">
            <p style="color: #004085; font-size: 0.9rem; margin: 0;">
                💡 <strong>Tip:</strong> Create an account or sign in to access the full shopping experience!
            </p>
        </div>
    </div>
</body>
</html>
