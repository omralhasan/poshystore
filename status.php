<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poshy Store - Status & Solutions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.4rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .link-list { list-style: none; }
        .link-list li {
            margin: 12px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .link-list li:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .link-list a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icon { font-size: 1.5rem; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: auto;
        }
        .badge-working { background: #d1e7dd; color: #0f5132; }
        .badge-test { background: #fff3cd; color: #856404; }
        .credentials {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .credentials h3 { margin-bottom: 15px; font-size: 1.2rem; }
        .cred-box {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
        }
        .cred-label { font-size: 0.9rem; opacity: 0.9; margin-bottom: 5px; }
        .cred-value { font-size: 1.1rem; font-weight: 600; }
        .solutions {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .solutions h2 { color: #dc3545; margin-bottom: 15px; }
        .solution-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        .solution-item h4 { color: #28a745; margin-bottom: 8px; }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-ok { background: #28a745; }
        .status-test { background: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Poshy Store</h1>
            <p style="color: #666; font-size: 1.1rem;">System Status & Navigation</p>
            <div style="margin-top: 15px;">
                <span class="status-indicator status-ok"></span>
                <strong>All Systems Operational</strong>
            </div>
        </div>
        
        <div class="status-grid">
            <!-- Main Pages -->
            <div class="card">
                <h2>🏪 Main Store Pages</h2>
                <ul class="link-list">
                    <li>
                        <a href="index.php">
                            <span class="icon">🏠</span>
                            <span>Homepage</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                    <li>
                        <a href="pages/auth/signin.php">
                            <span class="icon">🔐</span>
                            <span>Sign In</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                    <li>
                        <a href="pages/auth/signup.php">
                            <span class="icon">✍️</span>
                            <span>Sign Up</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                    <li>
                        <a href="pages/shop/cart.php">
                            <span class="icon">🛒</span>
                            <span>Shopping Cart</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                    <li>
                        <a href="pages/shop/my_orders.php">
                            <span class="icon">📦</span>
                            <span>My Orders</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                    <li>
                        <a href="pages/admin/admin_panel.php">
                            <span class="icon">⚙️</span>
                            <span>Admin Panel</span>
                            <span class="badge badge-working">WORKING</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Testing & Debug Tools -->
            <div class="card">
                <h2>🧪 Testing & Debug Tools</h2>
                <ul class="link-list">
                    <li>
                        <a href="quick_login.php">
                            <span class="icon">🚀</span>
                            <span>Quick Login</span>
                            <span class="badge badge-test">INSTANT</span>
                        </a>
                    </li>
                    <li>
                        <a href="test_signin_form.php">
                            <span class="icon">🐛</span>
                            <span>Signin Debug</span>
                            <span class="badge badge-test">DEBUG</span>
                        </a>
                    </li>
                    <li>
                        <a href="test_signin_debug.php">
                            <span class="icon">🔍</span>
                            <span>Login Test Tool</span>
                            <span class="badge badge-test">TEST</span>
                        </a>
                    </li>
                    <li>
                        <a href="test_admin_login.php">
                            <span class="icon">👨‍💼</span>
                            <span>Admin Test</span>
                            <span class="badge badge-test">TEST</span>
                        </a>
                    </li>
                    <li>
                        <a href="test_login.php">
                            <span class="icon">✅</span>
                            <span>System Check</span>
                            <span class="badge badge-test">INFO</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="credentials">
            <h3>🔑 Test Admin Credentials</h3>
            <div class="cred-box">
                <div class="cred-label">Email:</div>
                <div class="cred-value">admin@poshylifestyle.com</div>
            </div>
            <div class="cred-box">
                <div class="cred-label">Password:</div>
                <div class="cred-value">admin123</div>
            </div>
            <p style="margin-top: 15px; font-size: 0.95rem; opacity: 0.9;">
                Use these credentials to test both signin page and admin panel access
            </p>
        </div>
        
        <div class="solutions">
            <h2>✅ Solutions to Common Issues</h2>
            
            <div class="solution-item">
                <h4>Problem: Signin page not working</h4>
                <p><strong>Solution:</strong> Use the <a href="quick_login.php">Quick Login</a> page for instant access, or test with <a href="test_signin_form.php">Signin Debug</a> to see what's happening.</p>
            </div>
            
            <div class="solution-item">
                <h4>Problem: Admin panel redirects to homepage</h4>
                <p><strong>Solution:</strong> You must be logged in as admin first. Use <a href="quick_login.php">Quick Login</a> to login instantly, then access <a href="pages/admin/admin_panel.php">Admin Panel</a>.</p>
            </div>
            
            <div class="solution-item">
                <h4>Problem: Session not persisting</h4>
                <p><strong>Solution:</strong> Clear browser cookies and cache, or use incognito mode. Make sure cookies are enabled in your browser.</p>
            </div>
            
            <div class="solution-item">
                <h4>Problem: Can't remember credentials</h4>
                <p><strong>Solution:</strong> Use the credentials shown above. You can also run <a href="reset_admin_password.php">reset_admin_password.php</a> to reset the password.</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: white; font-size: 0.9rem;">
            <p>💡 Tip: Bookmark this page for easy access to all testing tools</p>
        </div>
    </div>
</body>
</html>
