<?php
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/checkout.php';
require_once __DIR__ . '/../../includes/points_wallet_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

// Get user's phone number and wallet balance from database
$user_phone = '';
$wallet_balance = 0;
$stmt = $conn->prepare("SELECT phonenumber, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_phone = $row['phonenumber'] ?? '';
    $wallet_balance = (float)($row['wallet_balance'] ?? 0);
}
$stmt->close();
$wallet_balance_formatted = formatJOD($wallet_balance);

// Get cart
$cart = viewCart();
if (empty($cart['cart_items'])) {
    header('Location: cart.php');
    exit;
}

// Get applied coupon from session
$applied_coupon = $_SESSION['applied_coupon'] ?? null;
$coupon_discount = $applied_coupon['discount'] ?? 0;
$cart_total = $cart['total_amount'];
$cart_total_after_coupon = max(0, $cart_total - $coupon_discount);

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    // Validate phone number
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    if (empty($phone)) {
        $error = 'Phone number is required for order delivery.';
    } elseif (empty($city)) {
        $error = 'City is required for order delivery.';
    } else {
        $additional_data = [
            'shipping_address' => $_POST['shipping_address'] ?? '',
            'city' => $city,
            'phone' => $phone,
            'notes' => $_POST['notes'] ?? '',
            'referral_code' => trim($_POST['referral_code'] ?? ''),
            'use_wallet' => isset($_POST['use_wallet']) && $_POST['use_wallet'] == '1',
            'is_gift' => (isset($_POST['is_gift']) && $_POST['is_gift'] == '1') ? 1 : 0,
            'gift_recipient_name' => $_POST['gift_recipient_name'] ?? '',
            'gift_message' => $_POST['gift_message'] ?? ''
        ];
        
        $result = processCheckout($_SESSION['user_id'], $additional_data);
        
        if ($result['success']) {
            header('Location: order_success.php?order_id=' . $result['order']['order_id']);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('checkout') ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%);
            min-height: 100vh;
        }
        
        header {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        @media (max-width: 968px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
        }
        
        .checkout-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .order-summary {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .order-summary h3 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            color: #333;
        }
        
        .item-qty {
            color: #666;
            font-size: 0.9rem;
        }
        
        .item-price {
            color: #28a745;
            font-weight: bold;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #333;
        }
        
        .confirm-btn {
            width: 100%;
            padding: 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1.5rem;
        }
        
        .confirm-btn:hover {
            background: #218838;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #dc3545;
        }
        
        /* Responsive Improvements */
        @media (max-width: 768px) {
            .section-title-ramadan {
                font-size: 2rem !important;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start !important;
                text-align: left;
            }
            
            .order-item img {
                width: 100%;
                height: auto;
                aspect-ratio: 1;
            }
        }
        
        /* Smooth Animations */
        .card-ramadan {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-ramadan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container py-5">
        <!-- Checkout Progress Steps -->
        <div style="max-width: 800px; margin: 0 auto 3rem auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
                <!-- Progress Line -->
                <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, rgba(201, 168, 106, 0.3), rgba(201, 168, 106, 0.3)); z-index: 0; transform: translateY(-50%);"></div>
                <div style="position: absolute; top: 50%; left: 0; width: 50%; height: 3px; background: linear-gradient(90deg, var(--purple-color), var(--gold-color)); z-index: 0; transform: translateY(-50%);"></div>
                
                <!-- Step 1: Cart -->
                <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--purple-color), var(--gold-color)); margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(72, 54, 110, 0.3);">
                        <i class="fas fa-check" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <small style="color: var(--purple-color); font-weight: 600; display: block; margin-top: 0.5rem;"><?= t('cart') ?></small>
                </div>
                
                <!-- Step 2: Checkout (Active) -->
                <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--purple-color), var(--gold-color)); margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(72, 54, 110, 0.3); animation: pulse 2s infinite;">
                        <i class="fas fa-credit-card" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <small style="color: var(--gold-color); font-weight: 700; display: block; margin-top: 0.5rem;"><?= t('checkout') ?></small>
                </div>
                
                <!-- Step 3: Complete -->
                <div style="position: relative; z-index: 1; text-align: center; flex: 1;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(201, 168, 106, 0.2); margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(201, 168, 106, 0.3);">
                        <i class="fas fa-check-circle" style="color: rgba(201, 168, 106, 0.5); font-size: 1.2rem;"></i>
                    </div>
                    <small style="color: #999; font-weight: 600; display: block; margin-top: 0.5rem;"><?= $current_lang === 'ar' ? 'تم' : 'Complete' ?></small>
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
                <i class="fas fa-credit-card me-3" style="color: var(--gold-color);"></i><?= t('checkout') ?>
            </h2>
            <p style="color: var(--gold-color); font-size: 1.1rem; margin-bottom: 1rem;"><?= t('complete_your_order') ?></p>
            <div style="width: 100px; height: 3px; background: linear-gradient(90deg, var(--purple-color), var(--gold-color), var(--purple-color)); margin: 0 auto; border-radius: 2px;"></div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert-ramadan alert-danger mb-4">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card-ramadan p-4 mb-4" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid rgba(201, 168, 106, 0.2);">
                        <h4 class="mb-4" style="color: var(--purple-color); font-weight: 700; border-bottom: 2px solid var(--gold-color); padding-bottom: 0.75rem;">
                            <i class="fas fa-user me-2" style="color: var(--gold-color);"></i><?= t('customer_information') ?>
                        </h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 0.9rem;">
                                    <i class="fas fa-user-circle me-1"></i><?= t('full_name') ?>
                                </label>
                                <input type="text" class="form-control-ramadan" value="<?= htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) ?>" readonly style="background: #f8f9fa;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 0.9rem;">
                                    <i class="fas fa-envelope me-1"></i><?= t('email_address') ?>
                                </label>
                                <input type="email" class="form-control-ramadan" value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly style="background: #f8f9fa;">
                            </div>
                        </div>
                    </div>

                    <div class="card-ramadan p-4" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid rgba(201, 168, 106, 0.2); margin-top: 3rem;">
                        <!-- Tab Navigation -->
                        <div class="mb-5">
                            <ul class="nav nav-pills justify-content-center" role="tablist" style="background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%); border-radius: 12px; padding: 0.5rem;">
                                <li class="nav-item" role="presentation" style="flex: 1;">
                                    <button 
                                        class="nav-link active" 
                                        id="shipping-tab" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#shipping-content" 
                                        type="button" 
                                        role="tab"
                                        style="width: 100%; border-radius: 10px; font-weight: 600; transition: all 0.3s; background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)); color: white; border: none;"
                                    >
                                        <i class="fas fa-shipping-fast me-2"></i><?= t('shipping_details_tab') ?>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation" style="flex: 1; margin-left: 0.5rem;">
                                    <button 
                                        class="nav-link" 
                                        id="gift-tab" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#gift-content" 
                                        type="button" 
                                        role="tab"
                                        style="width: 100%; border-radius: 10px; font-weight: 600; transition: all 0.3s; background: transparent; color: var(--purple-color); border: 2px solid var(--gold-color);"
                                    >
                                        <i class="fas fa-gift me-2"></i><?= t('gift_order_tab') ?>
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content" style="margin-top: 2rem;">
                            <!-- Shipping Details Tab -->
                            <div class="tab-pane fade show active" id="shipping-content" role="tabpanel" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 2px solid rgba(201, 168, 106, 0.15);">
                                <div style="background: linear-gradient(135deg, #e8f4f8 0%, #f0f8ff 100%); border-left: 5px solid var(--purple-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(72, 54, 110, 0.1);">
                                    <strong style="color: var(--purple-color); font-size: 1.15rem;"><i class="fas fa-truck me-2"></i><?= t('standard_delivery') ?></strong>
                                    <p style="color: #555; margin: 0.5rem 0 0 0; font-size: 0.95rem;"><?= t('provide_shipping_details') ?></p>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-phone me-2" style="color: var(--purple-color);"></i><?= t('phone_number') ?> <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input 
                                        type="tel" 
                                        name="phone" 
                                        id="phone" 
                                        class="form-control-ramadan" 
                                        required 
                                        placeholder="+962 7XXXXXXXX"
                                        value="<?= htmlspecialchars($user_phone) ?>"
                                        pattern="[+]?[0-9]{10,15}"
                                        title="Please enter a valid phone number (10-15 digits)"
                                        style="font-size: 1rem; padding: 0.875rem; border-width: 2px;"
                                    >
                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: var(--gold-color);"></i> <?= t('call_confirm_delivery') ?>
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-city me-2" style="color: var(--purple-color);"></i><?= t('city') ?> <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        name="city" 
                                        id="city"
                                        class="form-control-ramadan" 
                                        required 
                                        placeholder="<?= t('enter_your_city') ?>"
                                        pattern="[A-Za-z\s]{2,50}"
                                        title="Please enter a valid city name"
                                        style="font-size: 1rem; padding: 0.875rem; border-width: 2px;"
                                    >
                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: var(--gold-color);"></i> <?= t('required_delivery_routing') ?>
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-map-marked-alt me-2" style="color: var(--purple-color);"></i><?= t('shipping_address') ?> <span style="color: #dc3545;">*</span>
                                    </label>
                                    <textarea 
                                        name="shipping_address" 
                                        id="shipping_address"
                                        class="form-control-ramadan" 
                                        required 
                                        placeholder="<?= t('complete_delivery_address') ?>"
                                        rows="4"
                                        style="font-size: 1rem; font-weight: 500; line-height: 1.6; padding: 1rem; border-width: 2px; resize: vertical;"
                                    ></textarea>
                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: var(--gold-color);"></i> <?= t('full_address_landmarks') ?>
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: var(--gold-color); font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-comment-dots me-2" style="color: var(--purple-color);"></i><?= t('order_notes') ?> <span style="color: #888; font-weight: 400; font-size: 0.9rem;">(<?= t('optional') ?>)</span>
                                    </label>
                                    <textarea 
                                        name="notes" 
                                        id="notes"
                                        class="form-control-ramadan" 
                                        placeholder="<?= t('special_delivery_instructions') ?>"
                                        rows="3"
                                        style="font-size: 1rem; line-height: 1.6; padding: 1rem; border-width: 2px; resize: vertical;"
                                    ></textarea>
                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-lightbulb me-1" style="color: var(--gold-color);"></i> <?= t('example_delivery_time') ?>
                                    </small>
                                </div>
                                
                                <!-- Referral Code Field -->
                                <div class="mb-4" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 1.5rem; border-radius: 12px; border: 2px solid #38bdf8;">
                                    <label class="form-label" style="color: var(--purple-color); font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-gift me-2" style="color: var(--gold-color);"></i><?= t('have_referral_code') ?> <span style="color: #888; font-weight: 400; font-size: 0.9rem;">(<?= t('optional_earn_rewards') ?>)</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        name="referral_code" 
                                        id="referral_code"
                                        class="form-control-ramadan" 
                                        placeholder="<?= t('enter_friend_referral') ?>"
                                        maxlength="10"
                                        style="font-size: 1.1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-family: 'Courier New', monospace; border-width: 2px; padding: 0.9rem;"
                                    >
                                    <small class="text-muted" style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-star me-1" style="color: var(--gold-color);"></i> <strong>Your friend gets 200 points</strong> when you use their code!
                                    </small>
                                </div>
                            </div>
                            <!-- End Shipping Details Tab -->
                            
                            <!-- Gift Order Tab -->
                            <div class="tab-pane fade" id="gift-content" role="tabpanel" style="background: linear-gradient(135deg, #fff5f8 0%, #fffbf5 100%); padding: 2.5rem; border-radius: 16px; box-shadow: inset 0 0 40px rgba(236, 72, 153, 0.1), 0 4px 20px rgba(236, 72, 153, 0.15); position: relative; overflow: hidden; border: 2px solid rgba(236, 72, 153, 0.2);">
                                <!-- Decorative floating elements -->
                                <div style="position: absolute; top: 10px; right: 20px; color: rgba(236, 72, 153, 0.12); font-size: 3rem; animation: float 3s ease-in-out infinite;">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div style="position: absolute; bottom: 20px; left: 30px; color: rgba(212, 175, 55, 0.1); font-size: 2.5rem; animation: float 4s ease-in-out infinite;">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <div style="position: absolute; top: 50%; right: 10px; color: rgba(236, 72, 153, 0.08); font-size: 1.5rem; animation: float 2.5s ease-in-out infinite;">
                                    <i class="fas fa-heart"></i>
                                </div>
                                
                                <style>
                                    @keyframes float {
                                        0%, 100% { transform: translateY(0) rotate(0deg); }
                                        50% { transform: translateY(-15px) rotate(5deg); }
                                    }
                                    .gift-input-special {
                                        border: 2px solid #ffc0e0 !important;
                                        background: linear-gradient(135deg, #ffffff 0%, #fffbff 100%) !important;
                                        box-shadow: 0 2px 8px rgba(236, 72, 153, 0.1) !important;
                                        transition: all 0.3s ease !important;
                                    }
                                    .gift-input-special:focus {
                                        border-color: #ec4899 !important;
                                        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.15), 0 4px 12px rgba(236, 72, 153, 0.2) !important;
                                        transform: translateY(-2px);
                                    }
                                </style>
                                
                                <div class="alert" style="background: linear-gradient(135deg, #ffe6f7 0%, #fff0fb 100%); border: 2px dashed #ec4899; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.15); position: relative; z-index: 1;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="background: linear-gradient(135deg, #ec4899, #db2777); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(236, 72, 153, 0.3);">
                                            <i class="fas fa-heart" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div>
                                            <strong style="color: #be185d; font-size: 1.1rem;">💝 <?= t('sending_gift') ?></strong>
                                            <p style="color: #831843; margin: 0.25rem 0 0 0; font-size: 0.95rem;"><?= t('gift_unforgettable') ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: linear-gradient(135deg, #fff 0%, #fffbfe 100%); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 2px solid #ffc0e0; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.1); position: relative; z-index: 1;">
                                    <h5 style="color: #be185d; margin-bottom: 1.5rem; font-weight: 700; font-size: 1.2rem; text-align: center;">
                                        <i class="fas fa-gift me-2" style="color: #ec4899;"></i><?= t('gift_details') ?>
                                    </h5>
                                
                                    <div class="mb-4">
                                        <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                            <i class="fas fa-user-heart me-2" style="color: #ec4899;"></i><?= t('recipient_name') ?> <span style="color: #dc3545;">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            name="gift_recipient_name" 
                                            id="giftRecipientName"
                                            class="form-control-ramadan gift-input-special" 
                                            placeholder="<?= $current_lang === 'ar' ? 'أدخل اسم المستلم...' : "Enter recipient's name..." ?>"
                                            style="font-size: 1rem; padding: 0.875rem; font-weight: 500; border-width: 2px;"
                                        >
                                        <small style="color: #be185d; display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                            <i class="fas fa-info-circle me-1" style="color: #ec4899;"></i> <?= t('special_person_receive') ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                            <i class="fas fa-envelope-open-heart me-2" style="color: #ec4899;"></i><?= t('gift_message') ?> <span style="color: #dc3545;">*</span>
                                        </label>
                                        <textarea 
                                            name="gift_message" 
                                            id="giftMessage"
                                            class="form-control-ramadan gift-input-special" 
                                            placeholder="<?= t('write_heartfelt_message') ?>"
                                            rows="4"
                                            maxlength="500"
                                            style="font-size: 1rem; line-height: 1.6; padding: 1rem; border-width: 2px; resize: vertical;"
                                        ></textarea>
                                        <div class="d-flex justify-content-between align-items-center" style="margin-top: 0.5rem;">
                                            <small style="color: #be185d; font-size: 0.875rem;">
                                                <i class="fas fa-info-circle me-1" style="color: #ec4899;"></i> <?= t('present_message_gift') ?>
                                            </small>
                                            <small id="charCount" style="color: #ec4899; font-weight: 700; font-size: 0.875rem;">0/500</small>
                                        </div>
                                    </div>
                                </div>

                                <div style="border-top: 2px dashed #ffc0e0; margin: 2rem 0;"></div>
                                
                                <div style="background: linear-gradient(135deg, #fff 0%, #fffbfe 100%); border-radius: 12px; padding: 1.5rem; border: 2px solid #ffc0e0; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.1); position: relative; z-index: 1;">
                                    <h5 style="color: #be185d; margin-bottom: 1.5rem; font-weight: 700; font-size: 1.2rem; text-align: center;">
                                        <i class="fas fa-map-marker-alt me-2" style="color: #ec4899;"></i><?= t('delivery_address') ?>
                                    </h5>

                                <div class="mb-4">
                                    <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-phone me-2" style="color: #ec4899;"></i>Phone Number <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone_gift" 
                                        class="form-control-ramadan" 
                                        placeholder="+962 7XXXXXXXX"
                                        value="<?= htmlspecialchars($user_phone) ?>"
                                        pattern="[+]?[0-9]{10,15}"
                                        title="Please enter a valid phone number (10-15 digits)"
                                        style="font-size: 1rem; padding: 0.875rem; border-width: 2px;"
                                    >
                                    <small style="color: #be185d; display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: #ec4899;"></i> <?= t('contact_delivery') ?>
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-city me-2" style="color: #ec4899;"></i>City <span style="color: #dc3545;">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="city_gift"
                                        class="form-control-ramadan" 
                                        placeholder="<?= t('enter_delivery_city') ?>"
                                        pattern="[A-Za-z\s]{2,50}"
                                        title="Please enter a valid city name"
                                        style="font-size: 1rem; padding: 0.875rem; border-width: 2px;"
                                    >
                                    <small style="color: #be185d; display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: #ec4899;"></i> Required for delivery routing
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-map-marked-alt me-2" style="color: #ec4899;"></i><?= t('shipping_address') ?> <span style="color: #dc3545;">*</span>
                                    </label>
                                    <textarea 
                                        id="shipping_address_gift"
                                        class="form-control-ramadan" 
                                        placeholder="Enter the complete delivery address (street, building, floor)..."
                                        rows="4"
                                        style="font-size: 1rem; font-weight: 500; line-height: 1.6; padding: 1rem; border-width: 2px; resize: vertical;"
                                    ></textarea>
                                    <small style="color: #be185d; display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-info-circle me-1" style="color: #ec4899;"></i> Please provide full address including street, building number, floor, and landmarks
                                    </small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="color: #be185d; font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; display: block;">
                                        <i class="fas fa-comment-dots me-2" style="color: #ec4899;"></i><?= t('delivery_notes') ?> <span style="color: #be185d; font-weight: 400; font-size: 0.9rem;">(<?= t('optional') ?>)</span>
                                    </label>
                                    <textarea 
                                        id="notes_gift"
                                        class="form-control-ramadan" 
                                        placeholder="<?= t('special_delivery_preferred_time') ?>"
                                        rows="3"
                                        style="font-size: 1rem; line-height: 1.6; padding: 1rem; border-width: 2px; resize: vertical;"
                                    ></textarea>
                                    <small style="color: #be185d; display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-lightbulb me-1" style="color: #ec4899;"></i> <?= t('example_deliver_after_6pm') ?>
                                    </small>
                                </div>
                                </div>
                                </div>
                            </div>
                            <!-- End Gift Order Tab -->
                        </div>
                        <!-- End Tab Content -->
                        
                        <!-- Hidden field to track if this is a gift order -->
                        <input type="hidden" name="is_gift" id="isGiftField" value="0">
                    </div>

                    <!-- Order Summary Section -->
                    <div style="margin: 3rem 0;"></div>
                    <div class="card-ramadan p-4" style="max-width: 700px; margin: 0 auto; box-shadow: 0 6px 25px rgba(0,0,0,0.15); border: 3px solid rgba(201, 168, 106, 0.3);">
                        <h3 class="mb-4" style="color: var(--purple-color); font-weight: 800; text-align: center; border-bottom: 3px solid var(--gold-color); padding-bottom: 1.5rem; font-size: 2rem;">
                            <i class="fas fa-receipt me-3" style="color: var(--gold-color); font-size: 2rem;"></i><?= t('order_summary') ?>
                        </h3>
                        
                        <div class="mb-3" style="background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%); padding: 0.75rem; border-radius: 8px; text-align: center;">
                            <small style="color: var(--purple-color); font-weight: 600;">
                                <i class="fas fa-shopping-bag me-1" style="color: var(--gold-color);"></i>
                                <?= count($cart['cart_items']) ?> <?= count($cart['cart_items']) != 1 ? t('items_in_cart_plural') : t('items_in_cart_singular') ?>
                            </small>
                        </div>
                        
                        <div class="order-items-list" style="margin-bottom: 1rem;">
                            <?php foreach ($cart['cart_items'] as $item): ?>
                                <div class="order-item" style="display: flex; gap: 1rem; margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 2px solid rgba(201, 168, 106, 0.15); align-items: center;">
                                    <!-- Product Image -->
                                    <div style="flex-shrink: 0; width: 70px; height: 70px; border-radius: 10px; overflow: hidden; border: 2px solid var(--gold-color); box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                 alt="<?= htmlspecialchars($item['name_en']) ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--purple-color), var(--gold-color)); display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: white; font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Product Details -->
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; color: var(--purple-color); font-size: 0.95rem; margin-bottom: 0.25rem; line-height: 1.3;">
                                            <?= htmlspecialchars($item['name_en']) ?>
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                            <span style="color: #666; font-size: 0.85rem; background: linear-gradient(135deg, #f0f0f0, #e8e8e8); padding: 0.25rem 0.5rem; border-radius: 5px;">
                                                <i class="fas fa-times" style="font-size: 0.7rem;"></i> <?= $item['quantity'] ?>
                                            </span>
                                            <span style="color: var(--gold-color); font-weight: 700; font-size: 1rem; white-space: nowrap;">
                                                <?= $item['subtotal_formatted'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Subtotal Section -->
                        <div style="background: linear-gradient(135deg, #f8f5ff 0%, #fff9f5 100%); padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                            <div class="d-flex justify-content-between mb-2" style="font-size: 0.95rem;">
                                <span style="color: #666;"><i class="fas fa-calculator me-1"></i><?= t('subtotal_label') ?>:</span>
                                <span style="color: var(--purple-color); font-weight: 600;"><?= $cart['total_amount_formatted'] ?></span>
                            </div>
                            <?php if ($applied_coupon): ?>
                            <div class="d-flex justify-content-between mb-2" style="font-size: 0.95rem;">
                                <span style="color: #28a745;"><i class="fas fa-tag me-1"></i><?= t('coupon_label') ?> (<?= htmlspecialchars($applied_coupon['code']) ?>):</span>
                                <span style="color: #28a745; font-weight: 700;">-<?= formatJOD($coupon_discount) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2" style="font-size: 0.95rem;">
                                <span style="color: #666;"><i class="fas fa-truck me-1"></i><?= t('shipping_label') ?>:</span>
                                <span style="color: #28a745; font-weight: 600;"><?= t('free') ?></span>
                            </div>
                        </div>
                        
                        <?php if ($wallet_balance > 0): ?>
                        <!-- Wallet Balance Section -->
                        <div style="background: linear-gradient(135deg, #e6f7ff 0%, #f0fbff 100%); padding: 1.25rem; border-radius: 12px; margin-bottom: 1rem; border: 2px solid #1890ff;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div style="color: var(--purple-color); font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem;">
                                        <i class="fas fa-wallet me-2" style="color: #1890ff;"></i><?= t('your_wallet_balance') ?>
                                    </div>
                                    <div style="color: #1890ff; font-size: 1.3rem; font-weight: 900;">
                                        <?= $wallet_balance_formatted ?>
                                    </div>
                                </div>
                                <i class="fas fa-coins" style="font-size: 2.5rem; color: rgba(24, 144, 255, 0.2);"></i>
                            </div>
                            <div class="form-check" style="background: white; padding: 0.75rem; border-radius: 8px;">
                                <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWalletCheckbox" 
                                       style="width: 20px; height: 20px; border: 2px solid #1890ff; cursor: pointer;"
                                       <?= $wallet_balance >= $cart_total_after_coupon ? 'checked' : '' ?>
                                       onchange="updateTotal()">
                                <label class="form-check-label" for="useWalletCheckbox" style="color: var(--purple-color); font-weight: 600; margin-left: 0.5rem; cursor: pointer;">
                                    <i class="fas fa-check-circle me-1" style="color: #28a745;"></i>
                                    <?= t('use_wallet_balance_order') ?>
                                </label>
                            </div>
                            <small style="color: #666; display: block; margin-top: 0.75rem; font-size: 0.85rem;">
                                <i class="fas fa-info-circle me-1" style="color: #1890ff;"></i>
                                <?php if ($wallet_balance >= $cart_total_after_coupon): ?>
                                    <?= t('wallet_covers_full_amount') ?>
                                <?php else: ?>
                                    <?= t('wallet_applied_pay_remaining') ?>: <strong><?= formatJOD($cart_total_after_coupon - $wallet_balance) ?></strong>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Total Section -->
                        <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(72, 54, 110, 0.3);">
                            <span style="color: white; font-size: 1.4rem; font-weight: 700;">
                                <i class="fas fa-coins me-2" style="color: var(--gold-color);"></i><?= t('total_to_pay') ?>:
                            </span>
                            <span id="finalTotal" style="color: var(--gold-color); font-size: 1.6rem; font-weight: 900; text-shadow: 0 2px 4px rgba(0,0,0,0.2);"><?= formatJOD($cart_total_after_coupon) ?></span>
                        </div>
                        
                        <?php if ($wallet_balance > 0): ?>
                        <div id="walletSavings" style="display: none; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; border: 2px solid #22c55e; text-align: center;">
                            <small style="color: #15803d; font-weight: 600;">
                                <i class="fas fa-check-circle me-1"></i><?= t('wallet_credit_applied') ?>: <strong id="walletApplied">0.000 JOD</strong>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <script>
                        const cartTotal = <?= $cart_total_after_coupon ?>;
                        const walletBalance = <?= $wallet_balance ?>;
                        
                        function updateTotal() {
                            const useWallet = document.getElementById('useWalletCheckbox')?.checked || false;
                            const finalTotalEl = document.getElementById('finalTotal');
                            const walletSavingsEl = document.getElementById('walletSavings');
                            const walletAppliedEl = document.getElementById('walletApplied');
                            
                            if (useWallet && walletBalance > 0) {
                                const walletToUse = Math.min(walletBalance, cartTotal);
                                const finalAmount = Math.max(0, cartTotal - walletToUse);
                                
                                finalTotalEl.textContent = finalAmount.toFixed(3) + ' JOD';
                                walletAppliedEl.textContent = walletToUse.toFixed(3) + ' JOD';
                                walletSavingsEl.style.display = 'block';
                            } else {
                                finalTotalEl.textContent = cartTotal.toFixed(3) + ' JOD';
                                walletSavingsEl.style.display = 'none';
                            }
                        }
                        
                        // Initialize on page load
                        document.addEventListener('DOMContentLoaded', updateTotal);
                        </script>
                        
                        <button type="submit" name="confirm_order" class="btn-ramadan w-100 mt-2" style="padding: 1rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 6px 20px rgba(72, 54, 110, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(72, 54, 110, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(72, 54, 110, 0.3)';">
                            <i class="fas fa-check-circle me-2"></i><?= t('confirm_order') ?>
                        </button>
                        
                        <a href="cart.php" class="btn-ramadan-secondary w-100 mt-3" style="display: block; text-align: center; text-decoration: none; padding: 0.75rem; font-weight: 600;">
                            <i class="fas fa-arrow-left me-2"></i><?= t('back_to_cart') ?>
                        </a>
                        
                        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(201, 168, 106, 0.2);">
                            <small style="color: #666; font-size: 0.85rem;">
                                <i class="fas fa-lock me-1" style="color: #28a745;"></i><?= t('secure_checkout_protected') ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </div>
    
    <script>
        // Track current mode
        let isGiftMode = false;
        
        // Set shipping mode based on tab selection
        function setShippingMode(isGift) {
            isGiftMode = isGift;
            document.getElementById('isGiftField').value = isGift ? '1' : '0';
            
            // Update tab styles with smooth transition
            const shippingTab = document.getElementById('shipping-tab');
            const giftTab = document.getElementById('gift-tab');
            
            if (isGift) {
                giftTab.style.background = 'linear-gradient(135deg, var(--purple-color), var(--purple-dark))';
                giftTab.style.color = 'white';
                giftTab.style.border = 'none';
                giftTab.style.transform = 'translateY(-2px)';
                giftTab.style.boxShadow = '0 4px 12px rgba(72, 54, 110, 0.3)';
                
                shippingTab.style.background = 'transparent';
                shippingTab.style.color = 'var(--purple-color)';
                shippingTab.style.border = '2px solid var(--gold-color)';
                shippingTab.style.transform = 'translateY(0)';
                shippingTab.style.boxShadow = 'none';
            } else {
                shippingTab.style.background = 'linear-gradient(135deg, var(--purple-color), var(--purple-dark))';
                shippingTab.style.color = 'white';
                shippingTab.style.border = 'none';
                shippingTab.style.transform = 'translateY(-2px)';
                shippingTab.style.boxShadow = '0 4px 12px rgba(72, 54, 110, 0.3)';
                
                giftTab.style.background = 'transparent';
                giftTab.style.color = 'var(--purple-color)';
                giftTab.style.border = '2px solid var(--gold-color)';
                giftTab.style.transform = 'translateY(0)';
                giftTab.style.boxShadow = 'none';
            }
        }
        
        // Phone number and city validation
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for Bootstrap tab events
            const shippingTabEl = document.getElementById('shipping-tab');
            const giftTabEl = document.getElementById('gift-tab');
            
            if (shippingTabEl) {
                shippingTabEl.addEventListener('shown.bs.tab', function (e) {
                    setShippingMode(false);
                });
            }
            
            if (giftTabEl) {
                giftTabEl.addEventListener('shown.bs.tab', function (e) {
                    setShippingMode(true);
                });
            }
            
            const form = document.querySelector('form');
            const giftMessageTextarea = document.getElementById('giftMessage');
            const charCountSpan = document.getElementById('charCount');
            
            // Character counter for gift message
            if (giftMessageTextarea && charCountSpan) {
                giftMessageTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    charCountSpan.textContent = length + '/500';
                    
                    if (length > 450) {
                        charCountSpan.style.color = '#dc3545';
                    } else if (length > 400) {
                        charCountSpan.style.color = '#ffc107';
                    } else {
                        charCountSpan.style.color = '#999';
                    }
                });
            }
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                let phoneInput, cityInput, addressInput, notesInput;
                
                if (isGiftMode) {
                    // Validate gift fields
                    const recipientInput = document.getElementById('giftRecipientName');
                    const giftMessageInput = document.getElementById('giftMessage');
                    
                    if (!recipientInput.value.trim()) {
                        e.preventDefault();
                        alert('<?= $current_lang === "ar" ? "⚠️ اسم المستلم مطلوب!\\n\\nيرجى إدخال اسم الشخص الذي سيستلم هذه الهدية." : "⚠️ Recipient name is required!\\n\\nPlease enter the name of the person receiving this gift." ?>');
                        recipientInput.focus();
                        recipientInput.style.borderColor = '#dc3545';
                        return false;
                    }
                    
                    if (!giftMessageInput.value.trim()) {
                        e.preventDefault();
                        alert('<?= $current_lang === "ar" ? "⚠️ رسالة الهدية مطلوبة!\\n\\nيرجى كتابة رسالة شخصية للمستلم." : "⚠️ Gift message is required!\\n\\nPlease write a personal message for the recipient." ?>');
                        giftMessageInput.focus();
                        giftMessageInput.style.borderColor = '#dc3545';
                        return false;
                    }
                    
                    // Use gift tab fields
                    phoneInput = document.getElementById('phone_gift');
                    cityInput = document.getElementById('city_gift');
                    addressInput = document.getElementById('shipping_address_gift');
                    notesInput = document.getElementById('notes_gift');
                    
                    // Copy gift values to main form fields for submission
                    document.getElementById('phone').value = phoneInput.value;
                    document.getElementById('city').value = cityInput.value;
                    document.getElementById('shipping_address').value = addressInput.value;
                    document.getElementById('notes').value = notesInput.value || '';
                } else {
                    // Use shipping tab fields
                    phoneInput = document.getElementById('phone');
                    cityInput = document.getElementById('city');
                    addressInput = document.getElementById('shipping_address');
                    notesInput = document.getElementById('notes');
                    
                    // Clear gift fields
                    document.getElementById('giftRecipientName').value = '';
                    document.getElementById('giftMessage').value = '';
                }
                
                // Validate phone
                const phoneValue = phoneInput.value.trim();
                if (!phoneValue) {
                    e.preventDefault();
                    alert('<?= $current_lang === "ar" ? "⚠️ رقم الهاتف مطلوب!\\n\\nنحتاج رقم اتصالك لتنسيق توصيل طلبك." : "⚠️ Phone number is required!\\n\\nWe need your contact number to coordinate delivery of your order." ?>');
                    phoneInput.focus();
                    phoneInput.style.borderColor = '#dc3545';
                    return false;
                }
                
                const cleanPhone = phoneValue.replace(/\s/g, '');
                if (!/^[+]?[0-9]{10,15}$/.test(cleanPhone)) {
                    e.preventDefault();
                    alert('<?= $current_lang === "ar" ? "⚠️ تنسيق رقم الهاتف غير صحيح!\\n\\nيرجى إدخال رقم هاتف صالح من 10-15 رقم.\\nمثال: +962 7XXXXXXXX" : "⚠️ Invalid phone number format!\\n\\nPlease enter a valid phone number with 10-15 digits.\\nExample: +962 7XXXXXXXX" ?>');
                    phoneInput.focus();
                    phoneInput.style.borderColor = '#dc3545';
                    return false;
                }
                
                // Validate city
                const cityValue = cityInput.value.trim();
                if (!cityValue) {
                    e.preventDefault();
                    alert('<?= $current_lang === "ar" ? "⚠️ المدينة مطلوبة!\\n\\nنحتاج معلومات مدينتك لتوجيه التوصيل الصحيح." : "⚠️ City is required!\\n\\nWe need your city information for proper delivery routing." ?>');
                    cityInput.focus();
                    cityInput.style.borderColor = '#dc3545';
                    return false;
                }
                
                if (!/^[A-Za-z\s]{2,50}$/.test(cityValue)) {
                    e.preventDefault();
                    alert('<?= $current_lang === "ar" ? "⚠️ اسم المدينة غير صالح!\\n\\nيرجى إدخال اسم مدينة صالح (حروف فقط، 2-50 حرف).\\nمثال: عمان، إربد" : "⚠️ Invalid city name!\\n\\nPlease enter a valid city name (letters only, 2-50 characters).\\nExample: Amman, Irbid" ?>');
                    cityInput.focus();
                    cityInput.style.borderColor = '#dc3545';
                    return false;
                }
                
                // Validate address
                const addressValue = addressInput.value.trim();
                if (!addressValue) {
                    e.preventDefault();
                    alert('<?= $current_lang === "ar" ? "⚠️ عنوان الشحن مطلوب!\\n\\nيرجى تقديم عنوان التوصيل الكامل." : "⚠️ Shipping address is required!\\n\\nPlease provide the complete delivery address." ?>');
                    addressInput.focus();
                    addressInput.style.borderColor = '#dc3545';
                    return false;
                }
            });
            
            // Add real-time validation for all phone fields
            const phoneFields = ['phone', 'phone_gift'];
            phoneFields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        const value = this.value.replace(/\s/g, '');
                        if (value.length > 0 && !/^[+]?[0-9]{10,15}$/.test(value)) {
                            this.style.borderColor = '#dc3545';
                        } else if (value.length > 0) {
                            this.style.borderColor = '#28a745';
                        } else {
                            this.style.borderColor = '';
                        }
                    });
                }
            });
            
            // Add real-time validation for all city fields
            const cityFields = ['city', 'city_gift'];
            cityFields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        const value = this.value.trim();
                        if (value.length > 0 && !/^[A-Za-z\s]{2,50}$/.test(value)) {
                            this.style.borderColor = '#dc3545';
                        } else if (value.length > 0) {
                            this.style.borderColor = '#28a745';
                        } else {
                            this.style.borderColor = '';
                        }
                    });
                }
            });
        });
    </script>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
