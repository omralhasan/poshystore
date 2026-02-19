<?php
/**
 * Daily Reports & Invoice Generator - Admin Panel
 * Shows orders, calculates profits, and generates daily reports
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// Get date and period from URL or use today
$report_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$period = isset($_GET['period']) ? $_GET['period'] : 'day';

// Calculate date range based on period
$start_date = $report_date;
$end_date = $report_date;
$period_label = '';

switch ($period) {
    case 'week':
        // Get the week starting from the selected date
        $start_date = date('Y-m-d', strtotime($report_date));
        $end_date = date('Y-m-d', strtotime($report_date . ' +6 days'));
        $period_label = 'Week: ' . date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date));
        break;
    case 'month':
        // Get the entire month of the selected date
        $start_date = date('Y-m-01', strtotime($report_date));
        $end_date = date('Y-m-t', strtotime($report_date));
        $period_label = 'Month: ' . date('F Y', strtotime($report_date));
        break;
    default: // day
        $period_label = 'Date: ' . date('F j, Y', strtotime($report_date));
        break;
}

// Get orders for the selected period
$sql = "SELECT o.id as order_id, o.user_id, o.total_amount, o.status, o.order_type,
               o.order_date,
               u.firstname, u.lastname, u.email, u.phonenumber
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
$total_revenue = 0;
$total_cost = 0;
$customer_revenue = 0;
$supplier_revenue = 0;

while ($order = $result->fetch_assoc()) {
    // Get order items with cost information
    $items_sql = "SELECT oi.quantity, oi.price_per_item, oi.subtotal,
                         p.supplier_cost, p.public_price_min
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
    
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('i', $order['order_id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $order_cost = 0;
    $order_revenue = 0;
    
    while ($item = $items_result->fetch_assoc()) {
        $item_cost = ($item['supplier_cost'] ?? 0) * $item['quantity'];
        $order_cost += $item_cost;
        $order_revenue += $item['subtotal'];
    }
    $items_stmt->close();
    
    $order['cost'] = $order_cost;
    $order['revenue'] = $order_revenue;
    $order['profit'] = $order_revenue - $order_cost;
    $order['profit_margin'] = $order_revenue > 0 ? (($order_revenue - $order_cost) / $order_revenue * 100) : 0;
    
    $total_revenue += $order_revenue;
    $total_cost += $order_cost;
    
    if ($order['order_type'] === 'customer') {
        $customer_revenue += $order_revenue;
    } else {
        $supplier_revenue += $order_revenue;
    }
    
    $orders[] = $order;
}
$stmt->close();

$total_profit = $total_revenue - $total_cost;
$profit_margin = $total_revenue > 0 ? (($total_revenue - $total_cost) / $total_revenue * 100) : 0;
$total_orders = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2rem;
            color: #1a1d2e;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .date-selector {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .date-selector input[type="date"],
        .date-selector select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            background: white;
            cursor: pointer;
        }
        
        .date-selector select {
            font-weight: 600;
            color: #374151;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.revenue { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.cost { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .stat-icon.profit { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
        .stat-icon.orders { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1d2e;
        }
        
        .stat-subtitle {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 0.5rem;
        }
        
        .report-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .report-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1d2e;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }
        
        .table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-customer {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-supplier {
            background: #fce7f3;
            color: #be185d;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        
        .profit-positive {
            color: #10b981;
            font-weight: 600;
        }
        
        .profit-negative {
            color: #ef4444;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
            font-size: 1.1rem;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .header, .btn, .date-selector {
                display: none !important;
            }
            
            .report-card {
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Sales Report
            </h1>
            <div class="date-selector">
                <select id="periodSelect" onchange="changePeriod()">
                    <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Daily</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Weekly</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Monthly</option>
                </select>
                <input type="date" id="reportDate" value="<?= $report_date ?>" onchange="changeDate()">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="admin_panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?= formatJOD($total_revenue) ?></div>
                <div class="stat-subtitle">From <?= $total_orders ?> orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon cost">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-label">Total Cost</div>
                <div class="stat-value"><?= formatJOD($total_cost) ?></div>
                <div class="stat-subtitle">Product costs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon profit">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Net Profit</div>
                <div class="stat-value profit-positive"><?= formatJOD($total_profit) ?></div>
                <div class="stat-subtitle">Margin: <?= number_format($profit_margin, 1) ?>%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $total_orders ?></div>
                <div class="stat-subtitle">
                    Customer: <?= formatJOD($customer_revenue) ?> | 
                    Supplier: <?= formatJOD($supplier_revenue) ?>
                </div>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-header">
                <div class="report-title">
                    <i class="fas fa-file-invoice"></i>
                    Orders Details - <?= $period_label ?>
                </div>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    No orders found for this <?= $period === 'day' ? 'date' : $period ?>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= $period === 'day' ? 'Time' : 'Date & Time' ?></th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <?php if ($period === 'day'): ?>
                                        <?= date('g:i A', strtotime($order['order_date'])) ?>
                                    <?php else: ?>
                                        <?= date('M j, g:i A', strtotime($order['order_date'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?><br>
                                    <small style="color: #9ca3af;"><?= htmlspecialchars($order['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $order['order_type'] ?>">
                                        <?= ucfirst($order['order_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatJOD($order['revenue']) ?></td>
                                <td><?= formatJOD($order['cost']) ?></td>
                                <td class="<?= $order['profit'] >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                                    <?= formatJOD($order['profit']) ?>
                                </td>
                                <td><?= number_format($order['profit_margin'], 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f9fafb; font-weight: 700;">
                            <td colspan="4">TOTAL</td>
                            <td><?= formatJOD($total_revenue) ?></td>
                            <td><?= formatJOD($total_cost) ?></td>
                            <td class="profit-positive"><?= formatJOD($total_profit) ?></td>
                            <td><?= number_format($profit_margin, 1) ?>%</td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function changeDate() {
            const date = document.getElementById('reportDate').value;
            const period = document.getElementById('periodSelect').value;
            window.location.href = `daily_reports.php?date=${date}&period=${period}`;
        }
        
        function changePeriod() {
            const date = document.getElementById('reportDate').value;
            const period = document.getElementById('periodSelect').value;
            window.location.href = `daily_reports.php?date=${date}&period=${period}`;
        }
    </script>
</body>
</html>
