<?php
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/checkout.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT firstname, lastname, email, phonenumber FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$orders_result = getUserOrders($user_id, 50);
$orders = $orders_result['orders'] ?? [];

// Add wallet usage information to each order
foreach ($orders as &$order) {
    $wallet_check_sql = "SELECT ABS(amount) as wallet_used FROM wallet_transactions 
                        WHERE user_id = ? AND reference_id = ? AND transaction_type = 'order_payment' 
                        ORDER BY created_at DESC LIMIT 1";
    $wallet_check_stmt = $conn->prepare($wallet_check_sql);
    $wallet_check_stmt->bind_param('ii', $user_id, $order['order_id']);
    $wallet_check_stmt->execute();
    $wallet_check_result = $wallet_check_stmt->get_result();
    if ($wallet_check_row = $wallet_check_result->fetch_assoc()) {
        $order['wallet_used'] = (float)$wallet_check_row['wallet_used'];
        $order['wallet_used_formatted'] = formatJOD($order['wallet_used']);
        $order['original_total'] = $order['total_amount'] + $order['wallet_used'];
        $order['original_total_formatted'] = formatJOD($order['original_total']);
    } else {
        $order['wallet_used'] = 0;
        $order['wallet_used_formatted'] = formatJOD(0);
        $order['original_total'] = $order['total_amount'];
        $order['original_total_formatted'] = $order['total_amount_formatted'];
    }
    $wallet_check_stmt->close();
}
unset($order); // Break reference
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_lang === 'ar' ? 'طلباتي' : 'My Orders' ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .customer-info {
            background: linear-gradient(135deg, rgba(252, 248, 242, 0.5), rgba(228, 212, 180, 0.3));
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--gold-color);
        }
        
        .customer-info-title {
            font-weight: 600;
            color: var(--purple-color);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .customer-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        
        .order-card {
            border: 2px solid rgba(201, 168, 106, 0.3);
            border-radius: 12px;
            padding: 0;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
            overflow: hidden;
            background: white;
        }
        
        .order-card:hover {
            border-color: var(--gold-color);
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.3);
        }
        
        .order-card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(252, 248, 242, 0.5), rgba(228, 212, 180, 0.3));
            border-bottom: 2px solid rgba(201, 168, 106, 0.3);
        }
        
        .order-card-body {
            padding: 1.5rem;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        
        .order-date {
            color: var(--gold-color);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        
        .order-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-tracking {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .tracking-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .tracking-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        
        .tracking-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .tracking-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }
        
        .tracking-step:last-child::before {
            display: none;
        }
        
        .tracking-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            position: relative;
            z-index: 1;
            margin-bottom: 0.5rem;
        }
        
        .tracking-step.completed .tracking-icon {
            background: #28a745;
        }
        
        .tracking-step.active .tracking-icon {
            background: var(--purple-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .tracking-step.completed::before {
            background: #28a745;
        }
        
        .tracking-label {
            font-size: 0.75rem;
            color: #666;
            font-weight: 500;
        }
        
        .tracking-step.completed .tracking-label,
        .tracking-step.active .tracking-label {
            color: #333;
            font-weight: 600;
        }
        
        .order-items {
            margin-top: 1rem;
        }
        
        .order-items-header {
            font-weight: 600;
            color: var(--purple-color);
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }
        
        .order-item {
            display: flex;
            align-items: start;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 0.8rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .order-item:hover {
            background: linear-gradient(135deg, rgba(252, 248, 242, 0.3), rgba(228, 212, 180, 0.2));
            border-color: var(--gold-color);
        }
        
        .item-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-right: 1rem;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }
        
        .item-image-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .item-image-slide.active {
            opacity: 1;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--gold-color);
            margin-bottom: 0.3rem;
            font-size: 1.05rem;
        }
        
        .item-name-ar {
            font-size: 0.9rem;
            color: var(--gold-color);
            direction: rtl;
            margin-bottom: 0.5rem;
            font-family: 'Tajawal', sans-serif;
        }
        
        .item-description {
            font-size: 0.85rem;
            color: #777;
            line-height: 1.4;
            margin-bottom: 0.5rem;
            max-width: 500px;
        }
        
        .item-quantity {
            font-size: 0.9rem;
            color: var(--purple-color);
            font-weight: 600;
            background: rgba(201, 168, 106, 0.1);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            display: inline-block;
        }
        
        .item-stock {
            font-size: 0.8rem;
            color: #28a745;
            margin-top: 0.3rem;
        }
        
        .item-price {
            text-align: right;
            color: var(--purple-color);
            font-weight: 600;
            margin-left: auto;
            padding-left: 1rem;
        }
        
        .item-unit-price {
            font-size: 0.85rem;
            color: var(--gold-color);
            margin-bottom: 0.3rem;
        }
        
        .item-total-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--purple-color);
        }
        
        .order-summary {
            background: #fff8e1;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.95rem;
        }
        
        .summary-row.total {
            border-top: 2px solid rgba(201, 168, 106, 0.3);
            margin-top: 0.5rem;
            padding-top: 0.8rem;
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--purple-color);
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(201, 168, 106, 0.2);
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-reorder {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            color: black !important;
            text-decoration: none !important;
        }
        
        .btn-reorder:hover {
            background: linear-gradient(135deg, var(--gold-color), var(--gold-light));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.4);
            color: black !important;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: black !important;
        }
        
        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: black !important;
        }
        
        .btn-details {
            background: transparent;
            color: black !important;
            border: 2px solid var(--purple-color);
            text-decoration: none !important;
        }
        
        .btn-details:hover {
            background: var(--purple-color);
            color: black !important;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h2 {
            color: var(--purple-color);
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .shop-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .shop-btn:hover {
            background: linear-gradient(135deg, var(--gold-color), var(--gold-light));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.4);
        }
        
        @media (max-width: 768px) {
            .customer-details {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
        }
        
        /* Cancel Confirmation Modal */
        .cancel-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(45, 19, 44, 0.85);
            z-index: 9999;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }
        
        .cancel-modal-overlay.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .cancel-modal {
            background: var(--cream-color);
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.3s ease-out;
        }
        
        .cancel-modal-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            padding: 1.5rem 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .cancel-modal-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .cancel-modal-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .cancel-modal-body {
            padding: 2rem;
        }
        
        .cancel-warning-box {
            background: linear-gradient(135deg, #fff3cd, #fff9e6);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cancel-warning-title {
            color: #856404;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cancel-warning-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .cancel-warning-list li {
            color: #856404;
            padding: 0.5rem 0;
            display: flex;
            align-items: start;
            gap: 0.5rem;
        }
        
        .cancel-warning-list li i {
            margin-top: 0.2rem;
            color: #dc3545;
        }
        
        .cancel-modal-actions {
            display: flex;
            gap: 1rem;
        }
        
        .cancel-modal-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cancel-btn-confirm {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .cancel-btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }
        
        .cancel-btn-back {
            background: white;
            color: var(--purple-color);
            border: 2px solid var(--purple-color);
        }
        
        .cancel-btn-back:hover {
            background: var(--purple-color);
            color: white;
        }
        
        @media (max-width: 576px) {
            .cancel-modal-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container py-5">
        <h2 class="section-title-ramadan text-center mb-4">
            <i class="fas fa-box me-2" style="color: var(--gold-color);"></i>My Orders
        </h2>
        <p class="text-center mb-5" style="color: var(--gold-color);">Track and manage your complete order information</p>

        <?php if ($user_info): ?>
        <div class="card-ramadan p-4 mb-4">
            <h4 class="mb-3" style="color: var(--purple-color); font-weight: 600;">
                <i class="fas fa-user-circle me-2" style="color: var(--gold-color);"></i>Customer Information
            </h4>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <strong style="color: var(--gold-color);">Name:</strong> <?= htmlspecialchars($user_info['firstname'] . ' ' . $user_info['lastname']) ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong style="color: var(--gold-color);">Email:</strong> <?= htmlspecialchars($user_info['email']) ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong style="color: var(--gold-color);">Phone:</strong> <?= htmlspecialchars($user_info['phonenumber']) ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong style="color: var(--gold-color);"><?= t('total_orders') ?>:</strong> <?= count(array_filter($orders, function($o) { return !empty($o['items']); })) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card-ramadan p-4">
            <?php 
            // Filter out orders with no items
            $orders_with_items = array_filter($orders, function($o) { 
                return !empty($o['items']) && count($o['items']) > 0; 
            });
            ?>
            
            <?php if (empty($orders_with_items)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2><?= t('no_orders') ?></h2>
                    <p><?= $current_lang === 'ar' ? 'ابدأ التسوق لتقديم طلبك الأول!' : 'Start shopping to place your first order!' ?></p>
                    <a href="../../index.php" class="btn-ramadan">
                        <i class="fas fa-shopping-bag me-2" style="color: white;"></i><?= $current_lang === 'ar' ? 'ابدأ التسوق' : 'Start Shopping' ?>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders_with_items as $order): ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div class="order-header">
                                <div>
                                    <div class="order-date" style="font-size: 1.1rem; font-weight: 600; color: var(--purple-color);">
                                        📅 <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="order-status status-<?= $order['status'] ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-card-body">
                            <!-- Order Tracking Timeline -->
                            <div class="order-tracking">
                                <div class="tracking-title">📍 <?= $current_lang === 'ar' ? 'تتبع الطلب' : 'Order Tracking' ?></div>
                                <div class="tracking-steps">
                                    <?php 
                                    $statuses = [
                                        'pending' => $current_lang === 'ar' ? 'قيد المعالجة' : 'Pending',
                                        'shipped' => $current_lang === 'ar' ? 'تم الشحن' : 'Shipped',
                                        'delivered' => $current_lang === 'ar' ? 'تم التوصيل' : 'Delivered'
                                    ];
                                    $cancelled = $order['status'] === 'cancelled';
                                    $current_status = $order['status'];
                                    $status_order = ['pending', 'shipped', 'delivered'];
                                    $current_index = array_search($current_status, $status_order);
                                    
                                    if (!$cancelled) {
                                        foreach ($statuses as $key => $label):
                                            $step_index = array_search($key, $status_order);
                                            $class = '';
                                            if ($step_index < $current_index || ($step_index == $current_index && $current_status != 'pending')) {
                                                $class = 'completed';
                                            } elseif ($step_index == $current_index) {
                                                $class = 'active';
                                            }
                                    ?>
                                        <div class="tracking-step <?= $class ?>">
                                            <div class="tracking-icon">
                                                <?php 
                                                if ($class == 'completed') echo '✓';
                                                elseif ($class == 'active') echo '●';
                                                else echo '○';
                                                ?>
                                            </div>
                                            <div class="tracking-label"><?= $label ?></div>
                                        </div>
                                    <?php 
                                        endforeach;
                                    } else {
                                        echo '<div style="text-align: center; color: #dc3545; font-weight: 600;">❌ Order Cancelled</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Gift Information -->
                            <?php if (isset($order['is_gift']) && $order['is_gift'] == 1): ?>
                                <div class="gift-info" style="background: linear-gradient(135deg, #fff5f8 0%, #fffaf0 100%); border: 3px solid var(--gold-color); border-radius: 16px; padding: 2rem; margin: 1.5rem 0; box-shadow: 0 8px 24px rgba(212, 175, 55, 0.15); position: relative; overflow: hidden;">
                                    <!-- Decorative corner elements -->
                                    <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; color: rgba(212, 175, 55, 0.1); transform: rotate(15deg);">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div style="position: absolute; bottom: -15px; left: -15px; font-size: 3rem; color: rgba(72, 54, 110, 0.08); transform: rotate(-20deg);">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; margin-bottom: 1.5rem; position: relative; z-index: 1;">
                                        <div style="background: linear-gradient(135deg, var(--gold-color), #f4d78c); padding: 1rem; border-radius: 50%; margin-right: 1rem; box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);">
                                            <i class="fas fa-gift" style="font-size: 2rem; color: white;"></i>
                                        </div>
                                        <div>
                                            <h5 style="color: var(--purple-color); margin: 0; font-weight: 800; font-size: 1.3rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">
                                                <i class="fas fa-sparkles" style="color: var(--gold-color); font-size: 0.8rem;"></i> Special Gift Order
                                            </h5>
                                            <small style="color: #888; font-weight: 500;"><i class="fas fa-box-heart" style="color: var(--gold-color);"></i> Premium gift wrapping included</small>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($order['gift_recipient_name'])): ?>
                                        <div style="background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%); margin-bottom: 1.25rem; padding: 1.25rem; border-radius: 12px; border: 2px solid rgba(212, 175, 55, 0.3); box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative; z-index: 1;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(72, 54, 110, 0.3);">
                                                    <i class="fas fa-user" style="color: var(--gold-color); font-size: 1.3rem;"></i>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="color: var(--gold-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.25rem;">
                                                        <i class="fas fa-tag" style="font-size: 0.75rem;"></i> Gift Recipient
                                                    </div>
                                                    <div style="color: var(--purple-color); font-weight: 800; font-size: 1.5rem; line-height: 1.2;">
                                                        <?= htmlspecialchars($order['gift_recipient_name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($order['gift_message'])): ?>
                                        <div style="background: linear-gradient(135deg, #ffffff 0%, #fffef9 100%); padding: 1.5rem; border-radius: 12px; border-left: 5px solid var(--gold-color); box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative; z-index: 1;">
                                            <div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                                                <i class="fas fa-quote-left" style="color: var(--gold-color); font-size: 1.5rem; margin-right: 0.75rem; opacity: 0.5;"></i>
                                                <strong style="color: var(--gold-color); font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    Personal Message
                                                </strong>
                                            </div>
                                            <div style="padding-left: 2.25rem; position: relative;">
                                                <p style="color: #555; margin: 0; font-style: italic; line-height: 1.8; font-size: 1.05rem; font-family: 'Georgia', serif;">
                                                    <?= nl2br(htmlspecialchars($order['gift_message'])) ?>
                                                </p>
                                                <i class="fas fa-quote-right" style="color: var(--gold-color); font-size: 1.5rem; position: absolute; bottom: -10px; right: 10px; opacity: 0.5;"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="margin-top: 1.25rem; padding: 0.75rem; background: rgba(212, 175, 55, 0.1); border-radius: 8px; text-align: center; position: relative; z-index: 1;">
                                        <i class="fas fa-heart" style="color: #e74c3c; margin-right: 0.5rem;"></i>
                                        <small style="color: var(--purple-color); font-weight: 600; font-style: italic;">Made with love and care ✨</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($order['items'])): ?>
                                <div class="order-items">
                                    <div class="order-items-header">
                                        📦 Order Items: <?= count($order['items']) ?> Product<?= count($order['items']) != 1 ? 's' : '' ?> 
                                        (<?= $order['items_count'] ?> Total Items)
                                    </div>
                                    
                                    <?php 
                                    $photo_gradients = [
                        'linear-gradient(135deg, var(--purple-color), var(--purple-dark))',
                        'linear-gradient(135deg, var(--gold-color), var(--gold-light))',
                        'linear-gradient(135deg, #f093fb, #f5576c)',
                        'linear-gradient(135deg, #4facfe, #00f2fe)',
                        'linear-gradient(135deg, #43e97b, #38f9d7)'
                    ];
                    $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎', '🎁', '👑'];
                                    foreach ($order['items'] as $item): 
                                        // Create multiple icons for carousel effect
                                        $product_icons = [];
                                        for ($i = 0; $i < 3; $i++) {
                                            $product_icons[] = $icons[($item['product_id'] + $i) % count($icons)];
                                        }
                                    ?>
                                        <div class="order-item">
                                            <div class="item-icon" id="item-<?= $item['product_id'] ?>-<?= $order['order_id'] ?>">
                                                <?php foreach ($product_icons as $idx => $icon): ?>
                                                    <div class="item-image-slide <?= $idx === 0 ? 'active' : '' ?>" 
                                                         style="background: <?= $photo_gradients[$idx % count($photo_gradients)] ?>">
                                                        <?= $icon ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="item-info">
                                                <div class="item-name">
                                                    🏷️ <?= htmlspecialchars($item['product_name_en']) ?>
                                                </div>
                                                <div class="item-name-ar"><?= htmlspecialchars($item['product_name_ar']) ?></div>
                                                <?php if (!empty($item['description'])): ?>
                                                    <div class="item-description">
                                                        <?= htmlspecialchars(substr($item['description'], 0, 100)) ?><?= strlen($item['description']) > 100 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="item-quantity">
                                                    📊 Quantity: <?= $item['quantity'] ?> × <?= $item['price_formatted'] ?>
                                                </div>
                                                <?php if (isset($item['stock_quantity'])): ?>
                                                    <div class="item-stock">
                                                        ✓ Current Stock: <?= $item['stock_quantity'] ?> available
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-price">
                                                <div class="item-unit-price"><?= $item['price_formatted'] ?> each</div>
                                                <div class="item-total-price"><?= $item['subtotal_formatted'] ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Order Summary -->
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Subtotal (<?= count($order['items']) ?> products):</span>
                                    <span><?= $order['original_total_formatted'] ?></span>
                                </div>
                                <?php if ($order['wallet_used'] > 0): ?>
                                <div class="summary-row" style="color: #1890ff;">
                                    <span><i class="fas fa-wallet me-1"></i>Wallet Credit Used:</span>
                                    <span style="font-weight: 600;">-<?= $order['wallet_used_formatted'] ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-row">
                                    <span>Shipping:</span>
                                    <span style="color: #28a745; font-weight: 600;">FREE</span>
                                </div>
                                <div class="summary-row">
                                    <span>Tax:</span>
                                    <span>Included</span>
                                </div>
                                <div class="summary-row total">
                                    <span><?= $order['wallet_used'] > 0 ? 'Amount Paid:' : 'Total Amount:' ?></span>
                                    <span><?= $order['total_amount_formatted'] ?></span>
                                </div>
                            </div>
                            
                            <!-- Order Actions -->
                            <div class="order-actions">
                                <?php if (!empty($order['items'])): ?>
                                <a href="product_detail.php?id=<?= $order['items'][0]['product_id'] ?>" class="action-btn btn-details">
                                    <i class="fas fa-file-alt" style="color: var(--gold-color);"></i> View Products
                                </a>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="action-btn btn-cancel" onclick="cancelOrder(<?= $order['order_id'] ?>)">
                                        <i class="fas fa-times-circle" style="color: white;"></i> Cancel Order
                                    </button>
                                <?php endif; ?>
                                <a href="../../index.php" class="action-btn btn-reorder">
                                    <i class="fas fa-shopping-bag" style="color: var(--gold-color);"></i> Shop Again
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Product Image Carousel for Order Items
        function initItemCarousels() {
            const itemIcons = document.querySelectorAll('.item-icon');
            
            itemIcons.forEach(icon => {
                const slides = icon.querySelectorAll('.item-image-slide');
                if (slides.length > 1) {
                    let currentSlide = 0;
                    
                    setInterval(() => {
                        slides[currentSlide].classList.remove('active');
                        currentSlide = (currentSlide + 1) % slides.length;
                        slides[currentSlide].classList.add('active');
                    }, 2500); // Change every 2.5 seconds
                }
            });
        }
        
        // Initialize carousels when page loads
        document.addEventListener('DOMContentLoaded', initItemCarousels);
        
        function cancelOrder(orderId) {
            // Store order ID for later use
            window.pendingCancelOrderId = orderId;
            
            // Show modal
            document.getElementById('cancelModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('active');
            document.body.style.overflow = '';
            window.pendingCancelOrderId = null;
        }
        
        function confirmCancelOrder() {
            const orderId = window.pendingCancelOrderId;
            if (!orderId) return;
            
            // Close modal
            closeCancelModal();
            
            // Show loading state
            const loadingMsg = document.createElement('div');
            loadingMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #2d132c, #483670); color: white; padding: 1rem 1.5rem; border-radius: 12px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3);';
            loadingMsg.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelling order...';
            document.body.appendChild(loadingMsg);
            
            fetch('../../api/cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                loadingMsg.remove();
                
                if (data.success) {
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #28a745, #218838); color: white; padding: 1rem 1.5rem; border-radius: 12px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3);';
                    successMsg.innerHTML = '<i class="fas fa-check-circle me-2"></i>Order cancelled successfully!';
                    document.body.appendChild(successMsg);
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    const errorMsg = document.createElement('div');
                    errorMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 1rem 1.5rem; border-radius: 12px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3);';
                    errorMsg.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.error || 'Failed to cancel order');
                    document.body.appendChild(errorMsg);
                    
                    setTimeout(() => {
                        errorMsg.remove();
                    }, 3000);
                }
            })
            .catch(error => {
                loadingMsg.remove();
                
                const errorMsg = document.createElement('div');
                errorMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 1rem 1.5rem; border-radius: 12px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3);';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Network error. Please try again.';
                document.body.appendChild(errorMsg);
                
                setTimeout(() => {
                    errorMsg.remove();
                }, 3000);
            });
        }
    </script>
    
    <!-- Cancel Order Confirmation Modal -->
    <div id="cancelModal" class="cancel-modal-overlay" onclick="if(event.target === this) closeCancelModal()">
        <div class="cancel-modal">
            <div class="cancel-modal-header">
                <div class="cancel-modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="cancel-modal-title">Cancel Order Confirmation</h3>
            </div>
            
            <div class="cancel-modal-body">
                <div class="cancel-warning-box">
                    <div class="cancel-warning-title">
                        <i class="fas fa-info-circle"></i>
                        What will happen:
                    </div>
                    <ul class="cancel-warning-list">
                        <li>
                            <i class="fas fa-times-circle"></i>
                            <span>Your order will be <strong>cancelled immediately</strong></span>
                        </li>
                        <li>
                            <i class="fas fa-ban"></i>
                            <span>This action <strong>cannot be undone</strong></span>
                        </li>
                    </ul>
                </div>
                
                <p style="text-align: center; color: #666; margin-bottom: 1.5rem; font-size: 1.1rem;">
                    Are you sure you want to proceed?
                </p>
                
                <div class="cancel-modal-actions">
                    <button class="cancel-modal-btn cancel-btn-back" onclick="closeCancelModal()">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </button>
                    <button class="cancel-modal-btn cancel-btn-confirm" onclick="confirmCancelOrder()">
                        <i class="fas fa-check me-2"></i>Yes, Cancel Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
