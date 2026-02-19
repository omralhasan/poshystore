<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/checkout.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: ../../index.php');
    exit;
}

$order_id = filter_var($_GET['order_id'], FILTER_VALIDATE_INT);
$order_details = getOrderDetails($order_id, $_SESSION['user_id']);

if (!$order_details['success']) {
    header('Location: ../../index.php');
    exit;
}

$order = $order_details['order'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounceIn 0.6s ease-out, sparkle 2s infinite;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .success-card {
            background: white;
            border: 3px solid var(--gold-color);
            border-radius: 16px;
            padding: 3rem;
            max-width: 650px;
            margin: 0 auto;
            text-align: center;
            box-shadow: 0 10px 40px rgba(201, 168, 106, 0.3);
        }
        
        .order-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(201, 168, 106, 0.2);
        }
        
        .order-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.3rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
        <div class="container py-5">
            <div class="success-card">
                <div class="success-icon">✨🎉✅</div>
                <h2 class="mb-3" style="color: var(--purple-color); font-family: 'Playfair Display', serif;">
                    Order Confirmed!
                </h2>
                <p style="color: #666; margin-bottom: 2rem;">
                    Thank you for your purchase. Your order has been successfully placed.
                </p>
                
                <div class="card-ramadan p-4 text-start mb-4" style="background: linear-gradient(135deg, rgba(252, 248, 242, 0.5), rgba(228, 212, 180, 0.3));">
                    <div class="order-row">
                        <span style="color: var(--gold-color); font-weight: 600;">
                            <i class="fas fa-receipt me-2"></i>Order ID:
                        </span>
                        <span style="color: var(--purple-color); font-weight: 700;">#<?= $order['order_id'] ?></span>
                    </div>
                    <div class="order-row">
                        <span style="color: var(--gold-color); font-weight: 600;">
                            <i class="fas fa-calendar me-2"></i>Date:
                        </span>
                        <span style="color: var(--purple-color);"><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="order-row">
                        <span style="color: var(--gold-color); font-weight: 600;">
                            <i class="fas fa-info-circle me-2"></i>Status:
                        </span>
                        <span style="color: #ffc107; text-transform: capitalize; font-weight: 600;"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <div class="order-row">
                        <span style="color: var(--gold-color); font-weight: 600;">
                            <i class="fas fa-money-bill-wave me-2"></i>Total Amount:
                        </span>
                        <span style="color: var(--purple-color); font-size: 1.4rem; font-weight: 700;"><?= $order['total_amount_formatted'] ?></span>
                    </div>
                </div>
                
                <?php if (isset($order['is_gift']) && $order['is_gift'] == 1): ?>
                    <div class="card-ramadan p-4 text-start mb-4" style="background: linear-gradient(135deg, #fff5f8 0%, #fffaf0 100%); border: 3px solid var(--gold-color); box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2); border-radius: 16px; position: relative; overflow: hidden;">
                        <!-- Decorative background elements -->
                        <div style="position: absolute; top: -20px; right: -20px; font-size: 5rem; color: rgba(212, 175, 55, 0.08); transform: rotate(20deg);">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div style="position: absolute; bottom: -25px; left: -25px; font-size: 4rem; color: rgba(72, 54, 110, 0.06); transform: rotate(-15deg);">
                            <i class="fas fa-heart"></i>
                        </div>
                        
                        <div style="text-align: center; margin-bottom: 2rem; position: relative; z-index: 1;">
                            <div style="display: inline-block; background: linear-gradient(135deg, var(--gold-color), #f4d78c); padding: 1.5rem; border-radius: 50%; margin-bottom: 1rem; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);">
                                <i class="fas fa-gift" style="font-size: 3rem; color: white;"></i>
                            </div>
                            <h4 style="color: var(--purple-color); margin: 0; font-weight: 800; font-size: 1.8rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
                                <i class="fas fa-sparkles" style="color: var(--gold-color); font-size: 1rem;"></i> Special Gift Order <i class="fas fa-sparkles" style="color: var(--gold-color); font-size: 1rem;"></i>
                            </h4>
                            <p style="color: #666; margin: 0.5rem 0 0 0; font-weight: 500;">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> Premium gift wrapping with elegant presentation
                            </p>
                        </div>
                        
                        <?php if (!empty($order['gift_recipient_name'])): ?>
                            <div style="background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%); margin-bottom: 1.5rem; padding: 1.5rem; border-radius: 12px; border: 2px solid rgba(212, 175, 55, 0.4); box-shadow: 0 6px 16px rgba(0,0,0,0.08); position: relative; z-index: 1;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(72, 54, 110, 0.4);">
                                        <i class="fas fa-user" style="color: var(--gold-color); font-size: 1.6rem;"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="color: var(--gold-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 0.4rem;">
                                            <i class="fas fa-tag" style="font-size: 0.8rem;"></i> This Gift Is For
                                        </div>
                                        <div style="color: var(--purple-color); font-weight: 900; font-size: 1.8rem; line-height: 1.2;">
                                            <?= htmlspecialchars($order['gift_recipient_name']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['gift_message'])): ?>
                            <div style="background: linear-gradient(135deg, #ffffff 0%, #fffef9 100%); padding: 2rem; border-radius: 12px; border-left: 6px solid var(--gold-color); box-shadow: 0 6px 16px rgba(0,0,0,0.08); position: relative; z-index: 1;">
                                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                    <i class="fas fa-quote-left" style="color: var(--gold-color); font-size: 2rem; margin-right: 1rem; opacity: 0.5;"></i>
                                    <strong style="color: var(--gold-color); font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px;">
                                        Your Heartfelt Message
                                    </strong>
                                </div>
                                <div style="padding-left: 3rem; position: relative;">
                                    <p style="color: #555; margin: 0; font-style: italic; line-height: 2; font-size: 1.15rem; font-family: 'Georgia', serif;">
                                        <?= nl2br(htmlspecialchars($order['gift_message'])) ?>
                                    </p>
                                    <i class="fas fa-quote-right" style="color: var(--gold-color); font-size: 2rem; position: absolute; bottom: -15px; right: 20px; opacity: 0.5;"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(212, 175, 55, 0.15); border-radius: 10px; text-align: center; position: relative; z-index: 1;">
                            <i class="fas fa-heart" style="color: #e74c3c; margin-right: 0.5rem; animation: heartbeat 1.5s infinite;"></i>
                            <span style="color: var(--purple-color); font-weight: 700; font-size: 1rem;">Wrapped with love and care ✨</span>
                        </div>
                        
                        <style>
                            @keyframes heartbeat {
                                0%, 100% { transform: scale(1); }
                                25% { transform: scale(1.1); }
                                50% { transform: scale(1); }
                            }
                        </style>
                    </div>
                <?php endif; ?>
                
                <div class="alert-ramadan alert-success mb-4">
                    <i class="fas fa-phone me-2"></i>
                    A confirmation will be sent to <strong><?= htmlspecialchars($order['phone']) ?></strong>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="my_orders.php" class="btn-ramadan w-100">
                            <i class="fas fa-box me-2"></i>View My Orders
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="../../index.php" class="btn-ramadan-secondary w-100" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
