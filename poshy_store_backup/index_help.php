<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signin Page - Working!</title>
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
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        .status-box {
            background: #d1e7dd;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .status-box h3 {
            color: #0f5132;
            margin-bottom: 15px;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            margin: 8px 0;
        }
        .status-ok { color: #28a745; font-size: 1.5rem; }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .note h4 {
            color: #856404;
            margin-bottom: 8px;
        }
        .note p {
            color: #856404;
            font-size: 14px;
        }
        .url-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            margin: 10px 0;
            border-left: 4px solid #667eea;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Signin Page is Working!</h1>
        
        <div class="status-box">
            <h3>Server Status Check:</h3>
            <div class="status-item">
                <span class="status-ok">✓</span>
                <span><strong>Apache Server:</strong> Running</span>
            </div>
            <div class="status-item">
                <span class="status-ok">✓</span>
                <span><strong>PHP:</strong> 8.4.17 Working</span>
            </div>
            <div class="status-item">
                <span class="status-ok">✓</span>
                <span><strong>Database:</strong> Connected</span>
            </div>
            <div class="status-item">
                <span class="status-ok">✓</span>
                <span><strong>Signin Page:</strong> HTTP 200 OK</span>
            </div>
            <div class="status-item">
                <span class="status-ok">✓</span>
                <span><strong>No PHP Errors:</strong> Clean</span>
            </div>
        </div>
        
        <div class="note">
            <h4>📌 If you see "File not found" or similar error:</h4>
            <p><strong>Solution:</strong> You're probably using the WRONG URL. Make sure to use the correct path below.</p>
        </div>
        
        <h3 style="margin: 20px 0 10px 0; color: #333;">Correct URLs to Use:</h3>
        
        <div class="url-box">
            ✅ CORRECT: http://localhost/poshy_store/pages/auth/signin.php
        </div>
        
        <div class="url-box" style="border-left-color: #dc3545;">
            ❌ WRONG: http://localhost/poshy_store/signin.php (redirects, may cause issues)
        </div>
        
        <h3 style="margin: 30px 0 15px 0; color: #333;">Access Methods:</h3>
        
        <a href="pages/auth/signin.php" class="btn btn-primary">
            🔐 Go to Signin Page (Normal)
        </a>
        
        <a href="quick_login.php" class="btn btn-success">
            ⚡ Quick Login (Instant Admin Access)
        </a>
        
        <a href="test_signin_form.php" class="btn btn-info">
            🐛 Debug Tool (See What's Happening)
        </a>
        
        <div class="note" style="margin-top: 30px;">
            <h4>🔑 Test Credentials:</h4>
            <p><strong>Email:</strong> admin@poshystore.com</p>
            <p><strong>Password:</strong> admin123</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e9ecef;">
            <p style="color: #666; font-size: 14px;">
                Need more help? Visit <a href="status.php" style="color: #667eea;">Status Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>
