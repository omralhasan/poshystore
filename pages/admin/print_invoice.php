<?php
/**
 * Invoice Print Page - Poshy Lifestyle E-Commerce
 * 
 * Generates printable invoice for orders
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../shop/checkout.php';

// Check if user is admin
if (!isAdmin()) {
    // Log failed access attempt for debugging
    error_log("Invoice access denied - User ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role: " . ($_SESSION['role'] ?? 'none'));
    header('Location: ../../index.php');
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$invoice_type = isset($_GET['invoice_type']) ? $_GET['invoice_type'] : 'customer'; // customer or supplier

if (!$order_id) {
    die('Invalid order ID');
}

// Fetch order details
$sql = "SELECT o.id, o.user_id, o.total_amount, o.status, 
               o.order_date,
               u.firstname, u.lastname, u.email, u.phonenumber
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Order not found');
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch order items with product ID as SKU and pricing info
$items_sql = "SELECT oi.product_id, oi.product_name_en, oi.product_name_ar, 
                     oi.quantity, oi.price_per_item, oi.subtotal,
                     p.supplier_cost, p.public_price_min, p.public_price_max
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";

$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
$invoice_total = 0;

while ($item = $items_result->fetch_assoc()) {
    // Use product_id as SKU if no separate SKU exists
    $item['sku'] = 'SKU-' . str_pad($item['product_id'], 6, '0', STR_PAD_LEFT);
    
    // Determine price based on invoice type
    if ($invoice_type === 'supplier') {
        // For suppliers, show supplier cost
        $item['display_price'] = $item['supplier_cost'] ?: $item['price_per_item'];
        $item['display_subtotal'] = $item['display_price'] * $item['quantity'];
    } else {
        // For customers, use the minimum public price (or the original price_per_item)
        $item['display_price'] = $item['public_price_min'] ?: $item['price_per_item'];
        $item['display_subtotal'] = $item['display_price'] * $item['quantity'];
    }
    
    $invoice_total += $item['display_subtotal'];
    $order_items[] = $item;
}
$items_stmt->close();

// Company information
$company = [
    'name' => 'Poshy Lifestyle',
    'address' => 'Prince Asem Bin Nayef Street',
    'address2' => 'Al Baraka Complex Office 101',
    'city' => 'Amman - Marj Al Hamam 17110',
    'country' => 'Jordan',
    'email' => 'info@poshylifestyle.com',
    'website' => 'https://poshylifestyle.com',
    'phone' => '+962 6 XXX XXXX'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - Poshy Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .company-info h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .company-info p {
            color: #666;
            margin: 3px 0;
            font-size: 0.9rem;
        }
        
        .invoice-meta {
            text-align: right;
        }
        
        .invoice-meta h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .invoice-meta p {
            margin: 5px 0;
            color: #666;
        }
        
        .invoice-meta strong {
            color: #333;
        }
        
        .details-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .order-meta .meta-item {
            text-align: center;
        }
        
        .order-meta .meta-item strong {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .order-meta .meta-item span {
            display: block;
            color: #333;
            font-weight: 600;
        }
        
        .details-box {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fafafa;
        }
        
        .details-box h3 {
            color: #333;
            margin-bottom: 12px;
            font-size: 0.95rem;
            font-weight: bold;
            text-transform: uppercase;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .details-box p {
            margin: 6px 0;
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table thead {
            background: #333;
            color: white;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals-section {
            margin-left: auto;
            width: 400px;
            padding: 20px;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .totals-section h3 {
            color: #333;
            font-size: 1rem;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .totals-section table {
            width: 100%;
        }
        
        .totals-section td {
            padding: 8px 0;
        }
        
        .totals-section .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            border-top: 2px solid #333;
            padding-top: 12px !important;
            margin-top: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cfe2ff; color: #084298; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .invoice-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .print-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            margin: 20px 0;
            transition: background 0.3s;
        }
        
        .print-button:hover {
            background: #5568d3;
        }
        
        .back-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            margin: 20px 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .action-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
            
            .print-button,
            .back-button,
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <a href="admin_panel.php" class="back-button">← Back to Admin Panel</a>
        <button onclick="window.print()" class="print-button">🖨️ Print Invoice</button>
    </div>
    
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1><?php echo $company['name']; ?></h1>
                <p style="font-size: 1rem; color: #666; margin-top: 5px;">
                    <strong><?php echo $invoice_type === 'supplier' ? 'SUPPLIER INVOICE' : 'CUSTOMER INVOICE'; ?></strong>
                </p>
            </div>
            <div class="invoice-meta">
                <h2>#PO<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h2>
                <p><?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
            </div>
        </div>
        
        <!-- Order Meta Information -->
        <div class="order-meta">
            <div class="meta-item">
                <strong>Payment Terms</strong>
                <span>Cash on Delivery</span>
            </div>
            <div class="meta-item">
                <strong>Currency</strong>
                <span>JOD</span>
            </div>
            <div class="meta-item">
                <strong>Status</strong>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo strtoupper($order['status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Three Column Address Section -->
        <div class="details-section">
            <div class="details-box">
                <h3>Supplier</h3>
                <p><strong><?php echo $company['name']; ?></strong></p>
                <p><?php echo $company['address']; ?></p>
                <p><?php echo $company['address2']; ?></p>
                <p><?php echo $company['city']; ?></p>
                <p><?php echo $company['country']; ?></p>
            </div>
            
            <div class="details-box">
                <h3>Ship To</h3>
                <p><strong><?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
                <p><?php echo htmlspecialchars($order['phonenumber']); ?></p>
                <p>Customer ID: <?php echo $order['user_id']; ?></p>
            </div>
            
            <div class="details-box">
                <h3>Bill To</h3>
                <p><strong><?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
                <p><?php echo htmlspecialchars($order['phonenumber']); ?></p>
                <p><?php echo $company['city']; ?></p>
                <p><?php echo $company['country']; ?></p>
            </div>
        </div>
        
        <!-- Products Table -->
        <h3 style="color: #333; margin-bottom: 15px; font-size: 1rem; text-transform: uppercase;">Products</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Supplier SKU</th>
                    <th class="text-center">QTY</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Total (JOD)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name_en']); ?></strong>
                        <?php if (!empty($item['product_name_ar'])): ?>
                            <br><span style="color: #666; font-size: 0.85rem;"><?php echo htmlspecialchars($item['product_name_ar']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['sku'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo formatJOD($item['display_price']); ?></td>
                    <td class="text-right">0%</td>
                    <td class="text-right"><strong><?php echo formatJOD($item['display_subtotal']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Cost Summary -->
        <div class="totals-section">
            <h3>Cost Summary</h3>
            <table>
                <tr>
                    <td>Taxes (included)</td>
                    <td class="text-right">JOD 0.000</td>
                </tr>
                <tr>
                    <td>Subtotal (<?php echo count($order_items); ?> items)</td>
                    <td class="text-right"><?php echo formatJOD($invoice_total); ?></td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td class="text-right">JOD 0.000</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td class="text-right"><strong><?php echo formatJOD($invoice_total); ?></strong></td>
                </tr>
            </table>
        </div>
        
        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <p><strong><?php echo $company['name']; ?></strong></p>
            <p><?php echo $company['address'] . ', ' . $company['address2']; ?></p>
            <p><?php echo $company['email']; ?></p>
            <p><?php echo $company['website']; ?></p>
            <p style="margin-top: 15px; font-size: 0.85rem; color: #999;">Powered by Poshy Lifestyle E-Commerce Platform</p>
        </div>
    </div>
</body>
</html>
