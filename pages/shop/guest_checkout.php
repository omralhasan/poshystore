<?php
/**
 * Guest Checkout Page
 * Allows users to place orders without creating an account
 */
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/guest_cart_handler.php';
require_once __DIR__ . '/../../includes/product_image_helper.php';

// If user is logged in, redirect to normal checkout
if (isset($_SESSION['user_id'])) {
    header('Location: /pages/shop/checkout_page.php');
    exit;
}

// Get guest cart
$cart = guestViewCart();
if (empty($cart['cart_items'])) {
    header('Location: ../../index.php');
    exit;
}

$cart_total = $cart['total_amount'];
$delivery_fee = ($cart_total >= 35) ? 0 : 2;
$cart_total_with_delivery = $cart_total + $delivery_fee;

// Process guest order
$error = '';
$order_success = false;
$order_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_guest_order'])) {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($guest_name)) {
        $error = $current_lang === 'ar' ? 'الرجاء إدخال الاسم الكامل' : 'Please enter your full name';
    } elseif (empty($phone)) {
        $error = $current_lang === 'ar' ? 'الرجاء إدخال رقم الهاتف' : 'Please enter your phone number';
    } elseif (empty($city)) {
        $error = $current_lang === 'ar' ? 'الرجاء اختيار المدينة' : 'Please select your city';
    } elseif (empty($shipping_address)) {
        $error = $current_lang === 'ar' ? 'الرجاء إدخال عنوان التوصيل' : 'Please enter your shipping address';
    } else {
        // Process guest order
        require_once __DIR__ . '/../../includes/db_connect.php';
        
        // Refresh cart to validate stock
        $cart = guestViewCart();
        if (empty($cart['cart_items'])) {
            $error = $current_lang === 'ar' ? 'السلة فارغة' : 'Cart is empty';
        } else {
            // Check stock
            $stock_errors = [];
            foreach ($cart['cart_items'] as $item) {
                if ($item['stock'] < $item['quantity']) {
                    $stock_errors[] = $item['name_en'] . ' - Available: ' . $item['stock'];
                }
            }
            
            if (!empty($stock_errors)) {
                $error = ($current_lang === 'ar' ? 'مخزون غير كافٍ: ' : 'Insufficient stock: ') . implode(', ', $stock_errors);
            } else {
                $conn->begin_transaction();
                try {
                    $total_amount = $cart['total_amount'];
                    $delivery_fee = ($total_amount >= 35) ? 0 : 2;
                    $total_amount += $delivery_fee;
                    
                    // Insert guest order
                    $sql = "INSERT INTO orders (user_id, guest_name, guest_email, total_amount, status, shipping_address, phone, city, notes, order_type, is_guest) 
                            VALUES (NULL, ?, ?, ?, 'pending', ?, ?, ?, ?, 'customer', 1)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssdssss', $guest_name, $guest_email, $total_amount, $shipping_address, $phone, $city, $notes);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to create order: ' . $stmt->error);
                    }
                    $order_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Insert order items
                    $item_sql = "INSERT INTO order_items (order_id, product_id, product_name_en, product_name_ar, quantity, price_per_item, subtotal) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $item_stmt = $conn->prepare($item_sql);
                    
                    foreach ($cart['cart_items'] as $item) {
                        // Decrease stock
                        $conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['product_id']} AND stock_quantity >= {$item['quantity']}");
                        if ($conn->affected_rows === 0) {
                            throw new Exception("Stock update failed for product {$item['product_id']}");
                        }
                        
                        $item_stmt->bind_param('iissids',
                            $order_id, $item['product_id'],
                            $item['name_en'], $item['name_ar'],
                            $item['quantity'], $item['price'], $item['subtotal']
                        );
                        if (!$item_stmt->execute()) {
                            throw new Exception('Failed to save order item');
                        }
                    }
                    $item_stmt->close();
                    
                    $conn->commit();
                    
                    // Clear guest cart
                    guestClearCart();
                    
                    // Store order ID for success page
                    $_SESSION['guest_order_id'] = $order_id;
                    $_SESSION['guest_order_name'] = $guest_name;
                    
                    header('Location: /pages/shop/guest_order_success.php?order_id=' . $order_id);
                    exit;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = ($current_lang === 'ar' ? 'فشل في إنشاء الطلب: ' : 'Failed to create order: ') . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_lang === 'ar' ? 'إتمام الطلب كزائر' : 'Guest Checkout' ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .checkout-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .checkout-card h2 { color: var(--purple-color, #6b2fa0); margin-bottom: 1.5rem; font-size: 1.3rem; }
        .checkout-card h2 i { color: var(--gold-color, #d4a853); margin-right: 0.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #333; font-size: 0.95rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.75rem 1rem; border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 1rem; transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--purple-color, #6b2fa0); outline: none;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
        .order-item { display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid #f0f0f0; align-items: center; }
        .order-item img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; background: #f5f5f5; }
        .order-item-info { flex: 1; }
        .order-item-name { font-weight: 600; font-size: 0.9rem; }
        .order-item-qty { color: #666; font-size: 0.85rem; }
        .order-item-price { font-weight: 700; color: var(--purple-color, #6b2fa0); }
        .order-summary-row { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.95rem; }
        .order-summary-total { font-size: 1.2rem; font-weight: 700; color: var(--purple-color, #6b2fa0); border-top: 2px solid #e0e0e0; padding-top: 0.75rem; margin-top: 0.5rem; }
        .btn-checkout {
            width: 100%; padding: 1rem; background: linear-gradient(135deg, var(--purple-color, #6b2fa0), var(--purple-dark, #4a1f6e));
            color: #fff; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(107,47,160,0.3); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid #fecaca; }
        .or-login { text-align: center; padding: 1rem; background: linear-gradient(135deg, #f0e6f6, #e8d5f5); border-radius: 10px; margin-bottom: 1.5rem; }
        .or-login a { color: var(--purple-color, #6b2fa0); font-weight: 700; text-decoration: none; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container">
        <h1 style="text-align: center; color: var(--purple-color, #6b2fa0); margin-bottom: 0.5rem; font-size: 1.8rem;">
            <i class="fas fa-shopping-bag"></i>
            <?= $current_lang === 'ar' ? 'إتمام الطلب' : 'Checkout' ?>
        </h1>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">
            <?= $current_lang === 'ar' ? 'أكمل طلبك بدون حساب' : 'Complete your order without an account' ?>
        </p>
        
        <div class="or-login">
            <i class="fas fa-user-circle" style="font-size: 1.5rem; color: var(--purple-color, #6b2fa0);"></i>
            <p style="margin: 0.5rem 0 0;">
                <?= $current_lang === 'ar' ? 'لديك حساب؟' : 'Have an account?' ?>
                <a href="/pages/auth/signin.php"><?= $current_lang === 'ar' ? 'سجل الدخول للحصول على نقاط ومكافآت' : 'Sign in for points & rewards' ?></a>
            </p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Customer Information -->
            <div class="checkout-card">
                <h2><i class="fas fa-user"></i> <?= $current_lang === 'ar' ? 'معلوماتك' : 'Your Information' ?></h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?= $current_lang === 'ar' ? 'الاسم الكامل' : 'Full Name' ?> <span class="required">*</span></label>
                        <input type="text" name="guest_name" required placeholder="<?= $current_lang === 'ar' ? 'أدخل اسمك الكامل' : 'Enter your full name' ?>" value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= $current_lang === 'ar' ? 'البريد الإلكتروني' : 'Email' ?> <small style="color:#999;">(<?= $current_lang === 'ar' ? 'اختياري' : 'optional' ?>)</small></label>
                        <input type="email" name="guest_email" placeholder="<?= $current_lang === 'ar' ? 'بريدك الإلكتروني' : 'your@email.com' ?>" value="<?= htmlspecialchars($_POST['guest_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?= $current_lang === 'ar' ? 'رقم الهاتف' : 'Phone Number' ?> <span class="required">*</span></label>
                        <input type="tel" name="phone" required placeholder="<?= $current_lang === 'ar' ? '07XXXXXXXX' : '07XXXXXXXX' ?>" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" pattern="[0-9]{10,}">
                    </div>
                    <div class="form-group">
                        <label><?= $current_lang === 'ar' ? 'المدينة' : 'City' ?> <span class="required">*</span></label>
                        <select name="city" required>
                            <option value=""><?= $current_lang === 'ar' ? '-- اختر المدينة --' : '-- Select City --' ?></option>
                            <option value="Amman" <?= ($_POST['city'] ?? '') === 'Amman' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'عمّان' : 'Amman' ?></option>
                            <option value="Irbid" <?= ($_POST['city'] ?? '') === 'Irbid' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'إربد' : 'Irbid' ?></option>
                            <option value="Zarqa" <?= ($_POST['city'] ?? '') === 'Zarqa' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'الزرقاء' : 'Zarqa' ?></option>
                            <option value="Aqaba" <?= ($_POST['city'] ?? '') === 'Aqaba' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'العقبة' : 'Aqaba' ?></option>
                            <option value="Salt" <?= ($_POST['city'] ?? '') === 'Salt' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'السلط' : 'Salt' ?></option>
                            <option value="Madaba" <?= ($_POST['city'] ?? '') === 'Madaba' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'مأدبا' : 'Madaba' ?></option>
                            <option value="Mafraq" <?= ($_POST['city'] ?? '') === 'Mafraq' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'المفرق' : 'Mafraq' ?></option>
                            <option value="Jerash" <?= ($_POST['city'] ?? '') === 'Jerash' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'جرش' : 'Jerash' ?></option>
                            <option value="Ajloun" <?= ($_POST['city'] ?? '') === 'Ajloun' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'عجلون' : 'Ajloun' ?></option>
                            <option value="Karak" <?= ($_POST['city'] ?? '') === 'Karak' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'الكرك' : 'Karak' ?></option>
                            <option value="Tafilah" <?= ($_POST['city'] ?? '') === 'Tafilah' ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'الطفيلة' : 'Tafilah' ?></option>
                            <option value="Ma'an" <?= ($_POST['city'] ?? '') === "Ma'an" ? 'selected' : '' ?>><?= $current_lang === 'ar' ? 'معان' : "Ma'an" ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'عنوان التوصيل' : 'Shipping Address' ?> <span class="required">*</span></label>
                    <textarea name="shipping_address" required rows="2" placeholder="<?= $current_lang === 'ar' ? 'الحي، الشارع، رقم البناية...' : 'Area, Street, Building number...' ?>"><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'ملاحظات' : 'Notes' ?> <small style="color:#999;">(<?= $current_lang === 'ar' ? 'اختياري' : 'optional' ?>)</small></label>
                    <textarea name="notes" rows="2" placeholder="<?= $current_lang === 'ar' ? 'أي ملاحظات إضافية...' : 'Any additional notes...' ?>"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="checkout-card">
                <h2><i class="fas fa-receipt"></i> <?= $current_lang === 'ar' ? 'ملخص الطلب' : 'Order Summary' ?></h2>
                
                <?php foreach ($cart['cart_items'] as $item): ?>
                <div class="order-item">
                    <?php
                    $images_dir = __DIR__ . '/../../images';
                    $item_images = get_product_gallery_images($item['name_en'], $item['image_link'] ?? '', $images_dir, BASE_PATH . '/');
                    $thumb = !empty($item_images) ? $item_images[0] : BASE_PATH . '/images/placeholder-cosmetics.svg';
                    ?>
                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($item['name_en']) ?>" onerror="this.src='<?= BASE_PATH ?>/images/placeholder-cosmetics.svg';">
                    <div class="order-item-info">
                        <div class="order-item-name"><?= htmlspecialchars($current_lang === 'ar' ? ($item['name_ar'] ?: $item['name_en']) : $item['name_en']) ?></div>
                        <div class="order-item-qty"><?= $current_lang === 'ar' ? 'الكمية' : 'Qty' ?>: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="order-item-price"><?= $item['subtotal_formatted'] ?></div>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 1rem;">
                    <div class="order-summary-row">
                        <span><?= $current_lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal' ?></span>
                        <span><?= $cart['total_formatted'] ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span><?= $current_lang === 'ar' ? 'التوصيل' : 'Delivery' ?></span>
                        <span><?= $delivery_fee > 0 ? formatJOD($delivery_fee) : ($current_lang === 'ar' ? 'مجاني ✨' : 'Free ✨') ?></span>
                    </div>
                    <?php if ($delivery_fee > 0): ?>
                    <div style="font-size: 0.8rem; color: var(--gold-color, #d4a853); margin-bottom: 0.5rem;">
                        <i class="fas fa-info-circle"></i> <?= $current_lang === 'ar' ? 'توصيل مجاني للطلبات فوق 35 دينار' : 'Free delivery on orders above 35 JOD' ?>
                    </div>
                    <?php endif; ?>
                    <div class="order-summary-row order-summary-total">
                        <span><?= $current_lang === 'ar' ? 'الإجمالي' : 'Total' ?></span>
                        <span><?= formatJOD($cart_total_with_delivery) ?></span>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="confirm_guest_order" class="btn-checkout">
                <i class="fas fa-lock me-2"></i>
                <?= $current_lang === 'ar' ? 'تأكيد الطلب' : 'Confirm Order' ?>
            </button>
            
            <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #888;">
                <i class="fas fa-shield-alt"></i>
                <?= $current_lang === 'ar' ? 'الدفع عند الاستلام • معلوماتك آمنة' : 'Cash on delivery • Your information is secure' ?>
            </p>
        </form>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>
</body>
</html>
