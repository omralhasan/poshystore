<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('isRTL')) { require_once __DIR__ . '/language.php'; }
$lang = $_SESSION['language'] ?? 'en';
?>
<?php if ($lang === 'ar'): ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<?php else: ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php endif; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php if ($lang === 'ar'): ?>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@300;400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<?php else: ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<?php endif; ?>

<style>
    :root {
        --lux-cream: #f9f9f9;
        --lux-charcoal: #111111;
        --lux-gold: #c5a059;
        --lux-gold-soft: #d8be85;
        --lux-border: rgba(17, 17, 17, 0.12);
        --lux-surface: #ffffff;
    }

    * { box-sizing: border-box; }

    body {
        font-family: <?php echo ($lang === 'ar') ? "'Tajawal', 'Cairo', sans-serif" : "'Inter', sans-serif"; ?>;
        background: var(--lux-cream);
        color: var(--lux-charcoal);
        min-height: 100vh;
        overflow-x: hidden;
    }

    [dir="rtl"] {
        text-align: right;
        font-family: 'Tajawal', 'Cairo', sans-serif;
    }

    [dir="rtl"] .me-1 { margin-right: 0 !important; margin-left: 0.25rem !important; }
    [dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }
    [dir="rtl"] .me-3 { margin-right: 0 !important; margin-left: 1rem !important; }
    [dir="rtl"] .ms-1 { margin-left: 0 !important; margin-right: 0.25rem !important; }
    [dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }
    [dir="rtl"] .ms-3 { margin-left: 0 !important; margin-right: 1rem !important; }
    [dir="rtl"] .ms-auto { margin-left: unset !important; margin-right: auto !important; }

    .floating-decorations { display: none !important; }

    .ramadan-navbar {
        background: rgba(249, 249, 249, 0.96);
        backdrop-filter: blur(6px);
        border-bottom: 1px solid var(--lux-border);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
        padding: 0.85rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-brand-ramadan {
        font-family: 'Playfair Display', serif;
        font-size: 1.55rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        color: var(--lux-charcoal) !important;
        text-decoration: none !important;
        line-height: 1;
        transition: color .25s ease;
    }

    .navbar-brand-ramadan .logo-subtitle {
        font-family: <?php echo ($lang === 'ar') ? "'Tajawal', sans-serif" : "'Inter', sans-serif"; ?>;
        font-size: 0.58rem;
        letter-spacing: 0.35em;
        display: block;
        margin-top: 0.2rem;
        color: var(--lux-gold);
        text-transform: uppercase;
        font-weight: 600;
    }

    .nav-link-ramadan {
        color: var(--lux-charcoal) !important;
        font-weight: 500;
        margin: 0 0.35rem;
        text-decoration: none;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        transition: all .25s ease;
    }

    .nav-link-ramadan:hover {
        color: var(--lux-gold) !important;
        background: rgba(197, 160, 89, 0.08);
    }

    .nav-icon-ramadan {
        color: var(--lux-charcoal);
        font-size: 1.12rem;
        margin-left: 0.25rem;
        transition: all .2s ease;
        position: relative;
        border-radius: 10px;
        padding: 0.35rem 0.45rem;
        min-width: 34px;
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .nav-icon-ramadan:hover {
        color: var(--lux-gold);
        background: rgba(197, 160, 89, 0.1);
    }

    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--lux-gold);
        color: #111;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.68rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        border: 1.5px solid #fff;
    }

    .page-container { min-height: calc(100vh - 180px); padding: 1.5rem 0; }

    .btn-ramadan {
        background: linear-gradient(135deg, var(--lux-gold), var(--lux-gold-soft));
        color: #111;
        border: 1px solid transparent;
        border-radius: 999px;
        font-weight: 600;
        padding: 0.7rem 1.4rem;
        transition: all .25s ease;
        box-shadow: 0 10px 24px rgba(197, 160, 89, 0.22);
    }

    .btn-ramadan:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(197, 160, 89, 0.28);
        color: #111;
    }

    .btn-ramadan-secondary {
        background: #fff;
        color: var(--lux-charcoal);
        border: 1px solid var(--lux-border);
        border-radius: 999px;
        font-weight: 600;
        padding: 0.7rem 1.4rem;
        transition: all .25s ease;
    }

    .btn-ramadan-secondary:hover {
        border-color: var(--lux-gold);
        color: var(--lux-gold);
        background: #fff;
    }

    .card-ramadan {
        background: var(--lux-surface);
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        transition: all .25s ease;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    }

    .card-ramadan:hover {
        border-color: rgba(197, 160, 89, 0.35);
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.11);
        transform: translateY(-2px);
    }

    .section-title-ramadan {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--lux-charcoal);
        margin-bottom: 1.6rem;
        text-align: center;
    }

    .section-title-ramadan::after {
        content: '';
        display: block;
        width: 74px;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--lux-gold), transparent);
        margin: .8rem auto 0;
    }

    .footer-ramadan {
        background: #111;
        color: #f5f5f5;
        padding: 2rem 0 1rem;
        margin-top: 3rem;
    }

    .footer-ramadan h5 {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        letter-spacing: 0.12em;
        color: #fff;
        margin-bottom: .75rem;
        line-height: 1;
    }

    .footer-ramadan h5 span:not(.logo-accent) {
        font-family: 'Inter', sans-serif;
        font-size: 0.56rem;
        letter-spacing: 0.34em;
        color: var(--lux-gold);
        text-transform: uppercase;
        font-weight: 600;
        display: inline-block;
        margin-top: .25rem;
    }

    .footer-ramadan a { color: #e7dfcf; text-decoration: none; transition: color .2s ease; }
    .footer-ramadan a:hover { color: var(--lux-gold); }

    .form-control-ramadan {
        border: 1px solid rgba(0, 0, 0, 0.15);
        border-radius: 12px;
        padding: .75rem .9rem;
        transition: all .2s ease;
        font-family: inherit;
        background: #fff;
    }

    .form-control-ramadan:focus {
        border-color: var(--lux-gold);
        box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.16);
        outline: none;
    }

    .alert-ramadan {
        border-radius: 12px;
        border: 1px solid rgba(197, 160, 89, 0.5);
        background: rgba(197, 160, 89, 0.1);
        color: var(--lux-charcoal);
    }

    .mobile-menu-toggle {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        z-index: 1100;
        border-radius: 8px;
    }

    .hamburger-line {
        display: block;
        width: 24px;
        height: 2.5px;
        background: var(--lux-charcoal);
        border-radius: 3px;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
    }

    .mobile-menu-toggle.active .hamburger-line:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
    .mobile-menu-toggle.active .hamburger-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .mobile-menu-toggle.active .hamburger-line:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

    .mobile-menu-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1040;
        opacity: 0;
        transition: opacity .25s ease;
        backdrop-filter: blur(3px);
    }

    .mobile-menu-overlay.open { display: block; opacity: 1; }

    .mobile-menu {
        position: fixed;
        top: 0;
        right: -300px;
        width: 280px;
        height: 100vh;
        background: #fff;
        z-index: 1050;
        transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -8px 0 30px rgba(0, 0, 0, 0.18);
        overflow-y: auto;
    }

    [dir="rtl"] .mobile-menu { right: auto; left: -300px; transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
    .mobile-menu.open { right: 0; }
    [dir="rtl"] .mobile-menu.open { left: 0; }

    .mobile-menu-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--lux-border);
    }

    .mobile-menu-brand {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        letter-spacing: .14em;
        color: var(--lux-charcoal);
    }

    .mobile-menu-brand small {
        font-family: 'Inter', sans-serif;
        font-size: .48rem;
        letter-spacing: .32em;
        color: var(--lux-gold);
        display: block;
        text-transform: uppercase;
        font-weight: 600;
    }

    .mobile-menu-close {
        background: rgba(197, 160, 89, 0.1);
        border: none;
        color: var(--lux-charcoal);
        font-size: 1.2rem;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .mobile-menu-links { padding: .6rem 0; }

    .mobile-menu-link {
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: .85rem 1.2rem;
        color: var(--lux-charcoal);
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        transition: all .2s ease;
        border-left: 3px solid transparent;
    }

    .mobile-menu-link:hover { background: rgba(197, 160, 89, 0.08); color: var(--lux-gold); border-left-color: var(--lux-gold); }
    .mobile-menu-link i { color: var(--lux-gold); width: 20px; text-align: center; }

    .mobile-menu-link-danger { color: #dc2626; }
    .mobile-menu-link-danger i { color: #dc2626; }
    .mobile-menu-divider { height: 1px; background: rgba(0,0,0,.08); margin: .4rem 1.2rem; }

    .mobile-cart-count {
        background: var(--lux-gold);
        color: #111;
        border-radius: 10px;
        padding: 1px 7px;
        font-size: .72rem;
        font-weight: 700;
        margin-left: .4rem;
    }

    .mobile-bottom-nav { display: none; }

    @media (max-width: 768px) {
        .ramadan-navbar { padding: .55rem 0; }
        .navbar-brand-ramadan { font-size: 1.2rem !important; }
        .navbar-brand-ramadan .logo-subtitle { font-size: .42rem !important; letter-spacing: .24em !important; }
        .nav-icon-ramadan { font-size: 1rem; min-width: 32px; min-height: 32px; }
        body { padding-bottom: 68px; }
        .footer-ramadan { padding-bottom: 5rem; }

        .mobile-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #111;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 -6px 22px rgba(0,0,0,.28);
            height: 62px;
            align-items: stretch;
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: rgba(255,255,255,.7);
            text-decoration: none;
            font-size: .58rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: color .2s, background .2s;
            padding: 6px 0;
            border-top: 2px solid transparent;
            position: relative;
        }

        .bottom-nav-item i { font-size: 1.15rem; }
        .bottom-nav-item.active, .bottom-nav-item:active { color: var(--lux-gold); border-top-color: var(--lux-gold); background: rgba(197,160,89,.1); }

        .bottom-nav-badge {
            position: absolute;
            top: 4px;
            left: calc(50% + 4px);
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 1px 5px;
            font-size: .6rem;
            font-weight: 700;
            min-width: 16px;
            text-align: center;
            line-height: 1.4;
            border: 1.5px solid #111;
        }

        [dir="rtl"] .bottom-nav-badge { left: auto; right: calc(50% + 4px); }
    }
</style>
