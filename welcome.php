<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/auth_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Poshy Store</title>
    <?php require_once __DIR__ . '/includes/ramadan_theme_header.php'; ?>
    <style>
        .welcome-wrapper {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(45, 19, 44, 0.15);
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '☪';
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 8rem;
            color: rgba(201, 168, 106, 0.05);
            pointer-events: none;
        }
        
        .welcome-card:hover {
            border-color: var(--royal-gold);
            box-shadow: 0 15px 50px rgba(201, 168, 106, 0.2);
        }
        
        .welcome-logo {
            font-family: 'Dancing Script', cursive;
            font-size: 3.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
        }
        
        .welcome-logo-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: var(--royal-gold);
            font-weight: 300;
        }
        
        .welcome-card h1 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            margin: 1.5rem 0 0.5rem;
            font-size: 2rem;
        }
        
        .welcome-subtitle {
            color: var(--royal-gold);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .ramadan-greeting {
            background: linear-gradient(135deg, rgba(45, 19, 44, 0.05), rgba(201, 168, 106, 0.1));
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(201, 168, 106, 0.2);
        }

        .ramadan-greeting i {
            color: var(--royal-gold);
            font-size: 1.2rem;
        }
        
        .features {
            background: var(--creamy-white);
            padding: 1.5rem;
            border-radius: 15px;
            margin: 1.5rem 0;
            text-align: left;
            border: 1px solid rgba(201, 168, 106, 0.15);
        }
        
        .feature-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--royal-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }
        
        .feature-text {
            color: var(--deep-purple);
            font-size: 0.95rem;
        }
        
        .feature-text strong {
            color: var(--deep-purple);
        }
        
        .buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-welcome {
            flex: 1;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-welcome:hover {
            transform: translateY(-3px);
        }
        
        .btn-welcome-primary {
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
        }
        
        .btn-welcome-primary:hover {
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.5);
            color: var(--deep-purple);
        }
        
        .btn-welcome-secondary {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--gold-light);
            box-shadow: 0 4px 15px rgba(45, 19, 44, 0.2);
        }
        
        .btn-welcome-secondary:hover {
            box-shadow: 0 6px 20px rgba(45, 19, 44, 0.4);
            color: var(--royal-gold);
        }
        
        .guest-link {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 1.5rem;
            color: var(--royal-gold);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .guest-link:hover {
            color: var(--deep-purple);
        }

        @media (max-width: 576px) {
            .buttons {
                flex-direction: column;
            }
            .welcome-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="welcome-wrapper">
        <div class="welcome-card">
            <div class="welcome-logo">Poshy</div>
            <div class="welcome-logo-subtitle">STORE</div>
            
            <div class="ramadan-greeting">
                <i class="fas fa-moon"></i>
                <?= $current_lang === 'ar' ? 'رمضان كريم! عروض حصرية بمناسبة الشهر الفضيل' : 'Ramadan Kareem! Exclusive offers this holy month' ?>
                <i class="fas fa-star-and-crescent"></i>
            </div>
            
            <h1><?= $current_lang === 'ar' ? 'مرحباً بك في بوشي' : 'Welcome to Poshy' ?></h1>
            <p class="welcome-subtitle"><?= $current_lang === 'ar' ? 'وجهتك الفاخرة للتسوق' : 'Your luxury shopping destination' ?></p>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-gem"></i></div>
                    <span class="feature-text"><strong><?= $current_lang === 'ar' ? 'منتجات فاخرة' : 'Premium Products' ?></strong> <?= $current_lang === 'ar' ? 'جاهزة للتصفح' : 'ready to browse' ?></span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-shopping-cart"></i></div>
                    <span class="feature-text"><strong><?= $current_lang === 'ar' ? 'سلة تسوق' : 'Shopping Cart' ?></strong> <?= $current_lang === 'ar' ? 'مع تحديثات مباشرة' : 'with live updates' ?></span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <span class="feature-text"><strong><?= $current_lang === 'ar' ? 'دفع آمن' : 'Secure Checkout' ?></strong> <?= $current_lang === 'ar' ? 'عملية محمية' : 'process' ?></span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-box"></i></div>
                    <span class="feature-text"><strong><?= $current_lang === 'ar' ? 'تتبع الطلبات' : 'Order Tracking' ?></strong> <?= $current_lang === 'ar' ? 'نظام متكامل' : 'system' ?></span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-globe"></i></div>
                    <span class="feature-text"><strong><?= $current_lang === 'ar' ? 'عربي وإنجليزي' : 'Arabic & English' ?></strong> <?= $current_lang === 'ar' ? 'دعم كامل' : 'support' ?></span>
                </div>
            </div>
            
            <div class="buttons">
                <a href="pages/auth/signup.php" class="btn-welcome btn-welcome-secondary">
                    <i class="fas fa-user-plus"></i> <?= $current_lang === 'ar' ? 'إنشاء حساب' : 'Sign Up' ?>
                </a>
                <a href="pages/auth/signin.php" class="btn-welcome btn-welcome-primary">
                    <i class="fas fa-sign-in-alt"></i> <?= $current_lang === 'ar' ? 'تسجيل الدخول' : 'Sign In' ?>
                </a>
            </div>
            
            <a href="index.php" class="guest-link">
                <?= $current_lang === 'ar' ? 'أو تصفح المنتجات كزائر' : 'or browse products as guest' ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/includes/ramadan_footer.php'; ?>
</body>
</html>
