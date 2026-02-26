<?php
/**
 * Delete All Orders from Database
 * This script safely removes all orders and related data
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Check if user is admin
if (!isAdmin()) {
    die('❌ Access Denied. Admin only.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete All Orders</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .warning {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🗑️ Delete All Orders</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
            $confirmation = $_POST['confirmation'] ?? '';
            
            if ($confirmation !== 'DELETE ALL ORDERS') {
                echo '<div class="warning">❌ Confirmation text does not match. Please try again.</div>';
            } else {
                try {
                    // Get initial counts
                    $orders_count = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
                    $order_items_count = $conn->query("SELECT COUNT(*) as cnt FROM order_items")->fetch_assoc()['cnt'] ?? 0;
                    
                    // Begin transaction
                    $conn->begin_transaction();
                    
                    // Delete in correct order to respect foreign keys
                    $conn->query("DELETE FROM order_items WHERE 1=1");
                    $conn->query("DELETE FROM orders WHERE 1=1");
                    
                    // Commit transaction
                    $conn->commit();
                    
                    echo '<div class="success">';
                    echo '✅ <strong>Success!</strong> All orders have been removed from the database.<br>';
                    echo 'Deleted ' . $orders_count . ' orders and ' . $order_items_count . ' order items.';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    echo '<div class="warning">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
        
        // Show current stats
        $orders_count = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;
        $order_items_count = $conn->query("SELECT COUNT(*) as cnt FROM order_items")->fetch_assoc()['cnt'] ?? 0;
        $users_count = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'] ?? 0;
        ?>
        
        <div class="info">
            <strong>ℹ️ Current Database Stats:</strong><br>
            • Orders: <?= $orders_count ?><br>
            • Order Items: <?= $order_items_count ?><br>
            • Users: <?= $users_count ?>
        </div>
        
        <?php if ($orders_count > 0): ?>
        <div class="warning">
            <strong>⚠️ Warning!</strong><br>
            This action will permanently delete all <?= $orders_count ?> orders and <?= $order_items_count ?> order items from the database. This cannot be undone!
        </div>
        
        <form method="POST">
            <p><strong>To confirm deletion, type exactly:</strong></p>
            <p style="font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 5px; font-weight: bold;">DELETE ALL ORDERS</p>
            
            <input 
                type="text" 
                name="confirmation" 
                placeholder="Type the confirmation text above"
                style="width: 100%; padding: 10px; margin: 15px 0; border: 2px solid #ccc; border-radius: 5px; font-size: 1rem;"
                required
            >
            
            <div class="button-group">
                <button type="submit" class="btn-delete" onclick="if(this.form.confirmation.value !== 'DELETE ALL ORDERS') { alert('Please type the exact confirmation text'); return false; }">
                    🗑️ Delete All Orders
                </button>
                <a href="index.php" class="btn-cancel" style="text-decoration: none; display: inline-flex; align-items: center;">
                    Cancel
                </a>
            </div>
        </form>
        <?php else: ?>
        <div class="success">
            <strong>✅ No orders found</strong><br>
            The database is already empty. There are no orders to delete.
        </div>
        <div class="button-group">
            <a href="index.php" class="btn-cancel" style="text-decoration: none; display: inline-flex; align-items: center;">
                Back to Home
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
