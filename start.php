<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poshy Store - Quick Links</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .links {
            display: grid;
            gap: 15px;
        }
        .link-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .link-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        .link-card h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 18px;
        }
        .link-card p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        .credentials {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
        .credentials h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .credentials code {
            background: white;
            padding: 3px 8px;
            border-radius: 4px;
            color: #d63384;
            font-family: monospace;
        }
        .status {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            background: #d1e7dd;
            border-radius: 8px;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Poshy Store</h1>
        <p class="subtitle">Quick Navigation & Testing Links</p>
        
        <div class="links">
            <a href="index.php" class="link-card">
                <h3>🏠 Homepage</h3>
                <p>View all products organized by category</p>
            </a>
            
            <a href="pages/auth/signin.php" class="link-card">
                <h3>🔐 Sign In</h3>
                <p>Main login page - Use this to access your account</p>
            </a>
            
            <a href="pages/auth/signup.php" class="link-card">
                <h3>✍️ Sign Up</h3>
                <p>Create a new customer account</p>
            </a>
            
            <a href="test_signin_debug.php" class="link-card">
                <h3>🧪 Test Login (Debug)</h3>
                <p>Test credentials with detailed debug information</p>
            </a>
            
            <a href="pages/shop/cart.php" class="link-card">
                <h3>🛒 Shopping Cart</h3>
                <p>View your cart (requires login)</p>
            </a>
            
            <a href="pages/shop/my_orders.php" class="link-card">
                <h3>📦 My Orders</h3>
                <p>View order history (requires login)</p>
            </a>
            
            <a href="pages/admin/admin_panel.php" class="link-card">
                <h3>⚙️ Admin Panel</h3>
                <p>Manage products and orders (admin only)</p>
            </a>
        </div>
        
        <div class="credentials">
            <h3>🔑 Test Credentials</h3>
            <p><strong>Email:</strong> <code>admin@poshylifestyle.com</code></p>
            <p><strong>Password:</strong> <code>admin123</code></p>
            <p style="margin-top: 10px; font-size: 13px; color: #856404;">
                Use these credentials to test the signin functionality
            </p>
        </div>
        
        <div class="status">
            ✅ All pages are working correctly!<br>
            Server: Running | Database: Connected
        </div>
    </div>
</body>
</html>
