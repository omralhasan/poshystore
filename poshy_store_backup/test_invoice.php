<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Test - Posh Store</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .test-links {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .test-links a {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .test-links a:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Invoice Button - FIXED!</h1>
        
        <div class="success">
            <h3>Problem Fixed:</h3>
            <p>The invoice page was trying to access database columns that don't exist:</p>
            <ul>
                <li>❌ <code>created_at</code> → ✅ <code>order_date</code></li>
                <li>❌ <code>updated_at</code> → ✅ (removed - doesn't exist)</li>
            </ul>
        </div>

        <?php
        require_once __DIR__ . '/includes/db_connect.php';
        
        echo "<h3>📋 Available Orders for Testing:</h3>";
        $orders_sql = "SELECT o.id, o.user_id, o.total_amount, o.status, o.order_date,
                              u.firstname, u.lastname, u.email
                       FROM orders o
                       LEFT JOIN users u ON o.user_id = u.id
                       ORDER BY o.order_date DESC
                       LIMIT 10";
        $orders_result = $conn->query($orders_sql);
        
        if ($orders_result && $orders_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>";
            while ($order = $orders_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>#" . $order['id'] . "</td>";
                echo "<td>" . htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) . "</td>";
                echo "<td>" . number_format($order['total_amount'], 3) . " JOD</td>";
                echo "<td style='text-transform:capitalize;'>" . htmlspecialchars($order['status']) . "</td>";
                echo "<td>" . date('M d, Y', strtotime($order['order_date'])) . "</td>";
                echo "<td><a href='pages/admin/print_invoice.php?order_id=" . $order['id'] . "' target='_blank'>🖨️ View Invoice</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No orders found in database.</p>";
        }
        ?>

        <div class="test-links">
            <h3>🔗 Quick Links:</h3>
            <a href="pages/admin/admin_panel.php">📊 Admin Panel</a>
            <a href="pages/auth/signin.php">🔐 Sign In</a>
            <a href="index.php">🏠 Home</a>
        </div>

        <div style="background:#e7f3ff; padding:15px; border-radius:5px; margin-top:20px;">
            <h4>📝 What Was Fixed:</h4>
            <ol>
                <li>Updated SQL query in <code>print_invoice.php</code> to use <code>order_date</code> instead of <code>created_at</code></li>
                <li>Removed reference to non-existent <code>updated_at</code> column</li>
                <li>Fixed all date display references in the invoice template</li>
            </ol>
            <p><strong>Result:</strong> Invoice button now works correctly! ✅</p>
        </div>
    </div>
</body>
</html>
