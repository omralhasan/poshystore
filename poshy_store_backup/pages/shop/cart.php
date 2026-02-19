<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/cart_handler.php';

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
                    $message = $result['success'] ? 'Item removed from cart' : $result['error'];
                }
                break;
            
            case 'update_quantity':
                if (isset($_POST['cart_id']) && isset($_POST['quantity'])) {
                    $result = updateCartQuantity($_POST['cart_id'], $_POST['quantity']);
                    $message = $result['success'] ? 'Quantity updated' : $result['error'];
                }
                break;
            
            case 'clear':
                $result = clearCart();
                $message = $result['success'] ? 'Cart cleared' : $result['error'];
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
$total_items = $cart['total_items'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Poshy Store</title>
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
            object-fit: cover;
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
                        <small style="color: var(--gold-color); font-weight: 700; display: block; margin-top: 0.5rem;">Cart</small>
                    </div>
                    
                    <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(201, 168, 106, 0.2); margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(201, 168, 106, 0.3);">
                            <i class="fas fa-credit-card" style="color: rgba(201, 168, 106, 0.5); font-size: 1.2rem;"></i>
                        </div>
                        <small style="color: #999; font-weight: 600; display: block; margin-top: 0.5rem;">Checkout</small>
                    </div>
                    
                    <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(201, 168, 106, 0.2); margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(201, 168, 106, 0.3);">
                            <i class="fas fa-check-circle" style="color: rgba(201, 168, 106, 0.5); font-size: 1.2rem;"></i>
                        </div>
                        <small style="color: #999; font-weight: 600; display: block; margin-top: 0.5rem;">Complete</small>
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
                    <i class="fas fa-shopping-cart me-3" style="color: var(--gold-color);"></i>Shopping Cart
                </h2>
                <p style="color: var(--gold-color); font-size: 1.1rem; margin-bottom: 1rem;">Review your items before checkout</p>
                <div style="width: 100px; height: 3px; background: linear-gradient(90deg, var(--purple-color), var(--gold-color), var(--purple-color)); margin: 0 auto; border-radius: 2px;"></div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert-ramadan alert-success mb-4"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="card-ramadan">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h3 style="color: var(--purple-color); font-weight: 700; font-size: 2rem; margin-bottom: 1rem;">Your cart is empty</h3>
                        <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">Add some beautiful cosmetics to get started!</p>
                        <a href="../../index.php" class="btn-ramadan mt-3" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3);">
                            <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                        </a>
                        <div style="margin-top: 2rem; color: var(--gold-color); font-size: 0.95rem;">
                            <i class="fas fa-sparkles me-2"></i>Discover our premium collection
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8 mb-4">
                        <div style="background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 2px solid rgba(201, 168, 106, 0.2);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--purple-color); font-weight: 700;">
                                    <i class="fas fa-box-open me-2" style="color: var(--gold-color);"></i><?= count($cart_items) ?> Item<?= count($cart_items) != 1 ? 's' : '' ?> in Cart
                                </span>
                                <span style="color: var(--gold-color); font-weight: 600;">
                                    <i class="fas fa-tag me-1"></i>Total: <?= $total_amount ?>
                                </span>
                            </div>
                        </div>
                        <?php 
                        $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                        foreach ($cart_items as $item): 
                        ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name_en']) ?>">
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
                                        <span style="font-weight: 700; color: var(--purple-color);"><?= $item['quantity'] ?></span>
                                        <span style="color: #999;">=</span>
                                        <span class="subtotal-badge"><?= $item['subtotal_formatted'] ?></span>
                                    </div>
                                    
                                    <?php if (!$item['in_stock']): ?>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Out of stock - please remove
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="item-controls">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <div class="quantity-control">
                                                <button type="submit" name="quantity" value="<?= max(1, $item['quantity'] - 1) ?>">−</button>
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                                                <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                            </div>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <button type="submit" class="remove-btn">
                                                <i class="fas fa-trash-alt me-1"></i>Remove
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
                                <i class="fas fa-receipt me-2" style="color: var(--gold-color);"></i>Order Summary
                            </h3>
                            <div class="summary-row">
                                <span style="font-weight: 600; font-size: 1.05rem;">
                                    <i class="fas fa-box me-2" style="color: var(--gold-color);"></i>Total Items:
                                </span>
                                <span style="color: var(--purple-color); font-weight: 700; font-size: 1.2rem;"><?= $total_items ?></span>
                            </div>
                            <div class="summary-total">
                                <span style="color: white; font-weight: 700; font-size: 1.3rem;">
                                    <i class="fas fa-coins me-2" style="color: var(--gold-color);"></i>Total:
                                </span>
                                <span style="color: var(--gold-color); font-size: 1.8rem; font-weight: 900; text-shadow: 0 2px 4px rgba(0,0,0,0.2);"><?= $total_amount ?></span>
                            </div>
                            
                            <a href="checkout_page.php" class="btn-ramadan w-100 mt-3" style="padding: 1.1rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3); transition: all 0.3s; position: relative; z-index: 1;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(72, 54, 110, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(72, 54, 110, 0.3)';">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="clear-cart-btn" onclick="return confirm('Clear all items from cart?')">
                                    <i class="fas fa-trash me-2"></i>Clear Cart
                                </button>
                            </form>
                            
                            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid rgba(201, 168, 106, 0.2); position: relative; z-index: 1;">
                                <small style="color: #666; font-size: 0.85rem;">
                                    <i class="fas fa-lock me-1" style="color: #28a745;"></i>Secure shopping - Your data is protected
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
