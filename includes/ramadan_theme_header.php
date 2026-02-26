<?php
// Ensure session is running and language is loaded
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('isRTL')) { require_once __DIR__ . '/language.php'; }
// Always read directly from session - no caching
?>
<!-- Bootstrap 5 CSS (RTL for Arabic, LTR for English) -->
<?php if (($_SESSION['language'] ?? 'en') === 'ar'): ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<?php else: ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php endif; ?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<?php if (($_SESSION['language'] ?? 'en') === 'ar'): ?>
<!-- Arabic Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@300;400;600;700&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
<?php else: ?>
<!-- English Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;600;700&family=Dancing+Script:wght@600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
<?php endif; ?>

<style>
    :root {
        --deep-purple: #2d132c;
        --royal-gold: #c9a86a;
        --creamy-white: #fcf8f2;
        --gold-light: #e4d4b4;
        --purple-dark: #1a0a18;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: <?php echo (($_SESSION['language'] ?? 'en') === 'ar') ? "'Tajawal', 'Cairo', sans-serif" : "'Montserrat', 'Tajawal', sans-serif"; ?>;
        background-color: var(--creamy-white);
        color: var(--deep-purple);
        overflow-x: hidden;
        min-height: 100vh;
    }
    
    /* RTL full mirroring for Arabic */
    [dir="rtl"] {
        text-align: right;
        font-family: 'Tajawal', 'Cairo', sans-serif;
    }
    [dir="rtl"] h1, [dir="rtl"] h2, [dir="rtl"] h3,
    [dir="rtl"] h4, [dir="rtl"] h5, [dir="rtl"] h6,
    [dir="rtl"] p, [dir="rtl"] span, [dir="rtl"] a,
    [dir="rtl"] label, [dir="rtl"] input, [dir="rtl"] textarea {
        font-family: 'Tajawal', 'Cairo', sans-serif;
    }
    /* Flip icons next to text in RTL */
    [dir="rtl"] .me-1 { margin-right: 0 !important; margin-left: 0.25rem !important; }
    [dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }
    [dir="rtl"] .me-3 { margin-right: 0 !important; margin-left: 1rem !important; }
    [dir="rtl"] .ms-1 { margin-left: 0 !important; margin-right: 0.25rem !important; }
    [dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }
    [dir="rtl"] .ms-3 { margin-left: 0 !important; margin-right: 1rem !important; }
    [dir="rtl"] .ms-auto { margin-left: unset !important; margin-right: auto !important; }
    /* Float and flex mirroring */
    [dir="rtl"] .float-end { float: left !important; }
    [dir="rtl"] .float-start { float: right !important; }
    [dir="rtl"] .text-end { text-align: left !important; }
    [dir="rtl"] .text-start { text-align: right !important; }
    /* Padding flip */
    [dir="rtl"] .ps-3 { padding-left: 0 !important; padding-right: 1rem !important; }
    [dir="rtl"] .pe-3 { padding-right: 0 !important; padding-left: 1rem !important; }
    
    /* Floating Ramadan Elements */
    .floating-decorations {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    
    .floating-icon {
        position: absolute;
        color: var(--royal-gold);
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }
    
    .floating-icon:nth-child(1) { top: 10%; left: 10%; font-size: 3rem; animation-delay: 0s; }
    .floating-icon:nth-child(2) { top: 20%; right: 15%; font-size: 2.5rem; animation-delay: 1s; }
    .floating-icon:nth-child(3) { top: 60%; left: 5%; font-size: 2rem; animation-delay: 2s; }
    .floating-icon:nth-child(4) { top: 70%; right: 10%; font-size: 3.5rem; animation-delay: 1.5s; }
    .floating-icon:nth-child(5) { top: 40%; right: 5%; font-size: 2.2rem; animation-delay: 0.5s; }
    .floating-icon:nth-child(6) { top: 85%; left: 20%; font-size: 2.8rem; animation-delay: 2.5s; }
    
    @keyframes float {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    
    /* Navbar */
    .ramadan-navbar {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        box-shadow: 0 4px 20px rgba(45, 19, 44, 0.3);
        padding: 1rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .navbar-brand-ramadan {
        font-family: 'Dancing Script', cursive;
        font-size: 3rem;
        font-weight: 600;
        background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none !important;
        line-height: 1.1;
        transition: all 0.3s ease;
        filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
    }
    
    .navbar-brand-ramadan .logo-accent {
        display: none;
    }
    
    .navbar-brand-ramadan .logo-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 5px;
        display: block;
        margin-top: 0.3rem;
        text-transform: uppercase;
        font-weight: 300;
        background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .navbar-brand-ramadan:hover {
        opacity: 0.85;
        transform: scale(1.02);
    }
    
    .nav-link-ramadan {
        color: var(--creamy-white) !important;
        font-weight: 500;
        margin: 0 0.5rem;
        transition: all 0.3s;
        text-decoration: none;
        padding: 0.5rem 1rem;
    }
    
    .nav-link-ramadan:hover {
        color: var(--royal-gold) !important;
        transform: translateY(-2px);
    }
    
    .nav-icon-ramadan {
        color: var(--royal-gold);
        font-size: 1.3rem;
        margin-left: 1rem;
        transition: all 0.3s;
        position: relative;
    }
    
    .nav-icon-ramadan:hover {
        color: var(--gold-light);
        transform: scale(1.1);
    }
    
    .cart-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: linear-gradient(135deg, #ff4757, #dc3545);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.5);
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    /* Page Container */
    .page-container {
        min-height: calc(100vh - 200px);
        padding: 2rem 0;
        position: relative;
        z-index: 2;
    }
    
    /* Buttons */
    .btn-ramadan {
        background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
        color: var(--deep-purple);
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
    }
    
    .btn-ramadan:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(201, 168, 106, 0.5);
        color: var(--deep-purple);
    }
    
    .btn-ramadan-secondary {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        color: var(--royal-gold);
        border: 2px solid var(--royal-gold);
        padding: 0.8rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-ramadan-secondary:hover {
        background: var(--royal-gold);
        color: var(--deep-purple);
        transform: translateY(-3px);
    }
    
    /* Cards */
    .card-ramadan {
        background: white;
        border-radius: 15px;
        border: 2px solid transparent;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(45, 19, 44, 0.1);
    }
    
    .card-ramadan:hover {
        border-color: var(--royal-gold);
        box-shadow: 0 10px 30px rgba(201, 168, 106, 0.3);
        transform: translateY(-5px);
    }
    
    /* Section Titles */
    .section-title-ramadan {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--deep-purple);
        margin-bottom: 2rem;
        position: relative;
        text-align: center;
    }
    
    .section-title-ramadan::after {
        content: '';
        display: block;
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
        margin: 1rem auto 0;
    }
    
    /* Footer */
    .footer-ramadan {
        background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
        color: var(--creamy-white);
        padding: 2rem 0 1rem;
        margin-top: 4rem;
    }
    
    .footer-ramadan h5 {
        font-family: 'Dancing Script', cursive;
        font-weight: 600;
        font-size: 2.5rem;
        background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.1;
        filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
    }
    
    .footer-ramadan h5 span:not(.logo-accent) {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 5px;
        text-transform: uppercase;
        font-weight: 300;
        background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .footer-ramadan .logo-accent {
        display: none;
    }
    
    .footer-ramadan a {
        color: var(--gold-light);
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer-ramadan a:hover {
        color: var(--royal-gold);
    }
    
    /* Forms */
    .form-control-ramadan {
        border: 2px solid var(--gold-light);
        border-radius: 10px;
        padding: 0.8rem;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-control-ramadan:focus {
        border-color: var(--royal-gold);
        box-shadow: 0 0 15px rgba(201, 168, 106, 0.3);
        outline: none;
    }
    
    textarea.form-control-ramadan {
        border: 2px solid var(--gold-light);
        border-radius: 12px;
        padding: 1rem;
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        min-height: 80px;
        background: linear-gradient(to bottom, #ffffff 0%, #fefdfb 100%);
        transition: all 0.3s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    textarea.form-control-ramadan:hover {
        border-color: var(--gold-color);
        box-shadow: 0 2px 8px rgba(201, 168, 106, 0.2);
    }
    
    textarea.form-control-ramadan:focus {
        border-color: var(--royal-gold);
        box-shadow: 0 0 20px rgba(201, 168, 106, 0.4), inset 0 2px 4px rgba(201, 168, 106, 0.1);
        outline: none;
        background: #ffffff;
        transform: translateY(-1px);
    }
    
    textarea.form-control-ramadan::placeholder {
        color: #999;
        font-style: italic;
        opacity: 0.7;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar {
        width: 8px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-thumb {
        background: var(--gold-light);
        border-radius: 10px;
    }
    
    textarea.form-control-ramadan::-webkit-scrollbar-thumb:hover {
        background: var(--gold-color);
    }
    
    /* Alerts */
    .alert-ramadan {
        border-radius: 10px;
        border: 2px solid var(--royal-gold);
        background: rgba(201, 168, 106, 0.1);
        color: var(--deep-purple);
    }
    
    /* ============================================
       MOBILE HAMBURGER MENU & MODERN RESPONSIVE
       ============================================ */
    
    /* Hamburger Button */
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
        transition: background 0.3s;
    }
    
    .mobile-menu-toggle:hover,
    .mobile-menu-toggle:focus {
        background: rgba(201, 168, 106, 0.15);
        outline: none;
    }
    
    .hamburger-line {
        display: block;
        width: 24px;
        height: 2.5px;
        background: var(--royal-gold);
        border-radius: 3px;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
    }
    
    .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
        transform: translateY(7.5px) rotate(45deg);
    }
    .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }
    .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
        transform: translateY(-7.5px) rotate(-45deg);
    }
    
    /* Mobile Menu Overlay */
    .mobile-menu-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .mobile-menu-overlay.open {
        display: block;
        opacity: 1;
    }
    
    /* Mobile Slide-out Menu */
    .mobile-menu {
        position: fixed;
        top: 0;
        right: -300px;
        width: 280px;
        height: 100vh;
        background: linear-gradient(180deg, var(--deep-purple) 0%, #1a0a18 100%);
        z-index: 1050;
        transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -5px 0 30px rgba(0, 0, 0, 0.4);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    [dir="rtl"] .mobile-menu {
        right: auto;
        left: -300px;
        transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .mobile-menu.open {
        right: 0;
    }
    [dir="rtl"] .mobile-menu.open {
        left: 0;
    }
    
    .mobile-menu-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(201, 168, 106, 0.2);
    }
    
    .mobile-menu-brand {
        font-family: 'Dancing Script', cursive;
        font-size: 1.8rem;
        background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 600;
    }
    .mobile-menu-brand small {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.5rem;
        letter-spacing: 4px;
        display: block;
        text-transform: uppercase;
        background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .mobile-menu-close {
        background: rgba(201, 168, 106, 0.15);
        border: none;
        color: var(--royal-gold);
        font-size: 1.3rem;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .mobile-menu-close:hover {
        background: rgba(201, 168, 106, 0.3);
        transform: rotate(90deg);
    }
    
    .mobile-menu-links {
        padding: 1rem 0;
    }
    
    .mobile-menu-link {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        color: var(--creamy-white);
        text-decoration: none;
        font-weight: 500;
        font-size: 1.05rem;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    [dir="rtl"] .mobile-menu-link {
        border-left: none;
        border-right: 3px solid transparent;
    }
    
    .mobile-menu-link:hover,
    .mobile-menu-link:focus {
        background: rgba(201, 168, 106, 0.1);
        color: var(--royal-gold);
        border-left-color: var(--royal-gold);
    }
    [dir="rtl"] .mobile-menu-link:hover,
    [dir="rtl"] .mobile-menu-link:focus {
        border-right-color: var(--royal-gold);
    }
    
    .mobile-menu-link i {
        font-size: 1.2rem;
        width: 28px;
        text-align: center;
        color: var(--royal-gold);
        transition: transform 0.3s;
    }
    .mobile-menu-link:hover i {
        transform: scale(1.15);
    }
    
    .mobile-menu-link-danger {
        color: #ff6b6b;
    }
    .mobile-menu-link-danger i {
        color: #ff6b6b;
    }
    .mobile-menu-link-danger:hover {
        color: #ff4757;
        border-left-color: #ff4757;
    }
    
    .mobile-menu-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(201, 168, 106, 0.3), transparent);
        margin: 0.75rem 1.5rem;
    }
    
    .mobile-cart-count {
        background: linear-gradient(135deg, #ff4757, #dc3545);
        color: white;
        border-radius: 12px;
        padding: 1px 8px;
        font-size: 0.75rem;
        font-weight: 700;
        margin-left: 0.5rem;
    }
    
    /* ============================================
       GLOBAL MOBILE RESPONSIVE IMPROVEMENTS
       ============================================ */

    @media (max-width: 1024px) {
        /* Hide floating decorations on tablets and phones */
        .floating-decorations {
            display: none;
        }
    }

    @media (max-width: 768px) {
        /* Navbar mobile tweaks */
        .ramadan-navbar {
            padding: 0.5rem 0;
        }
        .ramadan-navbar .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        .navbar-brand-ramadan {
            font-size: 1.75rem !important;
            line-height: 1 !important;
        }
        .navbar-brand-ramadan .logo-subtitle {
            font-size: 0.45rem !important;
            letter-spacing: 2.5px !important;
            margin-top: 0.15rem !important;
        }
        
        /* Nav icons - touch-friendly */
        .nav-icon-ramadan {
            font-size: 1.1rem;
            padding: 7px;
            margin-left: 0.2rem;
            border-radius: 8px;
            min-width: 36px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .nav-icon-ramadan:active {
            transform: scale(0.9);
            background: rgba(201, 168, 106, 0.2);
        }
        
        .cart-badge {
            width: 18px;
            height: 18px;
            font-size: 0.6rem;
            top: -4px;
            right: -4px;
        }
        
        /* Page container */
        .page-container {
            padding: 1rem 0;
        }
        
        /* Section titles */
        .section-title-ramadan {
            font-size: 1.4rem !important;
            margin-bottom: 1.25rem;
        }
        
        /* Cards */
        .card-ramadan {
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .card-ramadan:hover {
            transform: none;
        }
        
        /* Buttons */
        .btn-ramadan, .btn-ramadan-secondary {
            padding: 0.7rem 1.25rem;
            font-size: 0.92rem;
            border-radius: 20px;
            touch-action: manipulation;
        }
        
        /* Forms */
        .form-control-ramadan {
            font-size: 16px !important;
            padding: 0.85rem !important;
        }
        
        /* Footer */
        .footer-ramadan {
            margin-top: 1.5rem;
            padding: 1.25rem 0 5rem;  /* 5rem bottom padding for bottom nav */
        }
        .footer-ramadan h5 {
            font-size: 1.6rem !important;
        }
        
        /* Hide floating decorations on mobile */
        .floating-decorations {
            display: none;
        }
        
        /* Prevent hover effects on touch */
        .p-card:hover {
            transform: none !important;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Add padding at bottom for mobile bottom nav */
        body {
            padding-bottom: 70px;
        }
    }
    
    /* Very small phones */
    @media (max-width: 390px) {
        .navbar-brand-ramadan {
            font-size: 1.5rem !important;
        }
        .nav-icon-ramadan {
            font-size: 1rem;
            margin-left: 0.1rem;
            min-width: 32px;
            min-height: 32px;
        }
    }
    
    /* ============================================
       MOBILE BOTTOM NAVIGATION BAR
       ============================================ */
    .mobile-bottom-nav {
        display: none;
    }
    
    @media (max-width: 768px) {
        .mobile-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: linear-gradient(180deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            border-top: 1px solid rgba(201, 168, 106, 0.25);
            box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
            padding: 0;
            height: 62px;
            align-items: stretch;
            -webkit-overflow-scrolling: touch;
        }
        
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: rgba(201, 168, 106, 0.55);
            text-decoration: none;
            font-size: 0.58rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            transition: color 0.2s, background 0.2s;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            padding: 6px 0;
            border-top: 2px solid transparent;
            position: relative;
        }
        
        .bottom-nav-item i {
            font-size: 1.2rem;
            line-height: 1;
        }
        
        .bottom-nav-item.active,
        .bottom-nav-item:active {
            color: var(--royal-gold);
            border-top-color: var(--royal-gold);
            background: rgba(201, 168, 106, 0.07);
        }
        
        .bottom-nav-item:focus {
            outline: none;
            color: var(--royal-gold);
        }
        
        .bottom-nav-badge {
            position: absolute;
            top: 4px;
            left: calc(50% + 4px);
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 1px 5px;
            font-size: 0.6rem;
            font-weight: 700;
            min-width: 16px;
            text-align: center;
            line-height: 1.4;
            border: 1.5px solid var(--purple-dark);
        }
        
        [dir="rtl"] .bottom-nav-badge {
            left: auto;
            right: calc(50% + 4px);
        }
    }
</style>
