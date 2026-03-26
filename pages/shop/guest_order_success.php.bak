<?php
/**
 * Guest Order Success Page
 */
require_once __DIR__ . '/../../includes/language.php';

$order_id = intval($_GET['order_id'] ?? $_SESSION['guest_order_id'] ?? 0);
$guest_name = $_SESSION['guest_order_name'] ?? '';

// Clean up session
unset($_SESSION['guest_order_id'], $_SESSION['guest_order_name']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_lang === 'ar' ? 'تم تأكيد الطلب' : 'Order Confirmed' ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%); min-height: 100vh; }
        .success-container { max-width: 600px; margin: 3rem auto; padding: 0 1rem; text-align: center; }
        .success-card { background: #fff; border-radius: 20px; padding: 3rem 2rem; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .success-icon { font-size: 4rem; color: #10b981; margin-bottom: 1rem; }
        .success-title { color: var(--purple-color, #6b2fa0); font-size: 1.8rem; margin-bottom: 0.5rem; }
        .success-msg { color: #666; font-size: 1.1rem; margin-bottom: 1.5rem; line-height: 1.6; }
        .order-number { background: linear-gradient(135deg, #f0e6f6, #e8d5f5); padding: 1rem; border-radius: 12px; margin: 1.5rem 0; }
        .order-number strong { color: var(--purple-color, #6b2fa0); font-size: 1.3rem; }
        .btn-home { display: inline-block; padding: 0.8rem 2rem; background: linear-gradient(135deg, var(--purple-color, #6b2fa0), var(--purple-dark, #4a1f6e)); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 600; transition: transform 0.2s; }
        .btn-home:hover { transform: translateY(-2px); color: #fff; }
        .register-cta { margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, #fff7ed, #fef3c7); border-radius: 12px; border: 1px solid #fcd34d; }
        .register-cta a { color: var(--purple-color, #6b2fa0); font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>
    
    <div class="page-container">
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">
                <?= $current_lang === 'ar' ? 'تم تأكيد طلبك!' : 'Order Confirmed!' ?>
            </h1>
            <p class="success-msg">
                <?php if (!empty($guest_name)): ?>
                    <?= $current_lang === 'ar' ? "شكراً $guest_name! " : "Thank you $guest_name! " ?>
                <?php endif; ?>
                <?= $current_lang === 'ar' ? 'سنتواصل معك قريباً عبر الهاتف لتأكيد الطلب والتوصيل.' : 'We will contact you soon by phone to confirm your order and delivery.' ?>
            </p>
            
            <?php if ($order_id): ?>
            <div class="order-number">
                <div style="color: #666; font-size: 0.85rem; margin-bottom: 0.3rem;">
                    <?= $current_lang === 'ar' ? 'رقم الطلب' : 'Order Number' ?>
                </div>
                <strong>#<?= $order_id ?></strong>
            </div>
            <?php endif; ?>
            
            <a href="<?= BASE_PATH ?>/" class="btn-home">
                <i class="fas fa-shopping-bag me-2"></i>
                <?= $current_lang === 'ar' ? 'متابعة التسوق' : 'Continue Shopping' ?>
            </a>
            
            <div class="register-cta">
                <i class="fas fa-gift" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <p style="margin: 0.5rem 0 0; font-size: 0.95rem;">
                    <?= $current_lang === 'ar' ? 'أنشئ حساباً لتحصل على نقاط ومكافآت وتتبع طلباتك!' : 'Create an account to earn points, rewards, and track your orders!' ?>
                    <br>
                    <a href="../auth/signin.php"><?= $current_lang === 'ar' ? 'إنشاء حساب الآن' : 'Create Account Now' ?></a>
                </p>
            </div>
        </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>
</body>
</html>
