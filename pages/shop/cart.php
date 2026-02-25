<?php
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/../../includes/product_image_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove':
                if (isset($_POST['cart_id'])) {
                    $result = removeFromCart($_POST['cart_id']);
                    $message = $result['success'] ? t('item_removed') : $result['error'];
                }
                break;
            
            case 'update_quantity':
                if (isset($_POST['cart_id']) && isset($_POST['quantity'])) {
                    $result = updateCartQuantity($_POST['cart_id'], $_POST['quantity']);
                    $message = $result['success'] ? t('quantity_updated') : $result['error'];
                }
                break;
            
            case 'clear':
                $result = clearCart();
                $message = $result['success'] ? t('cart_cleared') : $result['error'];
                break;
        }
        header('Location: cart.php?msg=' . urlencode($message ?? ''));
        exit;
    }
}

// Get cart contents
$cart = viewCart();
$cart_items = $cart['cart_items'] ?? [];
$total_amount = $cart['total_amount_formatted'] ?? '0.000 JOD';
$total_amount_raw = $cart['total_amount'] ?? 0;
$total_items = $cart['total_items'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('shopping_cart') ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        .cart-item {
            background: linear-gradient(135deg, #ffffff 0%, #fffbf8 100%);
            border: 2px solid rgba(201, 168, 106, 0.3);
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            display: flex;
            gap: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, var(--purple-color), var(--gold-color));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .cart-item:hover {
            border-color: var(--gold-color);
            box-shadow: 0 8px 25px rgba(201, 168, 106, 0.3);
            transform: translateY(-3px);
        }
        
        .cart-item:hover::before {
            opacity: 1;
        }
        
        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            border: 3px solid var(--gold-color);
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.3);
            position: relative;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #faf8f5;
            padding: 4px;
        }
        
        .item-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            font-size: 3rem;
        }
        
        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .item-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--purple-color);
            margin: 0;
            line-height: 1.3;
        }
        
        .item-name-ar {
            font-family: 'Tajawal', sans-serif;
            color: var(--gold-color);
            font-size: 1rem;
            font-weight: 600;
        }
        
        .item-price {
            color: #555;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .item-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: auto;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            border: 2px solid rgba(201, 168, 106, 0.3);
        }
        
        .quantity-control button {
            width: 36px;
            height: 36px;
            border: 2px solid var(--gold-color);
            background: white;
            color: var(--gold-color);
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-control button:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--gold-color), #f4d78c);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(201, 168, 106, 0.4);
        }
        
        .quantity-control button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .quantity-control input {
            width: 70px;
            text-align: center;
            border: none;
            background: transparent;
            border-radius: 6px;
            padding: 0.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--purple-color);
        }
        
        .remove-btn {
            padding: 0.6rem 1.25rem;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }
        
        .remove-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .cart-summary {
            background: linear-gradient(135deg, #ffffff 0%, #fffbf8 100%);
            border: 3px solid var(--gold-color);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(201, 168, 106, 0.25);
            height: fit-content;
            position: sticky;
            top: 100px;
            overflow: hidden;
        }
        
        .cart-summary::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(201, 168, 106, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .cart-summary::after {
            content: '🌙';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2rem;
            opacity: 0.1;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid rgba(201, 168, 106, 0.15);
            position: relative;
            z-index: 1;
        }
        
        .summary-total {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .coupon-section {
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border: 2px dashed var(--gold-color);
            border-radius: 12px;
            position: relative;
            z-index: 1;
        }
        
        .coupon-input-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .coupon-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .coupon-input:focus {
            border-color: var(--gold-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 168, 106, 0.1);
        }
        
        .apply-coupon-btn {
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--gold-color), #b8935f);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .apply-coupon-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.4);
        }
        
        .apply-coupon-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .coupon-message {
            margin-top: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            display: none;
        }
        
        .coupon-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .coupon-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .discount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-radius: 10px;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .discount-row .remove-coupon-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: all 0.2s;
        }
        
        .discount-row .remove-coupon-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        
        .clear-cart-btn {
            width: 100%;
            padding: 0.875rem;
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            margin-top: 1rem;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }
        
        .clear-cart-btn:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .empty-cart {
            text-align: center;
            padding: 5rem 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #fffbf8 100%);
            border-radius: 16px;
            border: 3px dashed var(--gold-color);
            position: relative;
        }
        
        .empty-cart::before {
            content: '✨';
            position: absolute;
            top: 30px;
            left: 30px;
            font-size: 2rem;
            opacity: 0.3;
            animation: twinkle 2s infinite;
        }
        
        .empty-cart::after {
            content: '🌙';
            position: absolute;
            bottom: 30px;
            right: 30px;
            font-size: 2rem;
            opacity: 0.3;
            animation: twinkle 2.5s infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        
        .empty-cart-icon {
            font-size: 6rem;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .stock-warning {
            color: #dc3545;
            font-size: 0.9rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffe6e6, #fff0f0);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .price-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.4rem 0.75rem;
            background: linear-gradient(135deg, var(--gold-color), #f4d78c);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(201, 168, 106, 0.3);
        }
        
        .subtotal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.4rem 0.75rem;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            color: var(--gold-color);
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(72, 54, 110, 0.3);
        }
        
        @media (max-width: 991px) {
            .cart-summary {
                position: static;
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin: 0 auto;
            }
            
            .item-controls {
                flex-direction: column;
            }
            
            .cart-summary {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Progress Indicator -->
            <div style="max-width: 800px; margin: 0 auto 3rem auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
                    <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: rgba(201, 168, 106, 0.3); z-index: 0; transform: translateY(-50%);"></div>
                    <div style="position: absolute; top: 50%; left: 0; width: 33.33%; height: 3px; background: linear-gradient(90deg, var(--purple-color), var(--gold-color)); z-index: 0; transform: translateY(-50%);"></div>
                    
                    <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--purple-color), var(--gold-color)); margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(72, 54, 110, 0.3); animation: pulse 2s infinite;">
                            <i class="fas fa-shopping-cart" style="color: white; font-size: 1.2rem;"></i>
                        </div>
                        <small style="color: var(--gold-color); font-weight: 700; display: block; margin-top: 0.5rem;"><?= t('cart') ?></small>
                    </div>
                    
                    <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(201, 168, 106, 0.2); margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(201, 168, 106, 0.3);">
                            <i class="fas fa-credit-card" style="color: rgba(201, 168, 106, 0.5); font-size: 1.2rem;"></i>
                        </div>
                        <small style="color: #999; font-weight: 600; display: block; margin-top: 0.5rem;"><?= t('checkout') ?></small>
                    </div>
                    
                    <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(201, 168, 106, 0.2); margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(201, 168, 106, 0.3);">
                            <i class="fas fa-check-circle" style="color: rgba(201, 168, 106, 0.5); font-size: 1.2rem;"></i>
                        </div>
                        <small style="color: #999; font-weight: 600; display: block; margin-top: 0.5rem;"><?= t('complete') ?></small>
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes pulse {
                    0%, 100% { transform: scale(1); box-shadow: 0 4px 12px rgba(72, 54, 110, 0.3); }
                    50% { transform: scale(1.05); box-shadow: 0 6px 16px rgba(72, 54, 110, 0.5); }
                }
            </style>
            
            <div class="text-center mb-5">
                <h2 class="section-title-ramadan mb-2" style="font-size: 2.5rem;">
                    <i class="fas fa-shopping-cart me-3" style="color: var(--gold-color);"></i><?= t('shopping_cart') ?>
                </h2>
                <p style="color: var(--gold-color); font-size: 1.1rem; margin-bottom: 1rem;"><?= t('review_items_checkout') ?></p>
                <div style="width: 100px; height: 3px; background: linear-gradient(90deg, var(--purple-color), var(--gold-color), var(--purple-color)); margin: 0 auto; border-radius: 2px;"></div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert-ramadan alert-success mb-4"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="card-ramadan">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h3 style="color: var(--purple-color); font-weight: 700; font-size: 2rem; margin-bottom: 1rem;"><?= t('cart_empty') ?></h3>
                        <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;"><?= t('add_beautiful_cosmetics') ?></p>
                        <a href="../../index.php" class="btn-ramadan mt-3" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3);">
                            <i class="fas fa-shopping-bag me-2"></i><?= t('start_shopping') ?>
                        </a>
                        <div style="margin-top: 2rem; color: var(--gold-color); font-size: 0.95rem;">
                            <i class="fas fa-sparkles me-2"></i><?= t('discover_premium') ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8 mb-4">
                        <div style="background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 2px solid rgba(201, 168, 106, 0.2);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--purple-color); font-weight: 700;">
                                    <i class="fas fa-box-open me-2" style="color: var(--gold-color);"></i><?= count($cart_items) ?> <?= count($cart_items) != 1 ? t('items_in_cart') : t('item_in_cart') ?>
                                </span>
                                <span style="color: var(--gold-color); font-weight: 600;">
                                    <i class="fas fa-tag me-1"></i><?= t('total') ?>: <?= $total_amount ?>
                                </span>
                            </div>
                        </div>
                        <?php 
                        $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                        foreach ($cart_items as $item): 
                        ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <?php
                                        $cart_img = get_product_thumbnail(
                                            $item['name_en'] ?? '',
                                            $item['image_url'] ?? '',
                                            __DIR__ . '/../..'
                                        );
                                    ?>
                                    <?php if (!empty($cart_img)): ?>
                                        <img src="<?= htmlspecialchars($cart_img) ?>" alt="<?= htmlspecialchars($item['name_en']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <div class="item-image-placeholder" style="display:none;">
                                            <?= $icons[$item['product_id'] % count($icons)] ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="item-image-placeholder">
                                            <?= $icons[$item['product_id'] % count($icons)] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['name_en']) ?></div>
                                    <div class="item-name-ar"><?= htmlspecialchars($item['name_ar']) ?></div>
                                    <div class="item-price">
                                        <span class="price-badge"><?= $item['price_formatted'] ?></span>
                                        <span style="color: #999;">×</span>
                                        <span style="font-weight: 700; color: var(--purple-color);" class="item-qty" data-cart-id="<?= $item['cart_id'] ?>"><?= $item['quantity'] ?></span>
                                        <span style="color: #999;">=</span>
                                        <span class="subtotal-badge item-subtotal" data-cart-id="<?= $item['cart_id'] ?>"><?= $item['subtotal_formatted'] ?></span>
                                    </div>
                                    
                                    <?php if (!$item['in_stock']): ?>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?= t('out_of_stock_remove') ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="item-controls">
                                        <div class="quantity-control">
                                            <button type="button" onclick="updateCartQuantity(<?= $item['cart_id'] ?>, 'decrease')">−</button>
                                            <input type="number" class="qty-input" data-cart-id="<?= $item['cart_id'] ?>" data-stock="<?= $item['stock'] ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                                            <button type="button" class="qty-plus-btn" data-cart-id="<?= $item['cart_id'] ?>" onclick="updateCartQuantity(<?= $item['cart_id'] ?>, 'increase')" <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                        </div>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <button type="submit" class="remove-btn">
                                                <i class="fas fa-trash-alt me-1"></i><?= t('remove') ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h3 class="mb-4" style="color: var(--purple-color); font-family: 'Playfair Display', serif; position: relative; z-index: 1; font-weight: 700; font-size: 1.8rem;">
                                <i class="fas fa-receipt me-2" style="color: var(--gold-color);"></i><?= t('order_summary') ?>
                            </h3>
                            <div class="summary-row">
                                <span style="font-weight: 600; font-size: 1.05rem;">
                                    <i class="fas fa-box me-2" style="color: var(--gold-color);"></i><?= t('total_items') ?>:
                                </span>
                                <span style="color: var(--purple-color); font-weight: 700; font-size: 1.2rem;" id="totalItemsDisplay"><?= $total_items ?></span>
                            </div>
                            
                            <!-- Coupon Section -->
                            <div class="coupon-section">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-tag" style="color: var(--gold-color); font-size: 1.1rem;"></i>
                                    <span style="font-weight: 700; color: var(--purple-color); font-size: 0.95rem;"><?= t('have_coupon') ?></span>
                                </div>
                                <div class="coupon-input-group">
                                    <input type="text" 
                                           id="couponCode" 
                                           class="coupon-input" 
                                           placeholder="<?= t('enter_code') ?>" 
                                           maxlength="50">
                                    <button onclick="applyCoupon()" class="apply-coupon-btn" id="applyCouponBtn">
                                        <i class="fas fa-check"></i> <?= t('apply') ?>
                                    </button>
                                </div>
                                <div id="couponMessage" class="coupon-message"></div>
                            </div>
                            
                            <!-- Discount Display (hidden by default) -->
                            <div id="discountRow" class="discount-row" style="display: none;">
                                <div>
                                    <span style="font-weight: 700; color: #155724;">
                                        <i class="fas fa-percent me-1"></i><?= t('discount') ?> (<span id="couponCodeDisplay"></span>):
                                    </span>
                                </div>
                                <div>
                                    <span style="color: #155724; font-weight: 700; font-size: 1.1rem;" id="discountAmount">0.000 JOD</span>
                                    <button onclick="removeCoupon()" class="remove-coupon-btn" title="Remove coupon">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="summary-total">
                                <span style="color: white; font-weight: 700; font-size: 1.3rem;">
                                    <i class="fas fa-coins me-2" style="color: var(--gold-color);"></i><?= t('total') ?>:
                                </span>
                                <span style="color: var(--gold-color); font-size: 1.8rem; font-weight: 900; text-shadow: 0 2px 4px rgba(0,0,0,0.2);" 
                                      id="cartTotalDisplay" 
                                      data-raw-total="<?= $total_amount_raw ?>"><?= $total_amount ?></span>
                            </div>
                            
                            <a href="checkout_page.php" class="btn-ramadan w-100 mt-3" style="padding: 1.1rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3); transition: all 0.3s; position: relative; z-index: 1;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(72, 54, 110, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(72, 54, 110, 0.3)';">
                                <i class="fas fa-credit-card me-2"></i><?= t('proceed_to_checkout') ?>
                            </a>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="clear-cart-btn" onclick="return confirm('<?= t('clear_cart_confirm') ?>')">
                                    <i class="fas fa-trash me-2"></i><?= t('clear_cart') ?>
                                </button>
                            </form>
                            
                            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid rgba(201, 168, 106, 0.2); position: relative; z-index: 1;">
                                <small style="color: #666; font-size: 0.85rem;">
                                    <i class="fas fa-lock me-1" style="color: #28a745;"></i><?= t('secure_shopping') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update cart quantity without page refresh
        function updateCartQuantity(cartId, action) {
            // Get current quantity from input
            const qtyInput = document.querySelector(`.qty-input[data-cart-id="${cartId}"]`);
            if (!qtyInput) return;
            
            const currentQuantity = parseInt(qtyInput.value);
            const maxStock = parseInt(qtyInput.dataset.stock);
            
            // Calculate new quantity
            let newQuantity;
            if (action === 'increase') {
                newQuantity = currentQuantity + 1;
                if (newQuantity > maxStock) {
                    alert('<?= t("maximum_stock_reached") ?>');
                    return;
                }
            } else if (action === 'decrease') {
                newQuantity = Math.max(1, currentQuantity - 1);
            } else {
                return;
            }
            
            // Prevent unnecessary API calls
            if (newQuantity === currentQuantity) return;
            
            fetch('../../api/update_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update quantity display
                    const qtyDisplay = document.querySelector(`.item-qty[data-cart-id="${cartId}"]`);
                    if (qtyDisplay) {
                        qtyDisplay.textContent = data.new_quantity;
                    }
                    
                    // Update quantity input
                    if (qtyInput) {
                        qtyInput.value = data.new_quantity;
                    }
                    
                    // Update item subtotal
                    const subtotalDisplay = document.querySelector(`.item-subtotal[data-cart-id="${cartId}"]`);
                    if (subtotalDisplay) {
                        subtotalDisplay.textContent = data.item_subtotal;
                    }
                    
                    // Update cart total
                    const totalDisplay = document.getElementById('cartTotalDisplay');
                    if (totalDisplay) {
                        totalDisplay.textContent = data.cart_total;
                    }
                    
                    // Update total items count
                    const totalItemsDisplay = document.getElementById('totalItemsDisplay');
                    if (totalItemsDisplay) {
                        totalItemsDisplay.textContent = data.total_items;
                    }
                    
                    // Disable/enable plus button based on stock
                    const plusBtn = document.querySelector(`.qty-plus-btn[data-cart-id="${cartId}"]`);
                    if (plusBtn) {
                        plusBtn.disabled = data.at_max_stock;
                    }
                    
                    // Show success feedback with animation
                    const itemRow = qtyDisplay.closest('.cart-item');
                    if (itemRow) {
                        itemRow.style.transition = 'background 0.3s';
                        itemRow.style.background = 'linear-gradient(135deg, #e8f5e9 0%, #fff9f5 100%)';
                        setTimeout(() => {
                            itemRow.style.background = '';
                        }, 500);
                    }
                } else {
                    alert(data.error || '<?= t('failed_update_quantity') ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?= t('network_error') ?>');
            });
        }
        
        // Coupon functionality
        function applyCoupon() {
            const couponInput = document.getElementById('couponCode');
            const code = couponInput.value.trim();
            const applyBtn = document.getElementById('applyCouponBtn');
            const messageDiv = document.getElementById('couponMessage');
            
            if (!code) {
                showCouponMessage('<?= t('please_enter_coupon') ?>', false);
                return;
            }
            
            // Get current cart total from data attribute
            const cartTotalElement = document.getElementById('cartTotalDisplay');
            const cartTotal = parseFloat(cartTotalElement.getAttribute('data-raw-total') || 0);
            
            if (cartTotal <= 0) {
                showCouponMessage('<?= t('cart_is_empty') ?>', false);
                return;
            }
            
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= t("applying") ?>';
            
            fetch('../../api/apply_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=apply_coupon&code=${encodeURIComponent(code)}&cart_total=${cartTotal}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCouponMessage(data.message, true);
                    
                    // Show discount row
                    document.getElementById('discountRow').style.display = 'flex';
                    document.getElementById('couponCodeDisplay').textContent = code.toUpperCase();
                    document.getElementById('discountAmount').textContent = data.discount;
                    
                    // Update total
                    document.getElementById('cartTotalDisplay').textContent = data.new_total;
                    document.getElementById('cartTotalDisplay').setAttribute('data-raw-total', data.new_total_raw);
                    
                    // Hide coupon input section
                    document.querySelector('.coupon-section').style.display = 'none';
                } else {
                    showCouponMessage(data.error, false);
                }
            })
            .catch(error => {
                showCouponMessage('<?= t("network_error") ?>', false);
                console.error('Error:', error);
            })
            .finally(() => {
                applyBtn.disabled = false;
                applyBtn.innerHTML = '<i class="fas fa-check"></i> <?= t("apply") ?>';
            });
        }
        
        function removeCoupon() {
            fetch('../../api/apply_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_coupon'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to recalculate totals
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?= t("failed_remove_coupon") ?>');
            });
        }
        
        function showCouponMessage(message, isSuccess) {
            const messageDiv = document.getElementById('couponMessage');
            messageDiv.textContent = message;
            messageDiv.className = 'coupon-message ' + (isSuccess ? 'success' : 'error');
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                if (isSuccess) {
                    messageDiv.style.display = 'none';
                }
            }, 3000);
        }
        
        // Allow Enter key to apply coupon
        document.addEventListener('DOMContentLoaded', function() {
            const couponInput = document.getElementById('couponCode');
            if (couponInput) {
                couponInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        applyCoupon();
                    }
                });
            }
        });
    </script>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
