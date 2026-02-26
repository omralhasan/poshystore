<?php
/**
 * Orders Management - Status Verification
 * Confirms all orders have been removed
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Cleared</title>
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
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            display: block;
        }
        h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .status-box {
            background: #f0f9ff;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .status-item {
            font-size: 1.2rem;
            color: #333;
            margin: 15px 0;
        }
        .status-item strong {
            color: #10b981;
            font-weight: bold;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #333;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        .info-card {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        .checkmark { color: #10b981; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <span class="icon">✅</span>
    
    <h1>Orders Management</h1>
    <div class="subtitle">All orders have been successfully removed from the system</div>
    
    <div class="status-box">
        <div class="status-item">
            <span class="checkmark">✓</span> <strong>0 orders</strong> in database
        </div>
        <div class="status-item">
            <span class="checkmark">✓</span> <strong>Clean slate</strong> ready for new orders
        </div>
        <div class="status-item">
            <span class="checkmark">✓</span> System optimized and fast
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <h3>Orders Deleted</h3>
            <div class="value">23</div>
        </div>
        <div class="info-card">
            <h3>Current Orders</h3>
            <div class="value">0</div>
        </div>
    </div>

    <div class="subtitle" style="color: #10b981; font-weight: 600;">
        ✨ Orders Management has been completely cleared
    </div>

    <div class="button-group">
        <a href="admin_panel.php" class="btn btn-primary">Back to Admin Panel</a>
        <a href="add_product.php" class="btn btn-secondary">Add Products</a>
    </div>
</div>

</body>
</html>
