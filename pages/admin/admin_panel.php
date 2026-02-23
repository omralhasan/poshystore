<?php
/**
 * Admin Panel - Poshy Lifestyle E-Commerce
 * 
 * Admin dashboard for managing orders and products
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../shop/checkout.php';
require_once __DIR__ . '/../../includes/product_manager.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_order_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        $result = updateOrderStatus($order_id, $new_status);
        echo json_encode($result);
        exit();
    }
    
    if ($action === 'update_order_type') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $order_type = $_POST['order_type'] ?? '';
        
        if ($order_id > 0 && in_array($order_type, ['customer', 'supplier'])) {
            $sql = "UPDATE orders SET order_type = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $order_type, $order_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Order type updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update order type']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        }
        exit();
    }
    
    if ($action === 'update_product_price') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $new_price = floatval($_POST['new_price'] ?? 0);
        
        $result = updateProductPrice($product_id, $new_price);
        echo json_encode($result);
        exit();
    }
    
    if ($action === 'apply_discount') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
        
        $result = applyProductDiscount($product_id, $discount_percentage);
        echo json_encode($result);
        exit();
    }
    
    if ($action === 'remove_discount') {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        $result = removeProductDiscount($product_id);
        echo json_encode($result);
        exit();
    }

    if ($action === 'delete_product') {
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }
        // Clean up all related rows first
        $conn->query("DELETE FROM product_tags WHERE product_id = $product_id");
        $conn->query("DELETE FROM cart_items WHERE product_id = $product_id");
        $conn->query("DELETE FROM cart WHERE product_id = $product_id");
        $conn->query("DELETE FROM product_reviews WHERE product_id = $product_id");
        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $product_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $stmt->close();
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

// Get all orders
$status_filter = $_GET['status'] ?? null;
$orders_result = getAllOrders(50, 0, $status_filter);
$orders = $orders_result['orders'] ?? [];

// Get all products
$products_result = getAllProducts(['in_stock' => false], 100);
$products = $products_result['products'] ?? [];

// Calculate statistics
$total_orders = count($orders);
$pending_orders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$total_revenue = array_sum(array_map(fn($o) => $o['total_amount'], $orders));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Poshy Lifestyle</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a1d2e;
            --secondary-dark: #242838;
            --accent-blue: #4f9eff;
            --accent-teal: #00d4aa;
            --accent-purple: #a855f7;
            --accent-orange: #ff6b35;
            --text-light: #ffffff;
            --text-gray: #9ca3af;
            --text-dark: #1f2937;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-light);
            min-height: 100vh;
            display: flex;
            color: var(--text-dark);
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
            color: var(--text-light);
            padding: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            z-index: 1000;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin-top: 0.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0;
        }
        
        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-light);
        }
        
        .nav-item.active {
            background: rgba(79, 158, 255, 0.1);
            color: var(--accent-blue);
            border-left-color: var(--accent-blue);
        }
        
        .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            overflow-x: hidden;
        }
        
        /* Top Header */
        .admin-header {
            background: white;
            padding: 1.75rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .home-btn {
            background: linear-gradient(135deg, var(--accent-blue), #3b82f6);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 158, 255, 0.3);
        }
        
        .home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(79, 158, 255, 0.4);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            opacity: 0.1;
            border-radius: 0 0 0 100%;
        }
        
        .stat-card h3 {
            color: var(--text-gray);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .stat-card:nth-child(2) .value,
        .stat-card:nth-child(2)::before {
            background: linear-gradient(135deg, var(--accent-purple), #8b5cf6);
        }
        
        .stat-card:nth-child(3) .value,
        .stat-card:nth-child(3)::before {
            background: linear-gradient(135deg, var(--accent-teal), #14b8a6);
        }
        
        .stat-card:nth-child(4) .value,
        .stat-card:nth-child(4)::before {
            background: linear-gradient(135deg, var(--accent-orange), #f97316);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 0.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        
        .tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-gray);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
        }
        
        .tab-btn:hover {
            background: var(--bg-light);
            color: var(--accent-blue);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 158, 255, 0.3);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Section */
        .section {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }
        
        .section h2 {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, var(--accent-blue), var(--accent-teal)) 1;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        /* Filter Bar */
        .filter-bar {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-bar select {
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 500;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .filter-bar select:hover {
            border-color: var(--accent-blue);
        }
        
        .filter-bar select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--bg-light);
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tbody tr {
            transition: all 0.2s ease;
        }
        
        tbody tr:hover {
            background: var(--bg-light);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .status-pending::before {
            background: #f59e0b;
        }
        
        .status-shipped { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        .status-shipped::before {
            background: #3b82f6;
        }
        
        .status-delivered { 
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .status-delivered::before {
            background: #10b981;
        }
        
        .status-cancelled { 
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .status-cancelled::before {
            background: #ef4444;
        }
        
        /* Form Elements */
        .status-select {
            padding: 0.625rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
        }
        
        .status-select:hover {
            border-color: var(--accent-blue);
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
        }
        
        .price-input {
            width: 120px;
            padding: 0.625rem 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .price-input:hover {
            border-color: var(--accent-blue);
        }
        
        .price-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(79, 158, 255, 0.1);
        }
        
        /* Buttons */
        .btn-update {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-discount {
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }
        
        .btn-discount:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }
        
        .btn-remove-discount {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: block;
            margin-top: 0.5rem;
            box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
        }
        
        .btn-remove-discount:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
        }
        
        .btn-update:disabled {
            background: linear-gradient(135deg, #d1d5db, #9ca3af);
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-print {
            background: linear-gradient(135deg, var(--accent-blue), #2563eb);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(79, 158, 255, 0.3);
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 158, 255, 0.4);
        }
        
        /* Additional Elements */
        .order-items {
            margin-top: 0.75rem;
            padding-left: 1.25rem;
            font-size: 0.875rem;
            color: var(--text-gray);
            line-height: 1.6;
        }
        
        .order-items li {
            margin: 0.375rem 0;
        }
        
        .customer-info {
            color: var(--text-gray);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        
        /* Product Info */
        .product-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
            color: white;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .original-price {
            text-decoration: line-through;
            color: var(--text-gray);
            font-size: 0.875rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .home-btn {
                width: 100%;
                justify-content: center;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: scroll;
            }
        }
        
        /* Loading State */
        .loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shopping-bag"></i> POSHY
            </div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> ADMIN PANEL
            </div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item active" onclick="showTab('orders')">
                <i class="fas fa-box"></i>
                <span>Orders Management</span>
            </div>
            <div class="nav-item" onclick="showTab('products')">
                <i class="fas fa-tag"></i>
                <span>Products & Pricing</span>
            </div>
            <a href="add_product.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>Add New Product</span>
            </a>
            <a href="manage_coupons.php" class="nav-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Coupon Management</span>
            </a>
            <a href="manage_categories.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                <span>Categories</span>
            </a>
            <a href="manage_brands.php" class="nav-item">
                <i class="fas fa-copyright"></i>
                <span>Brands</span>
            </a>
            <a href="daily_reports.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Daily Reports & Profits</span>
            </a>
            <a href="../../index.php" class="nav-item">
                <i class="fas fa-store"></i>
                <span>Visit Store</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="admin-header">
            <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
            <div class="header-actions">
                <a href="../../index.php" class="home-btn">
                    <i class="fas fa-store"></i>
                    View Store
                </a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-cart icon"></i>
                <h3>Total Orders</h3>
                <div class="value"><?= $total_orders ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock icon"></i>
                <h3>Pending Orders</h3>
                <div class="value"><?= $pending_orders ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-dollar-sign icon"></i>
                <h3>Total Revenue</h3>
                <div class="value"><?= formatJOD($total_revenue) ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-box-open icon"></i>
                <h3>Total Products</h3>
                <div class="value"><?= count($products) ?></div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('orders')">
                <i class="fas fa-box"></i>
                Orders Management
            </button>
            <button class="tab-btn" onclick="showTab('products')">
                <i class="fas fa-tag"></i>
                Products & Pricing
            </button>
        </div>
        
        <!-- Orders Tab -->
        <div id="orders-tab" class="tab-content active">
            <div class="section">
                <h2><i class="fas fa-clipboard-list"></i> Orders Management</h2>
                
                <div class="filter-bar">
                    <select onchange="filterOrders(this.value)">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                
                <div id="orders-alert"></div>
                
                <?php if (empty($orders)): ?>
                    <p style="text-align: center; padding: 3rem; color: var(--text-gray); font-size: 1.125rem;">
                        <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                        No orders found.
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div class="customer-info"><?= htmlspecialchars($order['email']) ?></div>
                                        <?php if ($order['phonenumber']): ?>
                                            <div class="customer-info">📞 <?= htmlspecialchars($order['phonenumber']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <strong><?= $order['items_count'] ?> items</strong>
                                        <?php if (!empty($order['items'])): ?>
                                            <ul class="order-items">
                                                <?php foreach ($order['items'] as $item): ?>
                                                    <li><?= htmlspecialchars($item['product_name_en']) ?> × <?= $item['quantity'] ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $order['total_amount_formatted'] ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select class="status-select" id="status-<?= $order['order_id'] ?>">
                                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button class="btn-update" onclick="updateOrderStatus(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-sync-alt"></i> Update
                                        </button>
                                        
                                        <div style="margin-top: 8px;">
                                            <select class="status-select" id="order-type-<?= $order['order_id'] ?>" style="background: #f0f9ff;">
                                                <option value="customer" <?= ($order['order_type'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>Customer Order</option>
                                                <option value="supplier" <?= ($order['order_type'] ?? 'customer') === 'supplier' ? 'selected' : '' ?>>Supplier Order</option>
                                            </select>
                                            <button class="btn-update" onclick="updateOrderType(<?= $order['order_id'] ?>)" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                                                <i class="fas fa-tag"></i> Update Type
                                            </button>
                                        </div>
                                        
                                        <div style="margin-top: 8px;">
                                            <button class="btn-print" onclick="printInvoice(<?= $order['order_id'] ?>)" style="width: 100%;">
                                                <i class="fas fa-print"></i> Print Invoice
                                            </button>
                                        </div>
                                        
                                        <div style="margin-top: 8px;">
                                            <a href="daily_reports.php" class="btn-print" style="width: 100%; display: inline-block; text-align: center; text-decoration: none; background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                                <i class="fas fa-chart-line"></i> View Reports
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Products Tab -->
        <div id="products-tab" class="tab-content">
            <div class="section">
                <h2><i class="fas fa-boxes"></i> Products & Pricing Management</h2>
                
                <div id="products-alert"></div>
                
                <?php if (empty($products)): ?>
                    <p style="text-align: center; padding: 3rem; color: var(--text-gray); font-size: 1.125rem;">
                        <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                        No products found.
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Current Price</th>
                                <th>Discount Status</th>
                                <th>Stock</th>
                                <th>New Price (JOD)</th>
                                <th>Discount (%)</th>
                                <th>Price / Discount</th>
                                <th>Edit / Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong style="color: var(--accent-blue);">#<?= $product['id'] ?></strong></td>
                                    <td>
                                        <div class="product-name"><?= htmlspecialchars($product['name_en']) ?></div>
                                        <div style="color: var(--text-gray); font-size: 0.875rem; margin-top: 0.25rem;"><?= htmlspecialchars($product['name_ar']) ?></div>
                                    </td>
                                    <td>
                                        <div class="product-price">
                                            <strong style="font-size: 1.0625rem; color: var(--text-dark);"><?= $product['price_formatted'] ?></strong>
                                            <?php if ($product['has_discount'] && isset($product['original_price'])): ?>
                                                <span class="original-price"><?= formatJOD($product['original_price']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($product['has_discount']): ?>
                                            <span class="discount-badge">
                                                <i class="fas fa-tag"></i> <?= number_format($product['discount_percentage'], 0) ?>% OFF
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-gray); font-size: 0.875rem;">
                                                <i class="fas fa-minus-circle"></i> No discount
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: var(--text-dark);">
                                            <i class="fas fa-boxes"></i> <?= $product['stock_quantity'] ?>
                                        </span>
                                        <span style="color: var(--text-gray); font-size: 0.875rem;">units</span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="price-input" 
                                               id="price-<?= $product['id'] ?>" 
                                               value="<?= $product['price_jod'] ?>" 
                                               step="0.001" 
                                               min="0">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="price-input" 
                                               id="discount-<?= $product['id'] ?>" 
                                               placeholder="e.g., 20" 
                                               step="0.1" 
                                               min="0" 
                                               max="100">
                                    </td>
                                    <td>
                                        <button class="btn-update" onclick="updateProductPrice(<?= $product['id'] ?>)">
                                            <i class="fas fa-dollar-sign"></i> Update Price
                                        </button>
                                        <button class="btn-discount" onclick="applyDiscount(<?= $product['id'] ?>)">
                                            <i class="fas fa-percent"></i> Apply Discount
                                        </button>
                                        <?php if ($product['has_discount']): ?>
                                            <button class="btn-remove-discount" onclick="removeDiscount(<?= $product['id'] ?>)">
                                                <i class="fas fa-times"></i> Remove Discount
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-update" style="display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;margin-bottom:.4rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn-remove-discount" onclick="deleteProduct(<?= $product['id'] ?>, this)" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function filterOrders(status) {
            const url = new URL(window.location.href);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            window.location.href = url.toString();
        }
        
        function showAlert(tabId, message, isSuccess) {
            const alertDiv = document.getElementById(tabId + '-alert');
            alertDiv.innerHTML = `<div class="alert ${isSuccess ? 'alert-success' : 'alert-error'}">${message}</div>`;
            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }
        
        async function updateOrderStatus(orderId) {
            const selectEl = document.getElementById('status-' + orderId);
            const newStatus = selectEl.value;
            const button = event.target;
            
            button.disabled = true;
            button.textContent = 'Updating...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_order_status');
                formData.append('order_id', orderId);
                formData.append('status', newStatus);
                
                const response = await fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    let message = `✅ Order #${orderId} status updated: ${result.old_status} → ${result.new_status}`;
                    if (result.stock_restored && result.restored_items) {
                        message += '<br>📦 Stock restored: ';
                        result.restored_items.forEach(item => {
                            message += `Product #${item.product_id} (+${item.quantity_restored} units) `;
                        });
                    }
                    showAlert('orders', message, true);
                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    showAlert('orders', `❌ Failed to update: ${result.error}`, false);
                    button.disabled = false;
                    button.textContent = 'Update';
                }
            } catch (error) {
                showAlert('orders', '❌ Error: ' + error.message, false);
                button.disabled = false;
                button.textContent = 'Update';
            }
        }
        
        async function updateOrderType(orderId) {
            const selectEl = document.getElementById('order-type-' + orderId);
            const orderType = selectEl.value;
            const button = event.target;
            
            button.disabled = true;
            button.textContent = 'Updating...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_order_type');
                formData.append('order_id', orderId);
                formData.append('order_type', orderType);
                
                const response = await fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('orders', `✅ Order #${orderId} type updated to: ${orderType}`, true);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showAlert('orders', `❌ Failed to update: ${result.error}`, false);
                    button.disabled = false;
                    button.textContent = 'Update Type';
                }
            } catch (error) {
                showAlert('orders', '❌ Error: ' + error.message, false);
                button.disabled = false;
                button.textContent = 'Update Type';
            }
        }
        
        function printInvoice(orderId) {
            const orderTypeEl = document.getElementById('order-type-' + orderId);
            const invoiceType = orderTypeEl.value;
            const url = `print_invoice.php?order_id=${orderId}&invoice_type=${invoiceType}`;
            window.open(url, '_blank');
        }
        
        async function updateProductPrice(productId) {
            const inputEl = document.getElementById('price-' + productId);
            const newPrice = parseFloat(inputEl.value);
            const button = event.target;
            
            if (isNaN(newPrice) || newPrice < 0) {
                showAlert('products', '❌ Please enter a valid price', false);
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Updating...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_product_price');
                formData.append('product_id', productId);
                formData.append('new_price', newPrice);
                
                const response = await fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('products', `✅ ${result.product_name}: ${result.old_price_formatted} → ${result.new_price_formatted}`, true);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showAlert('products', `❌ Failed to update: ${result.error}`, false);
                    button.disabled = false;
                    button.textContent = 'Update Price';
                }
            } catch (error) {
                showAlert('products', '❌ Error: ' + error.message, false);
                button.disabled = false;
                button.textContent = 'Update Price';
            }
        }
        
        async function applyDiscount(productId) {
            const inputEl = document.getElementById('discount-' + productId);
            const discountPercentage = parseFloat(inputEl.value);
            const button = event.target;
            
            if (isNaN(discountPercentage) || discountPercentage < 0 || discountPercentage > 100) {
                showAlert('products', '❌ Please enter a valid discount percentage (0-100)', false);
                return;
            }
            
            if (discountPercentage === 0) {
                showAlert('products', '❌ Please enter a discount percentage greater than 0', false);
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Applying...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'apply_discount');
                formData.append('product_id', productId);
                formData.append('discount_percentage', discountPercentage);
                
                const response = await fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('products', `✅ ${result.product_name}: ${discountPercentage}% discount applied! ${result.old_price_formatted} → ${result.new_price_formatted}`, true);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showAlert('products', `❌ Failed to apply discount: ${result.error}`, false);
                    button.disabled = false;
                    button.textContent = 'Apply Discount';
                }
            } catch (error) {
                showAlert('products', '❌ Error: ' + error.message, false);
                button.disabled = false;
                button.textContent = 'Apply Discount';
            }
        }
        
        async function removeDiscount(productId) {
            const button = event.target;
            
            if (!confirm('Are you sure you want to remove the discount from this product?')) {
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Removing...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove_discount');
                formData.append('product_id', productId);
                
                const response = await fetch('admin_panel.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('products', `✅ ${result.product_name}: Discount removed! Price restored to ${result.restored_price_formatted}`, true);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showAlert('products', `❌ Failed to remove discount: ${result.error}`, false);
                    button.disabled = false;
                    button.textContent = 'Remove Discount';
                }
            } catch (error) {
                showAlert('products', '❌ Error: ' + error.message, false);
                button.disabled = false;
                button.textContent = 'Remove Discount';
            }
        }

        async function deleteProduct(productId, btn) {
            if (!confirm('Permanently delete this product? This cannot be undone.')) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const fd = new FormData();
                fd.append('action', 'delete_product');
                fd.append('product_id', productId);
                const res = await fetch('admin_panel.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    btn.closest('tr').style.opacity = '0';
                    btn.closest('tr').style.transition = 'opacity .4s';
                    setTimeout(() => btn.closest('tr').remove(), 400);
                    showAlert('products', '✅ Product deleted successfully.', true);
                } else {
                    showAlert('products', '❌ ' + (data.error || 'Delete failed'), false);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                }
            } catch (e) {
                showAlert('products', '❌ Network error', false);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            }
        }
    </script>
</body>
</html>
